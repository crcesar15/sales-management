<script setup lang="ts">
import { DataTable, Column, Button, InputNumber, Tag, useConfirm } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { ref, computed } from "vue";
import SOProductPicker from "./SOProductPicker.vue";
import type { SalesOrderLineItemForm } from "@/Types/sales-order-types";

export type LineItem = SalesOrderLineItemForm;

const props = defineProps<{
  modelValue: LineItem[];
}>();

const emit = defineEmits<{
  (e: "update:modelValue", items: LineItem[]): void;
}>();

const { t } = useI18n();
const { formatCurrency, currencyCode } = useCurrencyFormatter();
const confirm = useConfirm();

const expandedRows = ref<LineItem[]>([]);

const items = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

const addedKeys = computed(
  () => new Set(items.value.map((i) => `${i.product_variant_id}:${i.sale_unit_id ?? "base"}`)),
);

function availableInSaleUnit(item: LineItem): number | null {
  if (item.stock === null || item.stock === undefined) return null;
  const cf = item.conversion_factor > 0 ? item.conversion_factor : 1;
  return Math.floor(item.stock / cf);
}

function getStockSeverity(
  convertedAvail: number | null | undefined,
  baseStock: number | null | undefined,
  minStock: number | null | undefined,
): "success" | "warn" | "danger" {
  if (convertedAvail === null || convertedAvail === undefined) return "success";
  if (convertedAvail === 0) return "danger";
  if (minStock && baseStock !== null && baseStock !== undefined && baseStock <= minStock) return "warn";
  return "success";
}

function getStockLabel(convertedAvail: number | null | undefined): string {
  if (convertedAvail === null || convertedAvail === undefined) return "—";
  if (convertedAvail === 0) return t("Out of stock");
  return `${t("In stock")}: ${String(convertedAvail)}`;
}

function maxQtyFor(item: LineItem): number {
  const avail = availableInSaleUnit(item);
  return avail === null ? 99999 : Math.max(1, avail);
}

function hasExpandableData(item: LineItem): boolean {
  return !!(item.sale_units && item.sale_units.length > 1);
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
    <SOProductPicker :added-keys="addedKeys" @add="onPickerAdd" />

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
            :value="getStockLabel(availableInSaleUnit(data))"
            :severity="getStockSeverity(availableInSaleUnit(data), data.stock, data.minimum_stock_level)"
            class="text-xs"
            rounded
          />
        </template>
      </Column>

      <Column :header="t('Sale Unit')" style="min-width: 120px">
        <template #body="{ data }">
          <span v-if="data.sale_unit" class="font-medium">
            {{ data.sale_unit.name }}
            <span v-if="data.sale_unit.conversion_factor !== 1" class="text-surface-500 font-normal ml-1">×{{ data.sale_unit.conversion_factor }}</span>
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