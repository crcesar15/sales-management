<script setup lang="ts">
import { Badge, Button, ConfirmDialog, DatePicker, InputNumber, InputText, Message, useConfirm } from "primevue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";

export interface ReceptionBatchAllocationForm {
  id: string;
  purchase_unit_quantity: number;
  loose_unit_quantity: number;
  expiry_date: Date | null;
  batch_identifier: string;
}

export interface ReceptionLineItem {
  id: string;
  purchase_order_item_id: number;
  product_variant_id: number;
  product_name: string;
  brand_name: string | null;
  variant_label: string;
  available_quantity: number;
  has_expiration: boolean;
  purchase_unit?: { id: number; name: string; conversion_factor: number } | null;
  base_unit?: { id: number; name: string; abbreviation: string } | null;
  batches: ReceptionBatchAllocationForm[];
}

const props = defineProps<{
  modelValue: ReceptionLineItem[];
  errors?: Record<string, string>;
  showValidation?: boolean;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", items: ReceptionLineItem[]): void;
}>();

const { t } = useI18n();
const confirm = useConfirm();
const { datePickerFormat } = useDatetimeFormatter();
const items = computed({ get: () => props.modelValue, set: (value) => emit("update:modelValue", value) });

function conversionFactor(item: ReceptionLineItem): number {
  return item.purchase_unit?.conversion_factor ?? 1;
}

function purchaseUnitLabel(item: ReceptionLineItem): string {
  return item.purchase_unit?.name ?? item.base_unit?.name ?? "—";
}

function hasVariantLabel(item: ReceptionLineItem): boolean {
  return Boolean(item.variant_label) && item.variant_label !== item.product_name;
}

function allocatedBaseQuantity(item: ReceptionLineItem): number {
  return item.batches.reduce((total, batch) => total + batch.purchase_unit_quantity * conversionFactor(item) + batch.loose_unit_quantity, 0);
}

function allocatedPurchaseQuantity(item: ReceptionLineItem): number {
  return allocatedBaseQuantity(item) / conversionFactor(item);
}

function unallocatedBaseQuantity(item: ReceptionLineItem): number {
  return item.available_quantity * conversionFactor(item) - allocatedBaseQuantity(item);
}

function updateBatch(itemIndex: number, batchIndex: number, updates: Partial<ReceptionBatchAllocationForm>) {
  const updated = [...items.value];
  const item = updated[itemIndex];
  updated[itemIndex] = { ...item, batches: item.batches.map((batch, index) => (index === batchIndex ? { ...batch, ...updates } : batch)) };
  emit("update:modelValue", updated);
}

function duplicateBatch(itemIndex: number, batchIndex: number) {
  const updated = [...items.value];
  const item = updated[itemIndex];
  const batch = item.batches[batchIndex];
  updated[itemIndex] = { ...item, batches: [...item.batches, { ...batch, id: crypto.randomUUID() }] };
  emit("update:modelValue", updated);
}

function removeBatch(itemIndex: number, batchIndex: number) {
  const updated = [...items.value];
  const item = updated[itemIndex];
  if (item.batches.length === 1) return;
  updated[itemIndex] = { ...item, batches: item.batches.filter((_, index) => index !== batchIndex) };
  emit("update:modelValue", updated);
}

function confirmRemoveBatch(itemIndex: number, batchIndex: number) {
  confirm.require({
    message: t("Are you sure you want to remove this batch?"),
    header: t("Confirm"),
    icon: "fa fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    acceptClass: "p-button-primary",
    accept: () => removeBatch(itemIndex, batchIndex),
  });
}

function fieldError(itemIndex: number, batchIndex: number, field: string): string | undefined {
  return props.errors?.[`items.${itemIndex}.batches.${batchIndex}.${field}`];
}

