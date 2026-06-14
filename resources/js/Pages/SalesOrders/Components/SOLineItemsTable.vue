<script setup lang="ts">
import { DataTable, Column, Button, InputNumber, AutoComplete, Tag, Select, useToast, useConfirm } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { ref, computed } from "vue";
import { useSalesOrderClient } from "@/Composables/useSalesOrderClient";
import type { SalesOrderLineItemForm, VariantSearchResult } from "@/Types/sales-order-types";

export type LineItem = SalesOrderLineItemForm;

const props = defineProps<{
  modelValue: LineItem[];
}>();

const emit = defineEmits<{
  (e: "update:modelValue", items: LineItem[]): void;
}>();

const { t } = useI18n();
const { formatCurrency, currencyCode } = useCurrencyFormatter();
const toast = useToast();
const confirm = useConfirm();
const { searchVariantsApi } = useSalesOrderClient();

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const searchResults = ref<any[]>([]);
const searchLoading = ref(false);
const selectedEntry = ref<Record<string, unknown> | null>(null);
const expandedRows = ref<LineItem[]>([]);

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
  return !!(item.sale_units && item.sale_units.length > 1);
}

async function searchVariants(event: { query: string }) {
  if (!event.query || event.query.length < 2) {
    searchResults.value = [];
    return;
  }
  searchLoading.value = true;
  try {
    const response = await searchVariantsApi(event.query);
    const data = response.data?.data ?? [];
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
  const variant: VariantSearchResult = event.value;
  const variantId = variant.id;

  const exists = items.value.some((i) => i.product_variant_id === variantId);
  if (exists) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Product already added"), life: 3000 });
    selectedEntry.value = null;
    return;
  }

  const productName = variant.product?.name ?? variant.name ?? "—";
  const variantLabel = variant.identifier ?? variant.name ?? productName;
  const saleUnits = variant.sale_units ?? [];
  const basePrice = variant.price ? parseFloat(String(variant.price)) : 0;
  const firstSaleUnit = saleUnits.length > 0 ? saleUnits[0] : null;

  // Build unit label for display
  const unitLabel = firstSaleUnit?.name;
  const displayLabel = unitLabel ? `${variantLabel} (${unitLabel})` : variantLabel;
  const unitPrice = firstSaleUnit?.price ?? basePrice;
  const conversionFactor = firstSaleUnit?.conversion_factor ?? 1;

  const newItem: LineItem = {
    id: crypto.randomUUID(),
    product_variant_id: variantId,
    product_name: productName,
    variant_label: displayLabel,
    sale_unit_id: firstSaleUnit?.id ?? null,
    quantity: 1,
    unit_price: unitPrice,
    conversion_factor: conversionFactor,
    line_total: unitPrice * 1 * conversionFactor,
    stock: variant.stock ?? null,
    minimum_stock_level: variant.minimum_stock_level ?? null,
    sale_units: saleUnits,
    sale_unit: firstSaleUnit
      ? { id: firstSaleUnit.id, name: firstSaleUnit.name, conversion_factor: firstSaleUnit.conversion_factor }
      : null,
  };

  emit("update:modelValue", [...items.value, newItem]);
  selectedEntry.value = null;
}

function updateQuantity(index: number, quantity: number) {
  const updated = [...items.value];
  updated[index] = {
    ...updated[index],
    quantity,
    line_total: quantity * updated[index].unit_price * updated[index].conversion_factor,
  };
  emit("update:modelValue", updated);
}

function updatePrice(index: number, price: number) {
  const updated = [...items.value];
  updated[index] = {
    ...updated[index],
    unit_price: price,
    line_total: updated[index].quantity * price * updated[index].conversion_factor,
  };
  emit("update:modelValue", updated);
}

