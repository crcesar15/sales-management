<script setup lang="ts">
import { Badge, Button, InputNumber, Tag, useToast, useConfirm } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { ref, computed, watch } from "vue";
import type { VendorCatalogEntry } from "@/Types/catalog-types";
import POVariantVendorsDialog from "./POVariantVendorsDialog.vue";
import POProductSearch from "./POProductSearch.vue";

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
  base_unit?: { id: number; name: string; abbreviation?: string } | null;
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
const vendorsDialogVisible = ref(false);
const vendorsDialogVariantId = ref<number | null>(null);
const vendorsDialogProductName = ref("");
const vendorsDialogVariantLabel = ref("");
const expandedItemDetails = ref<string[]>([]);

function openVendorsDialog(item: LineItem) {
  vendorsDialogVariantId.value = item.product_variant_id;
  vendorsDialogProductName.value = item.product_name;
  vendorsDialogVariantLabel.value = item.variant_label;
  vendorsDialogVisible.value = true;
}

function isItemDetailsExpanded(itemId: string): boolean {
  return expandedItemDetails.value.includes(itemId);
}

function toggleItemDetails(itemId: string) {
  expandedItemDetails.value = isItemDetailsExpanded(itemId)
    ? expandedItemDetails.value.filter((id) => id !== itemId)
    : [...expandedItemDetails.value, itemId];
}

const items = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

function getStockSeverity(stock: number | null | undefined, minStock: number | null | undefined): "success" | "warn" | "danger" | "secondary" {
  if (stock === null || stock === undefined) return "secondary";
  if (stock === 0) return "danger";
  if (minStock && stock <= minStock) return "warn";

  return "success";
}

function stockLabel(item: LineItem): string {
  if (item.stock === null || item.stock === undefined || !item.base_unit) return t("Stock unavailable");

  return `${String(item.stock)} ${item.base_unit.abbreviation ?? item.base_unit.name}`;
}

function purchaseUnitLabel(item: LineItem): string {
  return item.purchase_unit?.name ?? item.base_unit?.name ?? "—";
}

function hasVariantLabel(item: LineItem): boolean {
  return Boolean(item.variant_label) && item.variant_label !== item.product_name;
}

function minimumPurchaseUnits(minimumOrderQuantity: number | null | undefined): number {
  const minimum = Number(minimumOrderQuantity ?? 1);

  return Number.isFinite(minimum) ? Math.max(1, Math.ceil(minimum)) : 1;
}

function conversionLabel(item: LineItem): string | null {
  const baseUnit = item.base_unit?.name;
  const conversionFactor = item.purchase_unit?.conversion_factor ?? 1;

  if (!baseUnit || conversionFactor === 1) return null;

  return `1 ${purchaseUnitLabel(item)} = ${String(conversionFactor)} ${baseUnit}`;
}

function getInputLabel(label: string, item: LineItem): string {
  return `${t(label)}: ${item.product_name}, ${item.variant_label}`;
}

function getRemoveLabel(item: LineItem): string {
  return `${t("Delete")}: ${item.product_name}, ${item.variant_label}`;
}

function onEntrySelect(entry: VendorCatalogEntry) {
  const variant = entry.product_variant;
  if (!variant) return;
  const variantId = variant.id;
  const catalogId = entry.id;

  const exists = items.value.some((i) => i.catalog_id === catalogId);
  if (exists) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Product already added"), life: 3000 });
    return;
  }

  const productName = variant.product?.name ?? variant.name ?? "—";
  const variantLabel = variant.name || variant.identifier || productName;
  const purchaseUnit = entry.purchase_unit;
  const measurementUnit = variant.product?.measurement_unit;
  const price = Number(entry.price);
  const quantity = minimumPurchaseUnits(entry.minimum_order_quantity);

  const newItem: LineItem = {
    id: crypto.randomUUID(),
    catalog_id: catalogId,
    product_variant_id: Number(variantId),
    product_name: productName,
    variant_label: variantLabel,
    quantity,
    price,
    total: quantity * price,
    stock: variant.stock ?? null,
    minimum_stock_level: variant.minimum_stock_level ?? null,
    payment_terms: getPaymentTermsLabel(entry.payment_terms),
    details: entry.details ?? null,
    unit_id: entry.unit_id ?? null,
    purchase_unit: purchaseUnit ?? (measurementUnit ? { id: measurementUnit.id, name: measurementUnit.name, conversion_factor: 1 } : null),
    base_unit: measurementUnit ? { id: measurementUnit.id, name: measurementUnit.name, abbreviation: measurementUnit.abbreviation } : null,
    minimum_order_quantity: entry.minimum_order_quantity ?? null,
    lead_time_days: entry.lead_time_days ?? null,
  };

  emit("update:modelValue", [...items.value, newItem]);
}

