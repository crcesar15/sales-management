<script setup lang="ts">
import { Card, InputNumber, Textarea, SelectButton, Divider, Message } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { computed } from "vue";
import type { PurchaseDiscountType, PurchaseOrderFinancialPreview } from "@app-types/purchase-order-types";

const props = defineProps<{
  preview: PurchaseOrderFinancialPreview | null;
  loading: boolean;
  failed: boolean;
  notes: string | null | undefined;
  notesAttrs: Record<string, unknown>;
  submitCount: number;
  errors: Record<string, string>;
}>();

const emit = defineEmits<{
  "update:notes": [value: string | null];
}>();
const discountType = defineModel<PurchaseDiscountType>("discountType", { required: true });
const discountValue = defineModel<number | null>("discountValue", { required: true });

const { t } = useI18n();
const { formatCurrency, currencyCode } = useCurrencyFormatter();
const modeOptions = [
  { label: currencyCode, value: "flat" },
  { label: "%", value: "percentage" },
];

const headerError = computed(() => props.errors.discount_value ?? props.errors.discount_type ?? props.errors.discount);
const money = (value: string | undefined) => (value === undefined ? "—" : formatCurrency(value));
function onModeChange(type: PurchaseDiscountType) {
  if (type === discountType.value) return;
  discountType.value = type;
  discountValue.value = 0;
}

function updateNotes(val: string | null) {
  emit("update:notes", val);
}
</script>

<template>
  <Card class="!border !border-surface-200 !shadow-none dark:!border-surface-700">
    <template #title>{{ t("Summary") }}</template>
    <template #content>
      <div class="flex flex-col gap-3">
        <div class="flex justify-between">
          <span class="text-surface-500">{{ t("Gross subtotal") }}</span>
          <span class="shrink-0 whitespace-nowrap font-medium tabular-nums">{{ money(preview?.gross_sub_total) }}</span>
        </div>
        <div class="flex justify-between gap-2">
          <span>{{ t("Item discounts") }}</span>
          <span class="shrink-0 whitespace-nowrap font-medium tabular-nums text-red-500 dark:text-red-400">{{ money(preview?.line_discount_total) }}</span>
        </div>
        <div class="flex justify-between gap-2">
          <span>{{ t("Subtotal") }}</span>
          <span class="shrink-0 whitespace-nowrap font-medium tabular-nums">{{ money(preview?.sub_total) }}</span>
        </div>

        <div class="flex justify-between items-center gap-2">
          <label for="po-order-discount" class="text-surface-500">{{ t("PO discount") }}</label>
          <div class="flex items-center gap-2">
            <SelectButton
              :model-value="discountType"
              :options="modeOptions"
              option-label="label"
              option-value="value"
              :allow-empty="false"
              :aria-label="t('PO discount type')"
              @update:model-value="onModeChange"
            />
            <InputNumber
              input-id="po-order-discount"
              v-model="discountValue"
              :mode="discountType === 'percentage' ? 'decimal' : 'currency'"
              :currency="currencyCode"
              show-clear
              :suffix="discountType === 'percentage' ? '%' : undefined"
              :min="0"
              :max="discountType === 'percentage' ? 100 : undefined"
              :min-fraction-digits="2"
              :max-fraction-digits="2"
              :invalid="!!headerError"
              input-class="w-28 text-right"
            />
          </div>
        </div>
        <small v-if="headerError" class="text-red-400 dark:text-red-300">{{ t(headerError) }}</small>
        <div class="flex justify-between gap-2">
          <span>{{ t("PO discount amount") }}</span>
          <span class="shrink-0 whitespace-nowrap tabular-nums">{{ money(preview?.discount) }}</span>
        </div>
        <small v-if="loading" role="status">{{ t("Calculating PO totals...") }}</small>
        <Message v-if="failed" severity="error" :closable="false">
          {{ t("Unable to calculate PO totals. Review discounts and quantities.") }}
        </Message>

        <Divider class="!my-1" />

        <div class="flex justify-between">
          <span class="font-bold">{{ t("Total") }}</span>
          <span class="font-bold text-lg tabular-nums">{{ money(preview?.total) }}</span>
        </div>

        <div class="flex flex-col gap-1 mt-2">
          <label for="po-notes">{{ t("Notes") }}</label>
          <Textarea
            id="po-notes"
            :model-value="notes"
            v-bind="notesAttrs"
            rows="4"
            :class="{ 'p-invalid': submitCount > 0 && !!errors.notes }"
            @update:model-value="updateNotes($event)"
          />
          <small v-if="submitCount > 0 && errors.notes" class="text-red-400 dark:text-red-300">{{ errors.notes }}</small>
        </div>
      </div>
    </template>
  </Card>
</template>
