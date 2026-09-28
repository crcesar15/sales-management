<script setup lang="ts">
import { Badge, Button, ConfirmDialog, DatePicker, InputNumber, InputText, useConfirm } from "primevue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";

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
  has_expiration?: boolean;
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
const { datePickerFormat } = useDatetimeFormatter();
const items = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

function purchaseUnitLabel(item: ReceptionLineItem): string {
  return item.purchase_unit?.name ?? item.base_unit?.name ?? "—";
}

function hasVariantLabel(item: ReceptionLineItem): boolean {
  return Boolean(item.variant_label) && item.variant_label !== item.product_name;
}

function conversionLabel(item: ReceptionLineItem): string | null {
  const baseUnit = item.base_unit?.abbreviation;
  const conversionFactor = item.purchase_unit?.conversion_factor ?? 1;

  if (!baseUnit || conversionFactor === 1) return null;

  return `1 ${purchaseUnitLabel(item)} = ${String(conversionFactor)} ${baseUnit}`;
}

function getInputLabel(label: string, item: ReceptionLineItem): string {
  return `${t(label)}: ${item.product_name}, ${item.variant_label}`;
}

function getRemoveLabel(item: ReceptionLineItem): string {
  return `${t("Delete")}: ${item.product_name}, ${item.variant_label}`;
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
  emit(
    "update:modelValue",
    items.value.filter((_, itemIndex) => itemIndex !== index),
  );
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
  <div
    v-if="items.length === 0"
    class="mt-4 flex flex-col items-center justify-center border-y border-surface-200 py-10 text-surface-500 dark:border-surface-700 dark:text-surface-400"
  >
    <i class="fa fa-box-open mb-3 text-4xl" aria-hidden="true"></i>
    <span class="mb-1 text-lg font-medium">{{ t("No items added yet") }}</span>
    <small>{{ t("Select a purchase order to add items") }}</small>
  </div>

  <div v-else class="mt-4 2xl:border 2xl:border-surface-200 dark:2xl:border-surface-700">
    <div
      aria-hidden="true"
      class="hidden 2xl:grid 2xl:grid-cols-[minmax(8rem,1.35fr)_minmax(9rem,1.1fr)_minmax(9rem,1fr)_minmax(6rem,0.65fr)_minmax(10rem,1.15fr)_minmax(10rem,1.15fr)_5rem] 2xl:items-center 2xl:gap-3 2xl:border-b 2xl:border-surface-200 2xl:bg-surface-100 2xl:px-3 2xl:py-2 2xl:text-sm 2xl:font-semibold dark:2xl:border-surface-700 dark:2xl:bg-surface-800"
    >
      <span>{{ t("Product") }}</span>
      <span>{{ t("Purchase Unit") }}</span>
      <span>{{ t("Quantity") }}</span>
      <span>{{ t("Remaining") }}</span>
      <span>{{ t("Expiry Date") }}</span>
      <span>{{ t("Batch Identifier") }}</span>
      <span></span>
    </div>

    <ul class="flex flex-col gap-3 2xl:block 2xl:divide-y 2xl:divide-surface-200 dark:2xl:divide-surface-700">
      <li
        v-for="(item, index) in items"
        :key="item.id"
        class="rounded-xl border border-surface-200 bg-surface-50 p-4 dark:border-surface-700 dark:bg-surface-800/60 2xl:rounded-none 2xl:border-0 2xl:bg-transparent 2xl:px-3 2xl:py-3 dark:2xl:bg-transparent"
      >
        <div
          class="grid grid-cols-2 gap-x-3 gap-y-4 2xl:grid-cols-[minmax(8rem,1.35fr)_minmax(9rem,1.1fr)_minmax(9rem,1fr)_minmax(6rem,0.65fr)_minmax(10rem,1.15fr)_minmax(10rem,1.15fr)_5rem] 2xl:items-start 2xl:gap-3"
        >
          <div class="col-span-2 min-w-0 2xl:col-span-1 2xl:pt-2">
            <span class="block truncate text-base font-semibold text-surface-900 dark:text-surface-50">{{ item.product_name }}</span>
            <Badge v-if="hasVariantLabel(item)" :value="item.variant_label" severity="secondary" class="mt-1 w-fit max-w-full truncate" />
          </div>

          <div class="col-span-2 flex min-w-0 flex-col gap-1 2xl:col-span-1 2xl:pt-1">
            <span class="font-medium">{{ purchaseUnitLabel(item) }}</span>
            <span v-if="conversionLabel(item)" class="text-sm text-surface-500 dark:text-surface-400">{{ conversionLabel(item) }}</span>
          </div>

          <div class="col-span-2 min-w-0 2xl:col-span-1">
            <label :for="`reception-order-quantity-${item.id}`" class="mb-1 block text-base font-medium 2xl:sr-only">{{ t("Quantity") }}</label>
            <InputNumber
              :input-id="`reception-order-quantity-${item.id}`"
              :model-value="item.quantity"
              :aria-label="getInputLabel('Quantity', item)"
              :min="0.01"
              :max="item.max_quantity ?? 99999"
              :step="1"
              :min-fraction-digits="1"
              :max-fraction-digits="2"
              show-buttons
              button-layout="horizontal"
              decrement-button-icon="fa fa-minus"
              increment-button-icon="fa fa-plus"
              decrement-button-class="!min-h-[44px] !min-w-[44px]"
              increment-button-class="!min-h-[44px] !min-w-[44px]"
              fluid
              input-class="min-h-[44px] min-w-0 w-full tabular-nums 2xl:!text-sm"
              :disabled="disabled"
              @update:model-value="(val: number | null) => updateQuantity(index, val ?? item.quantity)"
            />
          </div>

          <div class="col-span-2 min-w-0 2xl:col-span-1 2xl:pt-2">
            <span class="mb-1 block text-base font-medium 2xl:sr-only">{{ t("Remaining") }}</span>
            <span class="block font-semibold tabular-nums">{{ item.max_quantity ?? "—" }}</span>
          </div>

          <div class="col-span-2 min-w-0 2xl:col-span-1">
            <label :for="`reception-order-expiry-date-${item.id}`" class="mb-1 block text-base font-medium 2xl:sr-only">{{ t("Expiry Date") }}</label>
            <DatePicker
              :input-id="`reception-order-expiry-date-${item.id}`"
              :model-value="item.expiry_date"
              :aria-label="getInputLabel('Expiry Date', item)"
              :placeholder="t('Select date')"
              :date-format="datePickerFormat"
              show-icon
              fluid
              input-class="min-h-[44px] w-full 2xl:!text-sm"
              :disabled="disabled"
              :class="{ 'p-invalid': item.has_expiration && !item.expiry_date }"
              @update:model-value="
                (val: Date | Date[] | (Date | null)[] | null | undefined) => updateExpiryDate(index, Array.isArray(val) ? null : (val ?? null))
              "
            />
            <small v-if="item.has_expiration && !item.expiry_date" class="mt-1 block text-red-400 dark:text-red-300">{{ t("Required") }}</small>
          </div>

          <div class="col-span-2 min-w-0 2xl:col-span-1">
            <label :for="`reception-order-batch-identifier-${item.id}`" class="mb-1 block text-base font-medium 2xl:sr-only">{{ t("Batch Identifier") }}</label>
            <InputText
              :id="`reception-order-batch-identifier-${item.id}`"
              :model-value="item.batch_identifier"
              :aria-label="getInputLabel('Batch Identifier', item)"
              :placeholder="t('Optional')"
              fluid
              class="min-h-[44px] w-full 2xl:!text-sm"
              :disabled="disabled"
              @update:model-value="(val: string | undefined) => updateBatchIdentifier(index, val ?? '')"
            />
          </div>

          <div class="col-span-2 flex items-start justify-end 2xl:col-span-1">
            <Button
              v-if="!disabled"
              v-tooltip.top="t('Delete')"
              :aria-label="getRemoveLabel(item)"
              icon="fa fa-trash-can"
              text
              rounded
              size="large"
              @click="confirmRemoveItem(index)"
            />
          </div>
        </div>

      </li>
    </ul>
  </div>

  <ConfirmDialog />
</template>
