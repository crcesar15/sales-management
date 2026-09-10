<script setup lang="ts">
import { toTypedSchema } from "@vee-validate/yup";
import { Button, Checkbox, InputNumber, InputText, Message } from "primevue";
import { computed, watch } from "vue";
import { useForm } from "vee-validate";
import { useI18n } from "vue-i18n";
import { boolean, number, object, string } from "yup";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import type { PosPaymentMode, PosPaymentPayload } from "@/Types/pos";
import PosPaymentMethodSelector from "./PosPaymentMethodSelector.vue";

const props = defineProps<{
  total: number;
  processing: boolean;
  disabled?: boolean;
  serverError?: string;
}>();

const emit = defineEmits<{
  submit: [payload: PosPaymentPayload];
}>();

const { t } = useI18n();
const { currencyCode, formatCurrency } = useCurrencyFormatter();
const schema = toTypedSchema(
  object({
    payment_mode: string().required().oneOf(["cash", "qr", "split"]),
    cash_received: number().nullable().min(0),
    cash_amount: number().nullable().min(0),
    qr_reference: string().nullable().max(255),
    qr_confirmed: boolean().required(),
  }),
);

const { defineField, errors, handleSubmit, setFieldError, setFieldValue } = useForm({
  validationSchema: schema,
  initialValues: {
    payment_mode: "cash",
    cash_received: props.total,
    cash_amount: null,
    qr_reference: null,
    qr_confirmed: false,
  },
});

const [paymentModeField] = defineField("payment_mode");
const [cashReceived, cashReceivedAttrs] = defineField("cash_received");
const [cashAmount, cashAmountAttrs] = defineField("cash_amount");
const [qrReference, qrReferenceAttrs] = defineField("qr_reference");
const [qrConfirmed, qrConfirmedAttrs] = defineField("qr_confirmed");

const paymentMode = computed<PosPaymentMode>({
  get: () => (paymentModeField.value as PosPaymentMode | undefined) ?? "cash",
  set: (value) => {
    paymentModeField.value = value;
  },
});

const changeDue = computed(() => Math.max(0, Number(cashReceived.value ?? 0) - props.total));
const qrAmount = computed(() => Math.max(0, props.total - Number(cashAmount.value ?? 0)));
const maximumSplitCash = computed(() => Math.max(0, props.total - 0.01));

watch(paymentMode, (mode) => {
  setFieldValue("qr_confirmed", false);
  if (mode === "cash") {
    setFieldValue("cash_received", props.total);
  } else if (mode === "qr") {
    setFieldValue("cash_amount", null);
  } else {
    setFieldValue("cash_amount", Math.floor((props.total / 2) * 100) / 100);
  }
});

watch(cashAmount, () => {
  if (paymentMode.value === "split") setFieldValue("qr_confirmed", false);
});

function applyExactCash(): void {
  setFieldValue("cash_received", props.total);
}

function applyHalfCash(): void {
  setFieldValue("cash_amount", Math.floor((props.total / 2) * 100) / 100);
}

const submit = handleSubmit((values) => {
  const mode = values.payment_mode as PosPaymentMode;
  const received = Number(values.cash_received ?? 0);
  const splitCash = Number(values.cash_amount ?? 0);

  if (mode === "cash" && received + 0.01 < props.total) {
    setFieldError("cash_received", t("Cash received must cover the total"));
    return;
  }

  if (mode === "split" && (splitCash <= 0 || splitCash >= props.total)) {
    setFieldError("cash_amount", t("Cash amount must be between zero and the total"));
    return;
  }

  if (mode !== "cash" && !values.qr_confirmed) {
    setFieldError("qr_confirmed", t("Confirm that the QR payment was received"));
    return;
  }

  emit("submit", {
    payment_mode: mode,
    cash_received: mode === "cash" ? received : null,
    cash_amount: mode === "split" ? splitCash : null,
    qr_reference: mode === "cash" ? null : values.qr_reference || null,
    qr_confirmed: mode !== "cash" && Boolean(values.qr_confirmed),
  });
});
</script>

