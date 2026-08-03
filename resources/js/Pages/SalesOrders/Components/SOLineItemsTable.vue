<script setup lang="ts">
import { Button, InputNumber, Tag, useConfirm, ConfirmDialog, Badge } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { computed } from "vue";
import SOProductPicker from "./SOProductPicker.vue";
import type { SalesOrderLineItemForm } from "@/Types/sales-order-types";

export type LineItem = SalesOrderLineItemForm;

const props = defineProps<{
  modelValue: LineItem[];
  /**
   * Live remaining base stock for a variant, after subtracting base units
   * already allocated across ALL line items. Returns null when unknown
   * (e.g. Edit page pre-populated items without a fresh stock snapshot).
   * Passed through to the picker so unadded rows see the full pool.
   */
  getRemainingBase?: (variantId: number) => number | null;
  /**
   * Live remaining base stock for a variant, EXCLUDING the allocation of one
   * line. Used for a line's quantity max so the ceiling is stable as the user
   * edits that line (only sibling lines move it).
   */
  getRemainingBaseExcludingLine?: (variantId: number, lineId: string) => number | null;
  /**
   * Store to scope product search and stock to. When null, the picker is
   * disabled (no store chosen yet).
   */
  storeId?: number | null;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", items: LineItem[]): void;
}>();

const { t } = useI18n();
const { formatCurrency, currencyCode } = useCurrencyFormatter();
const confirm = useConfirm();

const items = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

const addedKeys = computed(() => new Set(items.value.map((i) => `${i.product_variant_id}:${i.sale_unit_id ?? "base"}`)));

/**
 * Remaining base stock for a line's variant, excluding THIS line's own
 * allocation — the stable ceiling for the line, unaffected by its own qty.
 * Falls back to the all-lines resolver, then to the static snapshot.
 */
function quantityCeilingBaseForItem(item: LineItem): number | null {
  if (props.getRemainingBaseExcludingLine) {
    const live = props.getRemainingBaseExcludingLine(item.product_variant_id, item.id);
    if (live !== null) return live;
  }
  if (props.getRemainingBase) {
    const live = props.getRemainingBase(item.product_variant_id);
    if (live !== null) return live;
  }
  return item.stock ?? null;
}

/** Live available in the line's sale unit = floor(remainingBase / cf). */
function availableInSaleUnit(item: LineItem): number | null {
  const base = quantityCeilingBaseForItem(item);
  if (base === null || base === undefined) return null;
  const cf = item.conversion_factor > 0 ? item.conversion_factor : 1;
  return Math.floor(base / cf);
}

function maxQtyFor(item: LineItem): number {
  const avail = availableInSaleUnit(item);
  return avail === null ? 99999 : Math.max(1, avail);
}

/** Base stock remaining after every line for this variant is allocated. */
function remainingBaseAfterOrder(item: LineItem): number | null {
  const live = props.getRemainingBase?.(item.product_variant_id);
  if (live !== null && live !== undefined) return live;
  if (item.stock === null || item.stock === undefined) return null;

  const cf = item.conversion_factor > 0 ? item.conversion_factor : 1;
  return item.stock - item.quantity * cf;
}

function remainingInSaleUnit(item: LineItem): number | null {
  const remainingBase = remainingBaseAfterOrder(item);
  if (remainingBase === null) return null;

  return Math.floor(remainingBase / (item.conversion_factor > 0 ? item.conversion_factor : 1));
}

function stockStatus(item: LineItem): null | { label: string; severity: "warn" | "danger" } {
  const remainingBase = remainingBaseAfterOrder(item);
  if (remainingBase === null) return null;
  if (remainingBase <= 0) return { label: t("Out of stock"), severity: "danger" };
  if (item.minimum_stock_level !== null && item.minimum_stock_level !== undefined && remainingBase <= item.minimum_stock_level) {
    return { label: t("Low Stock"), severity: "warn" };
  }

  return null;
}

function saleUnitName(item: LineItem): string {
  return item.sale_unit?.name ?? item.base_unit_name ?? "—";
}