function updateSaleUnit(index: number, saleUnitId: number) {
  const updated = [...items.value];
  const item = updated[index];
  const saleUnit = item.sale_units?.find((u) => u.id === saleUnitId);
  if (!saleUnit) return;

  updated[index] = {
    ...updated[index],
    sale_unit_id: saleUnit.id,
    unit_price: saleUnit.price,
    conversion_factor: saleUnit.conversion_factor,
    sale_unit: { id: saleUnit.id, name: saleUnit.name, conversion_factor: saleUnit.conversion_factor },
    line_total: item.quantity * saleUnit.price * saleUnit.conversion_factor,
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

const saleUnitOptions = computed(() => {
  return (item: LineItem) => {
    if (!item.sale_units || item.sale_units.length === 0) return [];
    return item.sale_units.map((u) => ({ name: u.name, value: u.id }));
  };
});
</script>

<template>
  <div>
    <div class="flex flex-col gap-2 mb-3">
      <label>{{ t("Add Product") }}</label>
      <AutoComplete
        v-model="selectedEntry"
        :suggestions="searchResults"
        option-label="label"
        :placeholder="t('Search product...')"
        :empty-search-message="t('No results found')"
        :loading="searchLoading"
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
            <span class="col-span-2">{{ t("Brand") }}</span>
            <span class="col-span-2">{{ t("Price") }}</span>
            <span class="col-span-2">{{ t("Stock") }}</span>
            <span class="col-span-2">{{ t("Sale Unit") }}</span>
          </div>
        </template>
        <template #option="{ option }">
          <!-- Desktop: grid row -->
          <div class="hidden lg:grid grid-cols-12 gap-2 items-center w-full py-1">
            <div class="col-span-4 flex flex-col gap-0.5 min-w-0">
              <span class="font-medium text-sm truncate">{{ option.product?.name ?? option.name }}</span>
              <span class="text-sm text-surface-500 truncate">{{ option.identifier }}</span>
            </div>
            <div class="col-span-2 text-sm text-surface-500 truncate">
              {{ option.product?.brand?.name ?? "—" }}
            </div>
            <div class="col-span-2 font-medium text-sm">
              {{ formatCurrency(String(option.price)) }}
            </div>
            <div class="col-span-2">
              <Tag
                :value="getStockLabel(option.stock)"
                :severity="getStockSeverity(option.stock, option.minimum_stock_level)"
                class="text-sm"
                rounded
              />
            </div>
            <div class="col-span-2 flex flex-col gap-0.5 text-sm min-w-0">
              <span v-if="option.sale_units?.length" class="truncate">
                {{ option.sale_units[0].name }}
                <span v-if="option.sale_units[0].conversion_factor !== 1" class="text-surface-500 ml-1">
                  (x{{ option.sale_units[0].conversion_factor }})
                </span>
              </span>
              <span v-else class="text-surface-400">—</span>
            </div>
          </div>
          <!-- Mobile: card layout -->
          <div class="lg:hidden flex flex-col gap-1.5 py-2 w-full">
            <div class="flex items-center justify-between">
              <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                <span class="font-medium text-sm truncate">{{ option.product?.name ?? option.name }}</span>
                <span class="text-xs text-surface-500 truncate">
                  <span v-if="option.product?.brand?.name">{{ option.product.brand.name }} · </span>
                  {{ option.identifier }}
                </span>
              </div>
              <div class="flex flex-col items-end gap-2">
                <span class="font-medium">{{ formatCurrency(String(option.price)) }}</span>
                <Tag
                  :value="getStockLabel(option.stock)"
                  :severity="getStockSeverity(option.stock, option.minimum_stock_level)"
                  class="text-xs"
                />
              </div>
            </div>
            <div v-if="option.sale_units?.length" class="text-xs text-surface-500">
              {{ option.sale_units.map((u: { name: string }) => u.name).join(", ") }}
            </div>
          </div>
        </template>
      </AutoComplete>
    </div>

    <DataTable
      v-model:expanded-rows="expandedRows"
      :value="items"
      data-key="id"
      class="mt-4 border-t-2 border-surface-200 dark:border-surface-700"
      striped-rows
      row-hover
      scrollable
      scroll-direction="both"
    >
      <template #empty>
        <div class="flex flex-col items-center justify-center py-10 text-surface-400">
          <i class="fa fa-cart-plus text-4xl mb-3"></i>
          <span class="font-medium text-lg mb-1">{{ t("No items added yet") }}</span>
          <small>{{ t("Use the search above to add products") }}</small>
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

      <Column :header="t('Sale Unit')" style="min-width: 140px">
        <template #body="{ data, index }">
          <Select
            v-if="data.sale_units && data.sale_units.length > 0"
            :model-value="data.sale_unit_id"
            :options="saleUnitOptions(data)"
            option-label="name"
            option-value="value"
            size="small"
            class="w-full"
            @update:model-value="updateSaleUnit(index, $event)"
          />
          <span v-else class="text-surface-400">—</span>
        </template>
      </Column>

      <Column :header="t('Unit Price')" style="min-width: 150px">
        <template #body="{ data, index }">
          <InputNumber
            :model-value="data.unit_price"
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
          <InputNumber
            :model-value="data.quantity"
            :min="1"
            :max="99999"
            :step="1"
            :min-fraction-digits="0"
            show-buttons
            size="small"
            input-class="tabular-nums w-32"
            @update:model-value="(val: number) => updateQuantity(index, val)"
          />
        </template>
      </Column>

      <Column :header="t('Line Total')" style="min-width: 120px">
        <template #body="{ data }">
          <span class="font-semibold tabular-nums">{{ formatCurrency(String(data.line_total)) }}</span>
        </template>
      </Column>

      <Column style="min-width: 60px; width: 60px">
        <template #body="{ index }">
          <Button v-tooltip.top="t('Delete')" icon="fa fa-trash-can" text rounded @click="confirmRemoveItem(index)" />
        </template>
      </Column>

      <template #expansion="{ data }">
        <div v-if="hasExpandableData(data)" class="px-4 py-3">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div v-if="data.sale_units && data.sale_units.length > 0">
              <span class="text-surface-500 block mb-1">{{ t("Available Sale Units") }}</span>
              <div class="flex flex-col gap-1">
                <div v-for="unit in data.sale_units" :key="unit.id" class="flex items-center gap-2">
                  <span class="font-medium">{{ unit.name }}</span>
                  <span class="text-surface-500 text-xs">
                    {{ formatCurrency(String(unit.price)) }}
                    <span v-if="unit.conversion_factor !== 1" class="ml-1">(x{{ unit.conversion_factor }})</span>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </DataTable>
  </div>
</template>