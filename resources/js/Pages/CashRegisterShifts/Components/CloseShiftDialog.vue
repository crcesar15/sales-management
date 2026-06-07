<script setup lang="ts">
import { Dialog, InputNumber, InputText, Button, Tag, useConfirm, ConfirmDialog, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string } from "yup";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { watch, nextTick, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import type { CashRegisterShiftResponse } from "@/Types/cash-register-types";

const props = defineProps<{
  visible: boolean;
  shift: CashRegisterShiftResponse;
  forceClose?: boolean;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
  (e: "shift-closed"): void;
}>();

const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();
const { formatCurrencySymbol,currencyCode } = useCurrencyFormatter();

const schema = toTypedSchema(
  object({
    closing_balance: number().required(t("Closing balance is required")).min(0, t("Closing balance must be at least 0")),
    notes: string().nullable().optional(),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors, resetForm, submitCount, values } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    closing_balance: 0,
    notes: null as string | null,
  },
});

const [closingBalance, closingBalanceAttrs] = defineField("closing_balance");
const [notes, notesAttrs] = defineField("notes");

const expectedClosing = computed(() => {
  if (props.shift.expected_closing !== null) {
    return Number(props.shift.expected_closing);
  }
  // Mirror backend: opening_balance + cash_in - cash_out
  // Use Number() because Laravel decimal:2 cast serializes values as strings
  const movements = props.shift.movements ?? [];
  const cashIn = movements.filter((m) => m.type === "cash_in").reduce((sum, m) => sum + Number(m.amount), 0);
  const cashOut = movements.filter((m) => m.type === "cash_out").reduce((sum, m) => sum + Number(m.amount), 0);
  return Math.round((Number(props.shift.opening_balance) + cashIn - cashOut) * 100) / 100;
});

const difference = computed(() => {
  const actual = Number(values.closing_balance) || 0;
  return Math.round((actual - expectedClosing.value) * 100) / 100;
});

const differenceSeverity = computed(() => {
  const diff = difference.value;
  if (diff === 0) return "success";
  return diff > 0 ? "warn" : "danger";
});

const differenceLabel = computed(() => {
  const diff = difference.value;
  return (diff >= 0 ? "+" : "") + formatCurrencySymbol(String(diff));
});

watch(
  () => props.visible,
  async (val) => {
    if (val) {
      resetForm({ values: { closing_balance: 0, notes: null } });
    }
  },
);

const submit = handleSubmit((formValues) => {
  if (props.forceClose) {
    confirm.require({
      message: t("Are you sure you want to force close this shift?"),
      header: t("Confirm"),
      icon: "fas fa-exclamation-triangle",
      rejectLabel: t("Cancel"),
      acceptLabel: t("Yes, close shift"),
      rejectClass: "p-button-secondary",
      accept: () => {
        doClose(formValues);
      },
    });
  } else {
    doClose(formValues);
  }
});

function doClose(formValues: Record<string, unknown>) {
  const routeName = props.forceClose ? "shifts.force-close" : "shifts.close";

  const onSuccess = () => {
    emit("update:visible", false);
    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: props.forceClose ? t("Shift force closed successfully") : t("Shift closed successfully"),
      life: 3000,
    });
    emit("shift-closed");
  };

  const onError = (errs: Record<string, string>) => {
    setErrors(errs);
    nextTick(() => {
      const el = document.querySelector<HTMLInputElement>(".p-invalid");
      el?.focus();
    });
  };

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  router.patch(route(routeName, props.shift.id), formValues as any, { onSuccess, onError });
}
</script>

<template>
  <div>
    <ConfirmDialog />
    <Dialog
      :visible="visible"
      :header="forceClose ? t('Force Close Shift') : t('Close Shift')"
      modal
      :closable="true"
      :breakpoints="{ '1100px': '50vw', '750px': '75vw', '500px': '90vw' }"
      :style="{ width: '40vw' }"
      @update:visible="emit('update:visible', $event)"
      @keydown.ctrl.enter="submit"
    >
      <div class="flex flex-col gap-4">
        <!-- Expected vs Actual display -->
        <div class="grid grid-cols-2 gap-4 p-4 bg-surface-50 dark:bg-surface-800 rounded-lg">
          <div>
            <span class="text-sm text-surface-500 block">{{ t("Expected Closing") }}</span>
            <span class="text-lg font-bold">{{ formatCurrencySymbol(String(expectedClosing)) }}</span>
          </div>
          <div>
            <span class="text-sm text-surface-500 block">{{ t("Actual") }}</span>
            <InputNumber
              v-model="closingBalance"
              v-bind="closingBalanceAttrs"
              mode="currency"
              :currency="currencyCode"
              :min-fraction-digits="2"
              :max-fraction-digits="2"
              :class="{ 'p-invalid': submitCount > 0 && !!errors.closing_balance }"
            />
            <small v-if="submitCount > 0 && errors.closing_balance" class="text-red-400 dark:text-red-300 block">
              {{ errors.closing_balance }}
            </small>
          </div>
          <div v-if="closingBalance !== null && closingBalance !== undefined" class="col-span-2 flex items-center gap-2">
            <span class="text-sm text-surface-500">{{ t("Difference") }}:</span>
            <Tag :severity="differenceSeverity" :value="differenceLabel" />
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <label for="close_notes">{{ t("Notes") }} ({{ t("Optional") }})</label>
          <InputText id="close_notes" v-model="notes" v-bind="notesAttrs" autocomplete="off" />
        </div>
      </div>
      <template #footer>
        <Button severity="secondary" :label="t('Cancel')" :disabled="isSubmitting" @click="emit('update:visible', false)" />
        <Button
          :severity="forceClose ? 'danger' : 'primary'"
          :label="forceClose ? t('Force Close Shift') : t('Close Shift')"
          :loading="isSubmitting"
          :icon="forceClose ? 'fa fa-exclamation-triangle' : 'fa fa-lock'"
          @click="submit"
        />
      </template>
    </Dialog>
  </div>
</template>