function onPickerAdd(item: LineItem) {
  emit("update:modelValue", [...items.value, item]);
}

function updateQuantity(index: number, quantity: number) {
  const updated = [...items.value];
  updated[index] = {
    ...updated[index],
    quantity,
    line_total: quantity * updated[index].unit_price,
  };
  emit("update:modelValue", updated);
}

function updatePrice(index: number, price: number) {
  const updated = [...items.value];
  updated[index] = {
    ...updated[index],
    unit_price: price,
    line_total: updated[index].quantity * price,
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

function getInputLabel(label: string, item: LineItem): string {
  return `${t(label)}: ${item.product_name}, ${item.variant_label}`;
}

function getRemoveLabel(item: LineItem): string {
  return `${t("Delete")}: ${item.product_name}, ${item.variant_label}`;
}

function hasPriceOverride(item: LineItem): boolean {
  return Math.abs(item.unit_price - item.original_unit_price) > 0.00001;
}
</script>

<template>
  <div>
    <ConfirmDialog />
    <SOProductPicker :added-keys="addedKeys" :get-remaining-base="getRemainingBase" :store-id="storeId" @add="onPickerAdd" />

    <div
      v-if="items.length === 0"
      class="mt-4 flex flex-col items-center justify-center border-t border-surface-200 py-10 text-surface-500 dark:border-surface-700 dark:text-surface-400"
    >
      <i class="fa fa-cart-plus mb-3 text-4xl" aria-hidden="true"></i>
      <span class="mb-1 text-lg font-medium">{{ t("No items added yet") }}</span>
      <small>{{ t("Use the search above to add products") }}</small>
    </div>

    <div v-else class="mt-4 border-y lg:border-x border-x-0  border-surface-200 dark:border-surface-700">
      <div
        aria-hidden="true"
        class="hidden grid-cols-[minmax(6rem,1.2fr)_minmax(5rem,0.75fr)_minmax(6rem,0.85fr)_minmax(6.5rem,0.8fr)_minmax(8.25rem,1fr)_minmax(5.25rem,0.6fr)_5.75rem] gap-3 border-b border-surface-200 bg-surface-50 px-3 py-2 font-medium text-surface-600 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-300 xl:grid"
      >
        <span>{{ t("Product") }}</span>
        <span>{{ t("Brand") }}</span>
        <span>{{ t("Sale Unit") }}</span>
        <span>{{ t("Unit Price") }}</span>
        <span>{{ t("Quantity") }}</span>
        <span>{{ t("Line Total") }}</span>
        <span></span>
      </div>

      <ul class="divide-y divide-surface-200 dark:divide-surface-700">
        <li v-for="(item, index) in items" :key="item.id" class="flex flex-col gap-3 px-1 py-4 xl:px-3 xl:py-3">
          <div
            class="grid grid-cols-2 gap-x-3 gap-y-4 xl:grid-cols-[minmax(6rem,1.2fr)_minmax(5rem,0.75fr)_minmax(6rem,0.85fr)_minmax(6.5rem,0.8fr)_minmax(8.25rem,1fr)_minmax(5.25rem,0.6fr)_5.75rem] xl:items-start xl:gap-3"
          >
            <div class="col-span-2 min-w-0 xl:col-span-1">
              <div class="flex min-w-0 items-start justify-between gap-3 xl:block pt-3">
                <div class="min-w-0">
                  <div class="flex min-w-0 items-center gap-2">
                    <span class="truncate text-[16px] font-semibold text-surface-900 dark:text-surface-50">{{ item.product_name }}</span>
                    <Badge v-if="item.variant_identity" :value="item.variant_identity" severity="primary" class="shrink-0 !font-semibold !text-[14px]" />
                  </div>
                </div>

                <div class="flex shrink-0 flex-row items-end gap-1 text-right text-[14px] xl:hidden">
                  <span v-if="item.brand_name" class="pr-2 font-semibold"><i class="fa fa-tag"></i> {{ item.brand_name }}</span>
                  <span class="font-semibold">
                    <i class="fa fa-weight-hanging"></i>
                    {{ saleUnitName(item) }}
                    <span v-if="item.sale_unit && item.sale_unit.conversion_factor !== 1" class="font-normal">
                      ×{{ item.sale_unit.conversion_factor }}
                    </span>
                    <span v-if="item.sale_unit && item.base_unit_name" class="text-surface-600 dark:text-surface-300 pl-1">
                      {{ item.base_unit_name }}
                    </span>
                  </span>
                </div>
              </div>
            </div>

            <div class="hidden min-w-0 xl:block pt-3" >
              <span class="block truncate text-surface-600 dark:text-surface-300">{{ item.brand_name ?? "—" }}</span>
            </div>

            <div class="hidden min-w-0 xl:block pt-3">
              <span class="block truncate font-medium text-surface-800 dark:text-surface-100">{{ saleUnitName(item) }}</span>
              <span v-if="item.sale_unit && item.base_unit_name" class="block truncate text-surface-600 dark:text-surface-300">
                {{ t("Base units") }}: {{ item.base_unit_name }}
              </span>
            </div>

            <div class="col-span-2 min-w-0 min-[360px]:col-span-1 xl:col-span-1">
              <label :for="`so-item-price-${item.id}`" class="mb-1 block text-[16px] font-medium xl:sr-only">
                {{ t("Unit Price") }}
              </label>
              <InputNumber
                :input-id="`so-item-price-${item.id}`"
                :model-value="item.unit_price"
                :aria-label="getInputLabel('Unit Price', item)"
                :min="0.01"
                :min-fraction-digits="2"
                :max-fraction-digits="4"
                mode="currency"
                :currency="currencyCode"
                fluid
                input-class="min-h-[44px] w-full !text-[16px] tabular-nums xl:!text-sm"
                @update:model-value="(val: number) => updatePrice(index, val)"
              />
              <small v-if="hasPriceOverride(item)" class="mt-1 block text-[16px] text-surface-600 dark:text-surface-300">
                {{ t("Original price") }}: {{ formatCurrency(String(item.original_unit_price)) }}
              </small>
            </div>

            <div class="col-span-2 min-w-0 min-[360px]:col-span-1 xl:col-span-1">
              <label :for="`so-item-quantity-${item.id}`" class="mb-1 block text-[16px] font-medium xl:sr-only">
                {{ t("Quantity") }}
              </label>
              <InputNumber
                :input-id="`so-item-quantity-${item.id}`"
                :model-value="item.quantity"
                :aria-label="getInputLabel('Quantity', item)"
                :min="1"
                :max="maxQtyFor(item)"
                :step="1"
                :min-fraction-digits="0"
                show-buttons
                button-layout="horizontal"
                decrement-button-class="!min-h-[44px] !min-w-[44px]"
                increment-button-class="!min-h-[44px] !min-w-[44px]"
                fluid
                input-class="min-h-[44px] min-w-0 w-full !text-[16px] tabular-nums xl:!text-sm"
                @update:model-value="(val: number) => updateQuantity(index, val)"
              />
              <div class="mt-1 flex flex-wrap items-center gap-2 text-[16px] text-surface-600 dark:text-surface-300">
                <span>{{ t("Remaining") }}: {{ remainingInSaleUnit(item) ?? "—" }}</span>
              </div>
            </div>

            <div class="min-w-0 pt-3">
              <span class="block text-[16px] font-medium xl:sr-only">{{ t("Line Total") }}</span>
              <span class="flex items-center text-[16px] tabular-nums xl:min-h-0 xl:text-sm">
                {{ formatCurrency(String(item.line_total)) }}
              </span>
            </div>

            <div class="flex items-center justify-end xl:items-center">
              <Button
                v-tooltip.top="t('Delete')"
                :aria-label="getRemoveLabel(item)"
                icon="fa fa-trash-can"
                text
                size="large"
                @click="confirmRemoveItem(index)"
              />
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
