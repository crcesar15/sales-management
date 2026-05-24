<script setup lang="ts">
import { DataTable, Column, Button, InputNumber, DatePicker, InputText, Tag, useConfirm } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";

export interface ReceptionLineItem {
  id: string;
  purchase_order_item_id: number;
  product_variant_id: number;
  product_name: string;
  variant_label: string;
  quantity: number;
  max_quantity?: number;
  expiry_date: Date | null;
  batch_identifier: string;
  purchase_unit?: { id: number; name: string; conversion_factor: number } | null;
  base_unit?: { id: number; name: string; abbreviation: string } | null;
  stock?: number | null;
  minimum_stock_level?: number | null;
}

const props = defineProps<{
  modelValue: ReceptionLineItem[];
  disabled?: boolean;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", items: ReceptionLineItem[]): void;
}>();

const { t } = useI18n();
const confirm = useConfirm();

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

function updateQuantity(index: number, quantity: number) {
  const updated = [...items.value];
  updated[index] = { ...updated[index], quantity };
  emit("update:modelValue", updated);
}

function updateExpiryDate(index: number, date: Date | null) {
  const updated = [...items.value];
  updated[index] = { ...updated[index], expiry_date: date };
  emit("update:modelValue", updated);
}

function updateBatchIdentifier(index: number, value: string) {
  const updated = [...items.value];
  updated[index] = { ...updated[index], batch_identifier: value };
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

function formatConversion(item: ReceptionLineItem): string {
  console.log(item);
  if (!item.purchase_unit || item.purchase_unit.conversion_factor <= 1) return "";
  const baseName = item.base_unit?.abbreviation ?? item.base_unit?.name ?? t("units");
  return `1 ${item.purchase_unit.name} = ${item.purchase_unit.conversion_factor} ${baseName}`;
}
</script>

<template>
  <DataTable
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
        <i class="fa fa-box-open text-4xl mb-3"></i>
        <span class="font-medium text-lg mb-1">{{ t("No items added yet") }}</span>
        <small>{{ t("Select a purchase order to add items") }}</small>
      </div>
    </template>

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

    <Column :header="t('Quantity')" style="min-width: 140px">
      <template #body="{ data, index }">
        <InputNumber
          :model-value="data.quantity"
          :min="0.01"
          :max="data.max_quantity ?? 99999"
          :step="1"
          :min-fraction-digits="1"
          :max-fraction-digits="2"
          show-buttons
          size="small"
          input-class="tabular-nums w-32"
          :disabled="disabled"
          @update:model-value="(val: number) => updateQuantity(index, val)"
        />
        <small v-if="data.max_quantity != null" class="text-surface-500 block mt-1">{{ t("Max") }}: {{ data.max_quantity }}</small>
      </template>
    </Column>

    <Column :header="t('Expiry Date')" style="min-width: 180px">
      <template #body="{ data, index }">
        <DatePicker
          :model-value="data.expiry_date"
          :placeholder="t('Select date')"
          show-icon
          size="small"
          :disabled="disabled"
          class="w-full"
          @update:model-value="
            (val: Date | Date[] | (Date | null)[] | null | undefined) => updateExpiryDate(index, Array.isArray(val) ? null : (val ?? null))
          "
        />
      </template>
    </Column>

    <Column :header="t('Batch Identifier')" style="min-width: 160px">
      <template #body="{ data, index }">
        <InputText
          :model-value="data.batch_identifier"
          :placeholder="t('Optional')"
          size="small"
          class="w-full"
          :disabled="disabled"
          @update:model-value="(val: string | undefined) => updateBatchIdentifier(index, val ?? '')"
        />
      </template>
    </Column>

    <Column :header="t('Conversion')" style="min-width: 160px">
      <template #body="{ data }">
        <span v-if="formatConversion(data)">{{ formatConversion(data) }}</span>
        <span v-else>{{ data.base_unit?.abbreviation ?? data.base_unit?.name ?? t("units") }}</span>
      </template>
    </Column>

    <Column v-if="!disabled" style="min-width: 80px; width: 80px">
      <template #body="{ index }">
        <Button
          v-tooltip.top="t('Delete')"
          icon="fa fa-trash-can"
          text
          rounded
          severity="danger"
          @click="confirmRemoveItem(index)"
        />
      </template>
    </Column>
  </DataTable>
  <ConfirmDialog />
</template>