<template>
  <form class="flex flex-col gap-6" @submit.prevent="submit">
    <PosPaymentMethodSelector v-model="paymentMode" />

    <Message v-if="serverError" severity="error" :closable="false">{{ serverError }}</Message>

    <section v-if="paymentMode === 'cash'" class="flex flex-col gap-4" :aria-label="t('Cash payment')">
      <div class="flex items-end gap-3">
        <div class="min-w-0 flex-1">
          <label for="cash-received" class="mb-2 block font-medium">{{ t("Cash received") }}</label>
          <InputNumber
            v-model="cashReceived"
            v-bind="cashReceivedAttrs"
            input-id="cash-received"
            mode="currency"
            :currency="currencyCode"
            :min="0"
            :min-fraction-digits="2"
            :max-fraction-digits="2"
            fluid
            input-class="min-h-14 text-xl tabular-nums"
            :class="{ 'p-invalid': errors.cash_received }"
          />
        </div>
        <Button type="button" :label="t('Exact')" outlined class="min-h-14" @click="applyExactCash" />
      </div>
      <small v-if="errors.cash_received" class="text-red-400 dark:text-red-300">{{ errors.cash_received }}</small>

      <div class="flex items-center justify-between rounded-lg bg-surface-100 p-4 dark:bg-surface-800">
        <span class="font-medium">{{ t("Change due") }}</span>
        <span class="text-2xl font-bold tabular-nums">{{ formatCurrency(String(changeDue)) }}</span>
      </div>
    </section>

    <section v-else class="flex flex-col gap-4" :aria-label="t('QR payment')">
      <div v-if="paymentMode === 'split'" class="grid grid-cols-2 gap-4">
        <div>
          <div class="mb-2 flex items-center justify-between gap-2">
            <label for="cash-amount" class="font-medium">{{ t("Cash amount") }}</label>
            <Button type="button" :label="t('Half')" size="small" text @click="applyHalfCash" />
          </div>
          <InputNumber
            v-model="cashAmount"
            v-bind="cashAmountAttrs"
            input-id="cash-amount"
            mode="currency"
            :currency="currencyCode"
            :min="0.01"
            :max="maximumSplitCash"
            :min-fraction-digits="2"
            :max-fraction-digits="2"
            fluid
            input-class="min-h-14 text-lg tabular-nums"
            :class="{ 'p-invalid': errors.cash_amount }"
          />
          <small v-if="errors.cash_amount" class="mt-1 block text-red-400 dark:text-red-300">{{ errors.cash_amount }}</small>
        </div>
        <div class="rounded-lg bg-surface-100 p-4 dark:bg-surface-800">
          <span class="block text-sm text-surface-500 dark:text-surface-400">{{ t("QR remainder") }}</span>
          <span class="mt-1 block text-2xl font-bold tabular-nums">{{ formatCurrency(String(qrAmount)) }}</span>
        </div>
      </div>

      <div v-else class="flex items-center justify-between rounded-lg bg-surface-100 p-4 dark:bg-surface-800">
        <span class="font-medium">{{ t("QR amount") }}</span>
        <span class="text-2xl font-bold tabular-nums">{{ formatCurrency(String(total)) }}</span>
      </div>

      <div>
        <label for="qr-reference" class="mb-2 block font-medium">{{ t("QR reference") }}</label>
        <InputText
          v-model="qrReference"
          v-bind="qrReferenceAttrs"
          id="qr-reference"
          :placeholder="t('Optional transaction reference')"
          class="min-h-12 w-full"
          :class="{ 'p-invalid': errors.qr_reference }"
        />
        <small v-if="errors.qr_reference" class="mt-1 block text-red-400 dark:text-red-300">{{ errors.qr_reference }}</small>
      </div>

      <div class="flex items-start gap-3 rounded-lg border border-surface-200 p-4 dark:border-surface-700">
        <Checkbox v-model="qrConfirmed" v-bind="qrConfirmedAttrs" input-id="qr-confirmed" binary />
        <div>
          <label for="qr-confirmed" class="cursor-pointer font-medium">{{ t("QR payment received") }}</label>
          <small class="mt-1 block text-surface-500 dark:text-surface-400">{{ t("Confirm only after verifying the payment") }}</small>
          <small v-if="errors.qr_confirmed" class="mt-1 block text-red-400 dark:text-red-300">{{ errors.qr_confirmed }}</small>
        </div>
      </div>
    </section>

    <Button
      type="submit"
      :label="t('Confirm payment and handover')"
      icon="fa fa-check"
      :loading="processing"
      :disabled="processing || disabled"
      raised
      class="min-h-14 w-full uppercase"
    />
  </form>
</template>
