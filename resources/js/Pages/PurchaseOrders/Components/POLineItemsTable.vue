<script setup lang="ts">
import { DataTable, Column, Button, InputNumber, AutoComplete, Tag, useToast, useConfirm } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { ref, computed, watch } from "vue";
import { usePurchaseOrderClient } from "@/Composables/usePurchaseOrderClient";
import POVariantVendorsDialog from "./POVariantVendorsDialog.vue";

export interface LineItem {
  id: string;
  catalog_id: number;
  product_variant_id: number;
  product_name: string;
  variant_label: string;
  quantity: number;
  price: number;
  total: number;
  stock?: number | null;
  minimum_stock_level?: number | null;
  payment_terms?: string | null;
  details?: string | null;
  unit_id?: number | null;
  purchase_unit?: { id: number; name: string; conversion_factor: number } | null;
  base_unit?: { id: number; name: string } | null;
  minimum_order_quantity?: number | null;
  lead_time_days?: number | null;
}

const props = defineProps<{
  vendorId: number | null;
  modelValue: LineItem[];
}>();

const emit = defineEmits<{
  (e: "update:modelValue", items: LineItem[]): void;
}>();

const { t } = useI18n();
const { formatCurrency, currencyCode } = useCurrencyFormatter();
const toast = useToast();
const confirm = useConfirm();
const { fetchVendorCatalogApi } = usePurchaseOrderClient();

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const searchResults = ref<any[]>([]);
const searchLoading = ref(false);
const selectedEntry = ref<Record<string, unknown> | null>(null);
const expandedRows = ref<LineItem[]>([]);

const vendorsDialogVisible = ref(false);
const vendorsDialogVariantId = ref<number | null>(null);
const vendorsDialogProductName = ref("");
const vendorsDialogVariantLabel = ref("");

function openVendorsDialog(item: LineItem) {
  vendorsDialogVariantId.value = item.product_variant_id;
  vendorsDialogProductName.value = item.product_name;
  vendorsDialogVariantLabel.value = item.variant_label;
  vendorsDialogVisible.value = true;
}

const items = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

function getStockSeverity(stock: number | null | undefined, minStock: number | null | undefined): "success" | "warn" | "danger" {
  if (stock === null || stock === undefined) return "success";
  if (stock === 0) return "danger";
  if (minStock && stock <= minStock) return "warn";
  return "success";
}

function getStockLabel(stock: number | null | undefined): string {
  if (stock === null || stock === undefined) return "—";
  if (stock === 0) return t("Out of stock");
  return `${t("In stock")}: ${String(stock)}`;
}

function hasExpandableData(item: LineItem): boolean {
  return !!(item.minimum_order_quantity || item.lead_time_days || item.payment_terms || item.details || item.purchase_unit);
}

async function searchVariants(event: { query: string }) {
  if (!props.vendorId) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Select a vendor first"), life: 3000 });
    return;
  }
  if (!event.query || event.query.length < 2) {
    searchResults.value = [];
    return;
  }
  searchLoading.value = true;
  try {
    const response = await fetchVendorCatalogApi(props.vendorId, event.query);
    const data = response.data?.data || response.data || [];
    searchResults.value = Array.isArray(data) ? data : [];
  } catch {
    searchResults.value = [];
    toast.add({ severity: "error", summary: t("Error"), detail: t("Failed to search products"), life: 3000 });
  } finally {
    searchLoading.value = false;
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function onEntrySelect(event: { value: any }) {
  const entry = event.value;
  const variant = entry.product_variant;
  const variantId = variant.id;
  const catalogId = entry.id;

  const exists = items.value.some((i) => i.catalog_id === catalogId);
  if (exists) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Product already added"), life: 3000 });
    selectedEntry.value = null;
    return;
  }

  const productName = variant?.product?.name ?? variant?.name ?? "—";
  const variantLabel = variant?.name ?? variant?.identifier ?? productName;
  const purchaseUnit = entry.purchase_unit;
  const measurementUnit = variant?.product?.measurement_unit;
  const unitLabel = purchaseUnit?.name ?? measurementUnit?.name;
  const displayLabel = unitLabel ? `${variantLabel} (${unitLabel})` : variantLabel;
  const price = parseFloat(String(entry.price ?? variant?.price ?? 0));

  const newItem: LineItem = {
    id: crypto.randomUUID(),
    catalog_id: catalogId,
    product_variant_id: Number(variantId),
    product_name: productName,
    variant_label: displayLabel,
    quantity: entry.minimum_order_quantity ?? 1,
    price,
    total: (entry.minimum_order_quantity ?? 1) * price,
    stock: variant?.stock ?? null,
    minimum_stock_level: variant?.minimum_stock_level ?? null,
    payment_terms: getPaymentTermsLabel(entry.payment_terms),
    details: entry.details ?? null,
    unit_id: entry.unit_id ?? null,
    purchase_unit: purchaseUnit ?? (measurementUnit ? { id: measurementUnit.id, name: measurementUnit.name, conversion_factor: 1 } : null),
    base_unit: measurementUnit ? { id: measurementUnit.id, name: measurementUnit.name } : null,
    minimum_order_quantity: entry.minimum_order_quantity ?? null,
    lead_time_days: entry.lead_time_days ?? null,
  };

  emit("update:modelValue", [...items.value, newItem]);
  selectedEntry.value = null;
}