function getPaymentTermsLabel(paymentTerms: string | null): string | null {
  if (paymentTerms === null) return null;

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
  const item = items.value[index];
  const updated = items.value.filter((_, i) => i !== index);
  expandedItemDetails.value = expandedItemDetails.value.filter((id) => id !== item.id);

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
    expandedItemDetails.value = [];
    if (items.value.length > 0) {
      emit("update:modelValue", []);
    }
  },
);
</script>

<template>
  <div>
    <POProductSearch :vendor-id="vendorId" @select="onEntrySelect" />

    <div
      v-if="items.length === 0"
      class="mt-4 flex flex-col items-center justify-center border-y border-surface-200 py-10 text-surface-500 dark:border-surface-700 dark:text-surface-400"
    >
      <i class="fa fa-cart-plus mb-3 text-4xl" aria-hidden="true"></i>
      <span class="mb-1 text-lg font-medium">{{ t("No items added yet") }}</span>
      <small v-if="!vendorId">{{ t("Select a vendor first to add products") }}</small>
      <small v-else>{{ t("Use the search above to add products") }}</small>
    </div>

    <div v-else class="mt-4 border-y border-surface-200 dark:border-surface-700 2xl:border">
      <div
        aria-hidden="true"
        class="hidden 2xl:grid 2xl:grid-cols-[minmax(8rem,1.4fr)_minmax(9rem,1.1fr)_minmax(7rem,0.85fr)_minmax(9rem,1fr)_minmax(5rem,0.6fr)_5rem] 2xl:items-center 2xl:gap-3 2xl:border-b 2xl:border-surface-200 2xl:bg-surface-100 2xl:px-3 2xl:py-2 2xl:text-sm 2xl:font-semibold dark:2xl:border-surface-700 dark:2xl:bg-surface-800"
      >
        <span>{{ t("Product") }}</span>
        <span>{{ t("Purchase Unit") }}</span>
        <span>{{ t("Unit Price") }}</span>
        <span>{{ t("Quantity") }}</span>
        <span>{{ t("Line Total") }}</span>
        <span></span>
      </div>

      <ul class="divide-y divide-surface-200 dark:divide-surface-700">
        <li v-for="(item, index) in items" :key="item.id" class="px-1 py-4 xl:px-3 xl:py-3">
          <div
            class="grid grid-cols-2 gap-x-3 gap-y-4 2xl:grid-cols-[minmax(8rem,1.4fr)_minmax(9rem,1.1fr)_minmax(7rem,0.85fr)_minmax(9rem,1fr)_minmax(5rem,0.6fr)_5rem] 2xl:items-start 2xl:gap-3"
          >
            <div class="col-span-2 min-w-0 2xl:col-span-1 2xl:pt-2">
              <div class="min-w-0">
                <span class="block truncate text-base font-semibold text-surface-900 dark:text-surface-50">{{ item.product_name }}</span>
                <Badge v-if="hasVariantLabel(item)" :value="item.variant_label" severity="secondary" class="mt-1 w-fit max-w-full truncate" />
              </div>
            </div>

            <div class="col-span-2 flex min-w-0 flex-col gap-1 2xl:col-span-1 2xl:pt-1">
              <span class="font-medium">{{ purchaseUnitLabel(item) }}</span>
              <span v-if="conversionLabel(item)" class="text-sm text-surface-500 dark:text-surface-400">{{ conversionLabel(item) }}</span>
            </div>

            <div class="col-span-2 min-w-0 min-[360px]:col-span-1 2xl:col-span-1">
              <label :for="`purchase-order-price-${item.id}`" class="mb-1 block text-base font-medium 2xl:sr-only">{{ t("Unit Price") }}</label>
              <InputNumber
                :input-id="`purchase-order-price-${item.id}`"
                :model-value="item.price"
                :aria-label="getInputLabel('Unit Price', item)"
                :min="0.01"
                :min-fraction-digits="2"
                :max-fraction-digits="4"
                mode="currency"
                :currency="currencyCode"
                fluid
                input-class="min-h-[44px] w-full tabular-nums 2xl:!text-sm"
                @update:model-value="(val: number | null) => updatePrice(index, val ?? item.price)"
              />
            </div>

            <div class="col-span-2 min-w-0 min-[360px]:col-span-1 2xl:col-span-1">
              <label :for="`purchase-order-quantity-${item.id}`" class="mb-1 block text-base font-medium 2xl:sr-only">{{ t("Quantity") }}</label>
              <InputNumber
                :input-id="`purchase-order-quantity-${item.id}`"
                :model-value="item.quantity"
                :aria-label="getInputLabel('Quantity', item)"
                :min="minimumPurchaseUnits(item.minimum_order_quantity)"
                :max="99999"
                :step="1"
                :min-fraction-digits="0"
                :max-fraction-digits="0"
                show-buttons
                button-layout="horizontal"
                decrement-button-icon="fa fa-minus"
                increment-button-icon="fa fa-plus"
                decrement-button-class="!min-h-[44px] !min-w-[44px]"
                increment-button-class="!min-h-[44px] !min-w-[44px]"
                fluid
                input-class="min-h-[44px] min-w-0 w-full tabular-nums 2xl:!text-sm"
                @update:model-value="(val: number | null) => updateQuantity(index, val ?? item.quantity)"
              />
            </div>

            <div class="min-w-0 pt-2 2xl:pt-3">
              <span class="block text-base font-medium 2xl:sr-only">{{ t("Line Total") }}</span>
              <span class="block font-semibold tabular-nums">{{ formatCurrency(String(item.total)) }}</span>
            </div>

            <div class="flex items-center justify-end 2xl:pt-1">
              <Button
                v-tooltip.top="t('Details')"
                :aria-controls="`purchase-order-item-details-${item.id}`"
                :aria-expanded="isItemDetailsExpanded(item.id)"
                :aria-label="t('Details')"
                icon="fa fa-circle-info"
                text
                rounded
                size="large"
                @click="toggleItemDetails(item.id)"
              />
              <Button v-tooltip.top="t('View Vendors')" :aria-label="t('View Vendors')" icon="fa fa-store" text rounded size="large" @click="openVendorsDialog(item)" />
              <Button v-tooltip.top="t('Delete')" :aria-label="getRemoveLabel(item)" icon="fa fa-trash-can" text rounded size="large" @click="confirmRemoveItem(index)" />
            </div>
          </div>
          <div v-if="isItemDetailsExpanded(item.id)" :id="`purchase-order-item-details-${item.id}`" class="mt-4 border-t border-surface-200 pt-3 dark:border-surface-700">
            <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm min-[480px]:grid-cols-2 2xl:grid-cols-4">
              <div class="flex items-center justify-between gap-3">
                <dt class="text-surface-500 dark:text-surface-400">{{ t("Current Stock") }}</dt>
                <dd class="m-0"><Tag :value="stockLabel(item)" :severity="getStockSeverity(item.stock, item.minimum_stock_level)" class="text-xs" rounded /></dd>
              </div>
              <div v-if="item.minimum_order_quantity" class="flex items-center justify-between gap-3">
                <dt class="text-surface-500 dark:text-surface-400">{{ t("Min. Order") }}</dt>
                <dd class="m-0 font-medium tabular-nums">{{ minimumPurchaseUnits(item.minimum_order_quantity) }}</dd>
              </div>
              <div v-if="item.lead_time_days" class="flex items-center justify-between gap-3">
                <dt class="text-surface-500 dark:text-surface-400">{{ t("Lead Time") }}</dt>
                <dd class="m-0">{{ item.lead_time_days }} {{ t("days") }}</dd>
              </div>
              <div v-if="item.payment_terms" class="flex items-center justify-between gap-3">
                <dt class="text-surface-500 dark:text-surface-400">{{ t("Payment Terms") }}</dt>
                <dd class="m-0 text-right">{{ item.payment_terms }}</dd>
              </div>
            </dl>
          </div>
        </li>
      </ul>
    </div>

    <POVariantVendorsDialog
      v-model:visible="vendorsDialogVisible"
      :product-variant-id="vendorsDialogVariantId"
      :product-name="vendorsDialogProductName"
      :variant-label="vendorsDialogVariantLabel"
    />
  </div>
</template>
