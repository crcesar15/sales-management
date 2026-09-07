<script setup lang="ts">
import { Button, Dialog, InputNumber, Tag, Textarea, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { number, object, string } from "yup";
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { usePosClient } from "@/Composables/usePosClient";
import type { PosCloseShiftResponse, ShiftReconciliation } from "@/Types/pos";

const emit = defineEmits<{
  (e: "completed"): void;
}>();

const visible = defineModel<boolean>("visible", { default: false });
const { t } = useI18n();
const toast = useToast();
const { currencyCode, formatCurrencySymbol } = useCurrencyFormatter();
const posClient = usePosClient();
const step = ref<"count" | "review" | "complete">("count");
const summary = ref<ShiftReconciliation | null>(null);
const closedShift = ref<PosCloseShiftResponse | null>(null);

const schema = toTypedSchema(
  object({
    closing_balance: number().required(t("Closing balance is required")).min(0, t("Closing balance must be at least 0")),
    closing_notes: string().nullable().optional(),
    discrepancy_reason: string().nullable().optional(),
  }),
);

const { handleSubmit, defineField, errors, isSubmitting, resetForm, setErrors, values } = useForm({
  validationSchema: schema,
  initialValues: { closing_balance: undefined as number | undefined, closing_notes: null, discrepancy_reason: null },
});

const [closingBalance, closingBalanceAttrs] = defineField("closing_balance");
const [closingNotes, closingNotesAttrs] = defineField("closing_notes");
const [discrepancyReason, discrepancyReasonAttrs] = defineField("discrepancy_reason");

const difference = computed(() => {
  if (!summary.value || values.closing_balance === undefined) return null;
  return Math.round((Number(values.closing_balance) - Number(summary.value.expected_closing)) * 100) / 100;
});
const hasDiscrepancy = computed(() => difference.value !== null && difference.value !== 0);
const discrepancyLabel = computed(() => (difference.value !== null && difference.value > 0 ? t("Overage") : t("Shortage")));
const differenceSeverity = computed(() =>
  difference.value === 0 ? "success" : difference.value && difference.value > 0 ? "warn" : "danger",
);

watch(visible, (isVisible) => {
  if (!isVisible) return;
  step.value = "count";
  summary.value = null;
  closedShift.value = null;
  resetForm({ values: { closing_balance: undefined, closing_notes: null, discrepancy_reason: null } });
});

const review = handleSubmit(async () => {
  try {
    summary.value = await posClient.getClosingSummary();
    step.value = "review";
  } catch (error) {
    toast.add({ severity: "error", summary: t("Error"), detail: messageFor(error), life: 3000, group: "pos" });
  }
});

async function confirmClose(): Promise<void> {
  if (hasDiscrepancy.value && !values.discrepancy_reason?.trim()) {
    setErrors({ discrepancy_reason: t("A discrepancy reason is required") });
    return;
  }

  try {
    closedShift.value = await posClient.closeShift({
      closing_balance: Number(values.closing_balance),
      closing_notes: values.closing_notes,
      discrepancy_reason: values.discrepancy_reason,
    });
    step.value = "complete";
  } catch (error) {
    toast.add({ severity: "error", summary: t("Error"), detail: messageFor(error), life: 3000, group: "pos" });
  }
}

function messageFor(error: unknown): string {
  if (typeof error === "object" && error !== null && "response" in error) {
    const response = error.response as { data?: { message?: string } } | undefined;
    return response?.data?.message ?? t("Unable to close shift");
  }

  return error instanceof Error ? error.message : t("Unable to close shift");
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    :closable="step !== 'complete'"
    :header="t('Close Shift')"
    :style="{ width: '560px' }"
    :breakpoints="{ '768px': '98vw' }"
  >
    <div v-if="step === 'count'" class="flex flex-col gap-5">
      <p class="m-0 text-surface-600 dark:text-surface-300">
        {{ t("Count the cash in the register before reviewing the expected total.") }}
      </p>
      <div class="flex flex-col gap-2">
        <label for="pos-closing-balance" class="font-medium">{{ t("Counted Cash") }}</label>
        <InputNumber
          id="pos-closing-balance"
          v-model="closingBalance"
          v-bind="closingBalanceAttrs"
          mode="currency"
          :currency="currencyCode"
          :min="0"
          :min-fraction-digits="2"
          :max-fraction-digits="2"
          class="w-full"
          input-class="w-full"
          :class="{ 'p-invalid': errors.closing_balance }"
        />
        <small v-if="errors.closing_balance" class="text-red-400 dark:text-red-300">{{ errors.closing_balance }}</small>
      </div>
      <div class="flex flex-col gap-2">
        <label for="pos-closing-notes" class="font-medium">{{ t("Closing Notes") }} ({{ t("Optional") }})</label>
        <Textarea id="pos-closing-notes" v-model="closingNotes" v-bind="closingNotesAttrs" rows="3" />
      </div>
    </div>

    <div v-else-if="step === 'review' && summary" class="flex flex-col gap-5">
      <div class="grid grid-cols-2 gap-x-6 gap-y-3 border-y border-surface-200 py-4 dark:border-surface-700">
        <span class="text-surface-500">{{ t("Opening Balance") }}</span>
        <strong>{{ formatCurrencySymbol(String(summary.opening_balance)) }}</strong>
        <span class="text-surface-500">{{ t("Cash Sales") }} ({{ summary.cash_sales_count }})</span>
        <strong>{{ formatCurrencySymbol(String(summary.cash_sales)) }}</strong>
        <span class="text-surface-500">{{ t("Cash In") }}</span>
        <strong>{{ formatCurrencySymbol(String(summary.cash_in)) }}</strong>
        <span class="text-surface-500">{{ t("Cash Out") }}</span>
        <strong>-{{ formatCurrencySymbol(String(summary.cash_out)) }}</strong>
        <span class="border-t border-surface-200 pt-3 font-medium dark:border-surface-700">{{ t("Expected Closing") }}</span>
        <strong class="border-t border-surface-200 pt-3 dark:border-surface-700">
          {{ formatCurrencySymbol(String(summary.expected_closing)) }}
        </strong>
        <span class="font-medium">{{ t("Counted Cash") }}</span>
        <strong>{{ formatCurrencySymbol(String(closingBalance)) }}</strong>
      </div>
      <div class="flex items-center justify-between gap-4 rounded-md bg-surface-100 p-4 dark:bg-surface-800">
        <span class="font-medium">{{ t("Difference") }}</span>
        <Tag :severity="differenceSeverity" :value="`${discrepancyLabel}: ${formatCurrencySymbol(String(Math.abs(difference ?? 0)))}`" />
      </div>
      <div v-if="hasDiscrepancy" class="flex flex-col gap-2">
        <label for="pos-discrepancy-reason" class="font-medium">
          {{ t("Discrepancy Reason") }}
          <span class="text-red-500">*</span>
        </label>
        <Textarea
          id="pos-discrepancy-reason"
          v-model="discrepancyReason"
          v-bind="discrepancyReasonAttrs"
          rows="3"
          :class="{ 'p-invalid': errors.discrepancy_reason }"
        />
        <small v-if="errors.discrepancy_reason" class="text-red-400 dark:text-red-300">{{ errors.discrepancy_reason }}</small>
      </div>
    </div>

    <div v-else-if="closedShift" class="flex flex-col gap-5">
      <div class="flex items-center gap-3">
        <i class="fa fa-circle-check text-2xl text-green-600 dark:text-green-400" aria-hidden="true" />
        <div>
          <h3 class="m-0 text-lg">{{ t("Shift closed successfully") }}</h3>
          <p class="m-0 text-surface-500">{{ t("Final close summary") }}</p>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-y-3 border-y border-surface-200 py-4 dark:border-surface-700">
        <span class="text-surface-500">{{ t("Expected Closing") }}</span>
        <strong>{{ formatCurrencySymbol(String(closedShift.summary.expected_closing)) }}</strong>
        <span class="text-surface-500">{{ t("Counted Cash") }}</span>
        <strong>{{ formatCurrencySymbol(String(closedShift.summary.counted_cash)) }}</strong>
        <span class="text-surface-500">{{ t("Difference") }}</span>
        <strong>{{ formatCurrencySymbol(String(closedShift.summary.difference)) }}</strong>
      </div>
    </div>

    <template #footer>
      <Button v-if="step === 'count'" severity="secondary" :label="t('Cancel')" @click="visible = false" />
      <Button v-if="step === 'count'" :label="t('Review Closing')" :loading="isSubmitting" @click="review" />
      <Button v-if="step === 'review'" severity="secondary" :label="t('Back')" @click="step = 'count'" />
      <Button v-if="step === 'review'" severity="danger" :label="t('Confirm Close')" @click="confirmClose" />
      <Button v-if="step === 'complete'" :label="t('Finish')" @click="emit('completed')" />
    </template>
  </Dialog>
</template>