function getPaymentTermsLabel(paymentTerms: string): string {
  switch (paymentTerms) {
    case "debit":
      return t("Cash");
    case "credit":
      return t("Credit");
    case "both":
      return t("Cash / Credit");
    default:
      return paymentTerms;
  }
}

function updateQuantity(index: number, quantity: number) {
  const updated = [...items.value];
  updated[index] = {
    ...updated[index],
    quantity,
    total: quantity * updated[index].price,
  };
  emit("update:modelValue", updated);
}

function updatePrice(index: number, price: number) {
  const updated = [...items.value];
  updated[index] = {
    ...updated[index],
    price,
    total: updated[index].quantity * price,
  };
  emit("update:modelValue", updated);
}

function removeItem(index: number) {
  const updated = items.value.filter((_, i) => i !== index);
  emit("update:modelValue", updated);
}

function confirmRemoveItem(index: number) {
  confirm.require({
    message: t("Are you sure you want to remove this item?"),
    header: t("Confirm"),
    icon: "fa fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    acceptClass: "p-button-primary",
    accept: () => {
      removeItem(index);
    },
  });
}

watch(
  () => props.vendorId,
  () => {
    if (items.value.length > 0) {
      emit("update:modelValue", []);
    }
    selectedEntry.value = null;
    searchResults.value = [];
    expandedRows.value = [];
  },
);
</script>

