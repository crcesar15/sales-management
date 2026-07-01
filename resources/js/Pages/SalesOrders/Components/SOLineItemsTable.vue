<script setup lang="ts">
import { DataTable, Column, Button, InputNumber, Tag, useConfirm } from "primevue";
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
   * line. Used for a line's OWN Available Tag and quantity max so the ceiling
   * is stable as the user edits that line (only sibling lines move it).
   */
  getRemainingBaseExcludingLine?: (variantId: number, lineId: string) => number | null;
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
function remainingBaseForItem(item: LineItem): number | null {
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
  const base = remainingBaseForItem(item);
  if (base === null || base === undefined) return null;
  const cf = item.conversion_factor > 0 ? item.conversion_factor : 1;
  return Math.floor(base / cf);
}

/**
 * One severity rule, unit-aware, applied identically here and in the picker.
 * danger = remaining (in sale unit) <= 0
 * warn   = remaining (in sale unit) <= ceil(minStockBase / cf)
 * success otherwise
 */
function getStockSeverity(item: LineItem): "success" | "warn" | "danger" {
  const avail = availableInSaleUnit(item);
  if (avail === null || avail === undefined) return "success";
  if (avail <= 0) return "danger";
  const minBase = item.minimum_stock_level;
  if (minBase !== null && minBase !== undefined && minBase > 0) {
    const cf = item.conversion_factor > 0 ? item.conversion_factor : 1;
    const minInUnit = Math.ceil(minBase / cf);
    if (avail <= minInUnit) return "warn";
  }
  return "success";
}

function getStockLabel(item: LineItem): string {
  const avail = availableInSaleUnit(item);
  if (avail === null || avail === undefined) return "—";
  if (avail === 0) return t("Out of stock");
  return `${t("Available")}: ${String(avail)}`;
}

function maxQtyFor(item: LineItem): number {
  const avail = availableInSaleUnit(item);
  return avail === null ? 99999 : Math.max(1, avail);
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
</script>

<template>
  <div>
    <SOProductPicker :added-keys="addedKeys" :get-remaining-base="getRemainingBase" @add="onPickerAdd" />

    <DataTable
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

      <Column :header="t('Product')" style="min-width: 180px">
        <template #body="{ data }">
          <span class="font-medium">{{ data.product_name }}</span>
          <div class="text-sm text-surface-500">{{ data.variant_label }}</div>
        </template>
      </Column>

      <Column :header="t('Available')" style="min-width: 120px">
        <template #body="{ data }">
          <Tag :value="getStockLabel(data)" :severity="getStockSeverity(data)" class="text-xs" rounded />
        </template>
      </Column>

      <Column :header="t('Sale Unit')" style="min-width: 120px">
        <template #body="{ data }">
          <span v-if="data.sale_unit" class="font-medium">
            {{ data.sale_unit.name }}
            <span v-if="data.sale_unit.conversion_factor !== 1" class="text-surface-500 font-normal ml-1">
              ×{{ data.sale_unit.conversion_factor }}
            </span>
          </span>
          <span v-else class="text-surface-500">{{ t("Unit") }}</span>
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
            :max="maxQtyFor(data)"
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
    </DataTable>
  </div>
</template>
