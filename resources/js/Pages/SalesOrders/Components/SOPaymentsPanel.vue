<script setup lang="ts">
import { Card, Button, Select, InputNumber, InputText, Divider } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { computed } from "vue";
import type { SalesOrderPaymentForm } from "@/Types/sales-order-types";

const props = defineProps<{
  modelValue: SalesOrderPaymentForm[];
  totalAmount: number;
  error?: string;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", payments: SalesOrderPaymentForm[]): void;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const paymentMethodOptions = computed(() => [
  { name: t("Cash"), value: "cash" },
  { name: t("Credit Card"), value: "credit_card" },
  { name: t("QR"), value: "qr" },
  { name: t("Transfer"), value: "transfer" },
]);

const payments = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

const paymentsTotal = computed(() => payments.value.reduce((sum, p) => sum + p.amount, 0));
const paymentsDifference = computed(() => Math.abs(paymentsTotal.value - props.totalAmount));

// Direction-aware mismatch callout: positive shortfall = underpaid ("Short",
// sale can't close balanced — warn); negative = overpaid ("Over", operator
// owes change — not an error). Icon + label + amount = three carriers so
// color is never the sole state indicator (DESIGN.md Status-Pairs Rule).
const paymentsShortfall = computed(() => props.totalAmount - paymentsTotal.value);
const isShortfall = computed(() => paymentsShortfall.value > 0);
const mismatchLabel = computed(() => (isShortfall.value ? t("Short") : t("Over")));
const mismatchAmount = computed(() => formatCurrency(String(Math.abs(paymentsShortfall.value))));
const mismatchIcon = computed(() => (isShortfall.value ? "fa fa-circle-exclamation" : "fa fa-circle-check"));

function updatePaymentMethod(index: number, method: string) {
  const updated = [...payments.value];
  updated[index] = { ...updated[index], payment_method: method as SalesOrderPaymentForm["payment_method"] };
  emit("update:modelValue", updated);
}

function updatePaymentAmount(index: number, amount: number) {
  const updated = [...payments.value];
  updated[index] = { ...updated[index], amount };
  emit("update:modelValue", updated);
}

function updatePaymentReference(index: number, reference: string | null) {
  const updated = [...payments.value];
  updated[index] = { ...updated[index], reference };
  emit("update:modelValue", updated);
}

function addPayment() {
  emit("update:modelValue", [...payments.value, { payment_method: "cash", amount: 0, reference: null }]);
}

function removePayment(index: number) {
  if (payments.value.length <= 1) return;
  const updated = payments.value.filter((_, i) => i !== index);
  emit("update:modelValue", updated);
}
</script>

<template>
  <Card class="mb-4">
    <template #title>
      <div class="flex items-center justify-between">
        <span>{{ t("Payments") }}</span>
        <Button :label="t('Add Payment')" icon="fa fa-plus" size="small" @click="addPayment" />
      </div>
    </template>
    <template #content>
      <div class="flex flex-col gap-3">
        <div v-for="(payment, index) in payments" :key="index" class="flex items-end gap-3">
          <div class="flex-1 flex flex-col gap-1">
            <label :for="`payment-method-${index}`" class="text-sm">{{ t("Payment Method") }}</label>
            <Select
              :id="`payment-method-${index}`"
              :model-value="payment.payment_method"
              :options="paymentMethodOptions"
              option-label="name"
              option-value="value"
              class="w-full"
              @update:model-value="updatePaymentMethod(index, $event)"
            />
          </div>
          <div class="flex flex-col gap-1" style="min-width: 140px">
            <label :for="`payment-amount-${index}`" class="text-sm">{{ t("Amount") }}</label>
            <InputNumber
              :id="`payment-amount-${index}`"
              :model-value="payment.amount"
              :min="0"
              :min-fraction-digits="2"
              class="w-full"
              @update:model-value="updatePaymentAmount(index, $event ?? 0)"
            />
          </div>
          <div class="flex-1 flex flex-col gap-1">
            <label :for="`payment-reference-${index}`" class="text-sm">{{ t("Reference") }}</label>
            <InputText
              :id="`payment-reference-${index}`"
              :model-value="payment.reference"
              :placeholder="t('Optional')"
              class="w-full"
              @update:model-value="updatePaymentReference(index, $event || null)"
            />
          </div>
          <Button
            v-if="payments.length > 1"
            icon="fa fa-trash"
            text
            severity="danger"
            size="small"
            class="mb-1"
            @click="removePayment(index)"
          />
        </div>
        <Divider class="!my-1" />
        <div class="flex justify-between">
          <span class="text-surface-500">{{ t("Payment Total") }}</span>
          <span class="font-medium">{{ formatCurrency(String(paymentsTotal)) }}</span>
        </div>
        <div
          v-if="paymentsDifference > 0.01"
          role="alert"
          class="flex items-center justify-between rounded p-2"
          :class="isShortfall
            ? 'bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400'
            : 'bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-400'"
        >
          <span class="flex items-center gap-2 font-medium">
            <i :class="mismatchIcon" class="text-base" />
            {{ mismatchLabel }}
          </span>
          <span class="font-semibold tabular-nums">{{ mismatchAmount }}</span>
        </div>
        <small v-if="error" class="text-red-500 dark:text-red-400">{{ error }}</small>
      </div>
    </template>
  </Card>
</template>