<template>
  <div>
    <div class="flex flex-col gap-2 mb-3">
      <label>{{ t("Add Product") }}</label>
      <AutoComplete
        v-model="selectedEntry"
        :suggestions="searchResults"
        option-label="id"
        :placeholder="t('Search product...')"
        :empty-search-message="t('No results found')"
        :loading="searchLoading"
        :disabled="!vendorId"
        dropdown
        force-selection
        class="w-full"
        @complete="searchVariants"
        @item-select="onEntrySelect"
      >
        <template #header>
          <div
            class="hidden lg:grid grid-cols-12 gap-2 px-3 py-2 text-sm font-semibold text-surface-500 uppercase tracking-wide border-b border-surface-200 dark:border-surface-700"
          >
            <span class="col-span-4">{{ t("Product") }}</span>
            <span class="col-span-2">{{ t("Unit") }}</span>
            <span class="col-span-2">{{ t("Price") }}</span>
            <span class="col-span-2">{{ t("Stock") }}</span>
            <span class="col-span-2">{{ t("Details") }}</span>
          </div>
        </template>
        <template #option="{ option }">
          <!-- Desktop: grid row -->
          <div class="hidden lg:grid grid-cols-12 gap-2 items-center w-full py-1">
            <div class="col-span-4 flex flex-col gap-0.5 min-w-0">
              <span class="font-medium text-sm truncate">{{ option.product_variant?.product?.name ?? option.product_variant?.name }}</span>
              <span class="text-sm text-surface-500 truncate">
                {{ option.product_variant?.name ?? option.product_variant?.identifier }}
              </span>
            </div>
            <div class="col-span-2">
              <span v-if="option.purchase_unit?.name" class="ml-1">
                {{ option.purchase_unit.name }}
                <span v-if="option.purchase_unit.conversion_factor !== 1" class="text-surface-500 ml-1">
                  (x{{ option.purchase_unit.conversion_factor }} {{ option.product_variant.product.measurement_unit?.name }})
                </span>
              </span>
              <span v-else-if="option.product_variant?.product?.measurement_unit" class="ml-1">
                {{ option.product_variant.product.measurement_unit.name }}
              </span>
            </div>
            <div class="col-span-2">
              <Tag
                :value="getStockLabel(option.product_variant?.stock)"
                :severity="getStockSeverity(option.product_variant?.stock, option.product_variant?.minimum_stock_level)"
                class="text-sm"
                rounded
              />
            </div>
            <div class="col-span-2 font-medium text-sm">
              {{ formatCurrency(String(option.price)) }}
            </div>
            <div class="col-span-2 flex flex-col gap-0.5 text-sm min-w-0">
              <span v-if="option.minimum_order_quantity" class="truncate">{{ t("Min. Order") }}: {{ option.minimum_order_quantity }}</span>
              <span v-if="option.lead_time_days" class="truncate">{{ t("Lead time") }}: {{ option.lead_time_days }} {{ t("days") }}</span>
              <span v-if="option.payment_terms" class="truncate">
                {{ getPaymentTermsLabel(option.payment_terms) }}
              </span>
            </div>
          </div>
          <!-- Mobile: card layout -->
          <div class="lg:hidden flex flex-col gap-1.5 py-2 w-full">
            <div class="flex items-center justify-between">
              <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                <span class="font-medium text-sm truncate">
                  {{ option.product_variant?.product?.name ?? option.product_variant?.name }}
                </span>
                <span class="text-xs text-surface-500 truncate">
                  {{ option.product_variant?.name ?? option.product_variant?.identifier }}
                  <span v-if="option.purchase_unit?.name" class="ml-1">({{ option.purchase_unit.name }})</span>
                  <span v-else-if="option.product_variant?.product?.measurement_unit" class="ml-1">
                    ({{ option.product_variant.product.measurement_unit.name }})
                  </span>
                </span>
              </div>
              <div class="flex flex-col items-end gap-2">
                <span class="font-medium">{{ formatCurrency(String(option.price)) }}</span>
                <Tag
                  :value="getStockLabel(option.product_variant?.stock)"
                  :severity="getStockSeverity(option.product_variant?.stock, option.product_variant?.minimum_stock_level)"
                  class="text-xs"
                />
              </div>
            </div>
            <div v-if="option.payment_terms" class="text-xs text-surface-500">
              {{ getPaymentTermsLabel(option.payment_terms) }}
            </div>
            <div class="flex items-center gap-3 text-sm">
              <span v-if="option.minimum_order_quantity" class="text-xs text-surface-500">
                {{ t("Min. Order") }}: {{ option.minimum_order_quantity }}
              </span>
              <span v-if="option.lead_time_days" class="text-xs text-surface-500">
                {{ t("Lead time") }}: {{ option.lead_time_days }} {{ t("days") }}
              </span>
            </div>
          </div>
        </template>
      </AutoComplete>
      <small v-if="!vendorId" class="text-surface-400">{{ t("Select a vendor first to add products") }}</small>
    </div>

    <DataTable
      v-model:expanded-rows="expandedRows"
      :value="items"
      data-key="id"
      class="mt-4 border-t-2 border-surface-200"
      striped-rows
      row-hover
      scrollable
      scroll-direction="both"
    >
      <template #empty>
        <div class="flex flex-col items-center justify-center py-10 text-surface-400">
          <i class="fa fa-cart-plus text-4xl mb-3"></i>
          <span class="font-medium text-lg mb-1">{{ t("No items added yet") }}</span>
          <small v-if="!vendorId">{{ t("Select a vendor first to add products") }}</small>
          <small v-else>{{ t("Use the search above to add products") }}</small>
        </div>
      </template>

      <Column expander style="width: 3rem" />

      <Column :header="t('Product')" style="min-width: 180px">
        <template #body="{ data }">
          <span class="font-medium">{{ data.product_name }}</span>
          <div class="text-sm text-surface-500">{{ data.variant_label }}</div>
        </template>
      </Column>

      <Column :header="t('Stock')" style="min-width: 90px">
        <template #body="{ data }">
          <Tag
            :value="getStockLabel(data.stock)"
            :severity="getStockSeverity(data.stock, data.minimum_stock_level)"
            class="text-xs"
            rounded
          />
        </template>
      </Column>

      <Column :header="t('Unit Price')" style="min-width: 150px">
        <template #body="{ data, index }">
          <InputNumber
            :model-value="data.price"
            :min="0.01"
            :min-fraction-digits="2"
            :max-fraction-digits="4"
            mode="currency"
            :currency="currencyCode"
            size="small"
            input-class="w-full tabular-nums"
            @update:model-value="(val: number) => updatePrice(index, val)"
          />
        </template>
      </Column>

      <Column :header="t('Quantity')" style="min-width: 140px">
        <template #body="{ data, index }">
          <div class="flex flex-col gap-0.5">
            <InputNumber
              :model-value="data.quantity"
              :min="0.01"
              :max="99999"
              :step="1"
              :min-fraction-digits="1"
              :max-fraction-digits="2"
              show-buttons
              size="small"
              input-class="tabular-nums w-32"
              @update:model-value="(val: number) => updateQuantity(index, val)"
            ></InputNumber>
            <small v-if="data.minimum_order_quantity" class="text-surface-400 text-xs">
              {{ t("Min. Order") }}: {{ data.minimum_order_quantity }}
            </small>
          </div>
        </template>
      </Column>

      <Column :header="t('Line Total')" style="min-width: 120px">
        <template #body="{ data }">
          <span class="font-semibold tabular-nums">{{ formatCurrency(String(data.total)) }}</span>
        </template>
      </Column>

      <Column style="min-width: 80px; width: 80px">
        <template #body="{ data, index }">
          <div class="flex gap-1">
            <Button
              v-tooltip.top="t('View Vendors')"
              icon="fa fa-store"
              text
              rounded
              size="small"
              @click="openVendorsDialog(data)"
            />
            <Button v-tooltip.top="t('Delete')" icon="fa fa-trash-can" text rounded size="small" @click="confirmRemoveItem(index)" />
          </div>
        </template>
      </Column>

      <template #expansion="{ data }">
        <div v-if="hasExpandableData(data)" class="px-4 py-3">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div v-if="data.minimum_order_quantity">
              <span class="text-surface-500 block mb-1">{{ t("Min. Order") }}</span>
              <span class="font-medium">{{ data.minimum_order_quantity }}</span>
            </div>
            <div v-if="data.lead_time_days">
              <span class="text-surface-500 block mb-1">{{ t("Lead Time") }}</span>
              <span class="font-medium">{{ data.lead_time_days }} {{ t("days") }}</span>
            </div>
            <div v-if="data.payment_terms">
              <span class="text-surface-500 block mb-1">{{ t("Payment Terms") }}</span>
              <span class="font-medium">{{ data.payment_terms }}</span>
            </div>
            <div v-if="data.details">
              <span class="text-surface-500 block mb-1">{{ t("Details") }}</span>
              <span class="font-medium">{{ data.details }}</span>
            </div>
            <div v-if="data.purchase_unit">
              <span class="text-surface-500 block mb-1">{{ t("Purchase Unit") }}</span>
              <span class="font-medium">{{ data.purchase_unit.name }}</span>
              <span v-if="data.purchase_unit.conversion_factor !== 1" class="text-surface-500 ml-1">
                (x{{ data.purchase_unit.conversion_factor }} {{ data.base_unit.name }})
              </span>
            </div>
          </div>
        </div>
      </template>
    </DataTable>

    <POVariantVendorsDialog
      v-model:visible="vendorsDialogVisible"
      :product-variant-id="vendorsDialogVariantId"
      :product-name="vendorsDialogProductName"
      :variant-label="vendorsDialogVariantLabel"
    />
  </div>
</template>