function batchError(itemIndex: number): string | undefined {
  return props.errors?.[`items.${itemIndex}.batches`];
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <div v-for="(item, itemIndex) in items" :key="item.id" class="border border-surface-200 dark:border-surface-700">
      <div class="flex flex-col gap-3 border-b border-surface-200 bg-surface-50 p-4 dark:border-surface-700 dark:bg-surface-800/60 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="font-semibold text-surface-900 dark:text-surface-50">{{ item.product_name }}</span>
            <Badge v-if="hasVariantLabel(item)" :value="item.variant_label" />
          </div>
          <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-surface-500 dark:text-surface-400">
            <span v-if="item.brand_name"> <b>{{ t("Brand") }} :</b> {{ item.brand_name }}</span>
            <span> <b>{{ t("Available to receive") }}:</b> {{ item.available_quantity }} {{ purchaseUnitLabel(item) }}</span>
            <span> <b>{{ t("Allocated") }}:</b> {{ allocatedPurchaseQuantity(item) }} {{ purchaseUnitLabel(item) }}</span>
            <span v-if="conversionFactor(item) > 1"> (1 {{ purchaseUnitLabel(item) }} = {{ conversionFactor(item) }} {{ item.base_unit?.abbreviation }})</span>
          </div>
        </div>
        <span :class="unallocatedBaseQuantity(item) < 0 ? 'text-red-500' : 'text-surface-600 dark:text-surface-300'" class="font-medium tabular-nums">
          {{ t("Unallocated") }}: {{ unallocatedBaseQuantity(item) / conversionFactor(item) }} {{ purchaseUnitLabel(item) }}
        </span>
      </div>

      <Message v-if="unallocatedBaseQuantity(item) < 0" severity="error" class="m-4">
        {{ t("Allocated quantity cannot exceed the available quantity") }}
      </Message>
      <Message v-if="batchError(itemIndex)" severity="error" class="m-4">{{ batchError(itemIndex) }}</Message>

      <div class="flex flex-col divide-y divide-surface-200 dark:divide-surface-700">
        <div v-for="(batch, batchIndex) in item.batches" :key="batch.id" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 xl:grid-cols-[minmax(7rem,.7fr)_minmax(7rem,.7fr)_minmax(10rem,1fr)_minmax(10rem,1fr)_5rem]">
          <div class="flex flex-col gap-1">
            <label :for="`purchase-units-${batch.id}`">{{ purchaseUnitLabel(item) }}</label>
            <InputNumber :input-id="`purchase-units-${batch.id}`" :model-value="batch.purchase_unit_quantity" :min="0" :max-fraction-digits="0" fluid :disabled="disabled" :class="{ 'p-invalid': !!fieldError(itemIndex, batchIndex, 'purchase_unit_quantity') }" @update:model-value="(value) => updateBatch(itemIndex, batchIndex, { purchase_unit_quantity: value ?? 0 })" />
            <small v-if="fieldError(itemIndex, batchIndex, 'purchase_unit_quantity')" class="text-red-400 dark:text-red-300">{{ fieldError(itemIndex, batchIndex, "purchase_unit_quantity") }}</small>
          </div>
          <div v-if="conversionFactor(item) > 1" class="flex flex-col gap-1">
            <label :for="`loose-units-${batch.id}`">{{ t("Loose Units") }} ({{ item.base_unit?.abbreviation }})</label>
            <InputNumber :input-id="`loose-units-${batch.id}`" :model-value="batch.loose_unit_quantity" :min="0" :max="conversionFactor(item) - 1" :max-fraction-digits="0" fluid :disabled="disabled" :class="{ 'p-invalid': !!fieldError(itemIndex, batchIndex, 'loose_unit_quantity') }" @update:model-value="(value) => updateBatch(itemIndex, batchIndex, { loose_unit_quantity: value ?? 0 })" />
            <small v-if="fieldError(itemIndex, batchIndex, 'loose_unit_quantity')" class="text-red-400 dark:text-red-300">{{ fieldError(itemIndex, batchIndex, "loose_unit_quantity") }}</small>
          </div>
          <div v-else class="flex flex-col gap-1">
            <label :for="`loose-units-${batch.id}`">{{ t("Loose Units") }} ({{ item.base_unit?.abbreviation }})</label>
            <span class="mt-2 text-center text-lg font-bold text-surface-500 dark:text-surface-400">--</span>
          </div>
          <div class="flex flex-col gap-1">
            <label :for="`expiry-date-${batch.id}`">{{ t("Expiry Date") }}</label>
            <DatePicker :input-id="`expiry-date-${batch.id}`" :model-value="batch.expiry_date" :date-format="datePickerFormat" :placeholder="t('Select date')" show-icon fluid :disabled="disabled" :class="{ 'p-invalid': !!fieldError(itemIndex, batchIndex, 'expiry_date') || (showValidation && item.has_expiration && !batch.expiry_date) }" @update:model-value="(value) => updateBatch(itemIndex, batchIndex, { expiry_date: Array.isArray(value) ? null : (value ?? null) })" />
            <small v-if="fieldError(itemIndex, batchIndex, 'expiry_date') || (showValidation && item.has_expiration && !batch.expiry_date)" class="text-red-400 dark:text-red-300">{{ fieldError(itemIndex, batchIndex, "expiry_date") ?? t("Required") }}</small>
          </div>
          <div class="flex flex-col gap-1">
            <label :for="`batch-identifier-${batch.id}`">{{ t("Batch Identifier") }}</label>
            <InputText :id="`batch-identifier-${batch.id}`" :model-value="batch.batch_identifier" :placeholder="t('Optional')" fluid :disabled="disabled" :class="{ 'p-invalid': !!fieldError(itemIndex, batchIndex, 'batch_identifier') }" @update:model-value="(value) => updateBatch(itemIndex, batchIndex, { batch_identifier: value ?? '' })" />
            <small v-if="fieldError(itemIndex, batchIndex, 'batch_identifier')" class="text-red-400 dark:text-red-300">{{ fieldError(itemIndex, batchIndex, "batch_identifier") }}</small>
          </div>
          <div class="flex items-end justify-end gap-1">
            <Button v-if="!disabled" v-tooltip.top="t('Duplicate')" icon="fa fa-copy" text rounded size="large" @click="duplicateBatch(itemIndex, batchIndex)" />
            <Button v-if="!disabled && item.batches.length > 1" v-tooltip.top="t('Delete')" icon="fa fa-trash-can" text rounded size="large" @click="confirmRemoveBatch(itemIndex, batchIndex)" />
          </div>
        </div>
      </div>
    </div>
    <ConfirmDialog />
  </div>
</template>
