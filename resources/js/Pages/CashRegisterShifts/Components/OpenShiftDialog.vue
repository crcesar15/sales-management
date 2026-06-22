<script setup lang="ts">
import { Dialog, Select, InputNumber, InputText, Button, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string } from "yup";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { watch, nextTick, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";

const props = defineProps<{
  visible: boolean;
  registers: Array<{ id: number; name: string; code: string }>;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
  (e: "shift-opened"): void;
}>();

const toast = useToast();
const { t } = useI18n();
const { currencyCode } = useCurrencyFormatter();

const registerOptions = computed(() =>
  props.registers.map((r) => ({ label: r.name, value: r.id })),
);

const schema = toTypedSchema(
  object({
    cash_register_id: number().required(t("Register is required")),
    opening_balance: number().required(t("Opening balance is required")).min(0, t("Opening balance must be at least 0")),
    notes: string().nullable().optional(),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors, resetForm, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    cash_register_id: undefined as number | undefined,
    opening_balance: 0,
    notes: null as string | null,
  },
});

const [cashRegisterId, cashRegisterIdAttrs] = defineField("cash_register_id");
const [openingBalance, openingBalanceAttrs] = defineField("opening_balance");
const [notes, notesAttrs] = defineField("notes");

watch(
  () => props.visible,
  async (val) => {
    if (val) {
      resetForm({ values: { cash_register_id: undefined, opening_balance: 0, notes: null } });
      await nextTick();
      document.getElementById("cash_register_id")?.focus();
    }
  },
);

const submit = handleSubmit((values) => {
  const onSuccess = () => {
    emit("update:visible", false);
    toast.add({ severity: "success", summary: t("Success"), detail: t("Shift opened successfully"), life: 3000 });
    emit("shift-opened");
  };

  const onError = (errs: Record<string, string>) => {
    setErrors(errs);
    nextTick(() => {
      const el = document.querySelector<HTMLInputElement>(".p-invalid");
      el?.focus();
    });
  };

  router.post(route("shifts.open"), values, { onSuccess, onError });
});
</script>

<template>
  <Dialog
    :visible="visible"
    :header="t('Open Shift')"
    modal
    :closable="true"
    :breakpoints="{ '1100px': '50vw', '750px': '75vw', '500px': '90vw' }"
    :style="{ width: '35vw' }"
    @update:visible="emit('update:visible', $event)"
    @keydown.ctrl.enter="submit"
  >
    <div class="flex flex-col gap-4">
      <div class="flex flex-col gap-2">
        <label for="cash_register_id">
          {{ t("Register") }}
          <span class="text-red-500">*</span>
        </label>
        <Select
          id="cash_register_id"
          v-model="cashRegisterId"
          v-bind="cashRegisterIdAttrs"
          :options="registerOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('Select register')"
          :empty-message="t('No available options')"
          :class="{ 'p-invalid': submitCount > 0 && !!errors.cash_register_id }"
        />
        <small v-if="submitCount > 0 && errors.cash_register_id" class="text-red-400 dark:text-red-300">
          {{ errors.cash_register_id }}
        </small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="opening_balance">
          {{ t("Opening Balance") }}
          <span class="text-red-500">*</span>
        </label>
        <InputNumber
          id="opening_balance"
          v-model="openingBalance"
          v-bind="openingBalanceAttrs"
          mode="currency"
          :currency="currencyCode"
          :min-fraction-digits="2"
          :max-fraction-digits="2"
          :class="{ 'p-invalid': submitCount > 0 && !!errors.opening_balance }"
        />
        <small v-if="submitCount > 0 && errors.opening_balance" class="text-red-400 dark:text-red-300">
          {{ errors.opening_balance }}
        </small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="notes">{{ t("Notes") }} ({{ t("Optional") }})</label>
        <InputText
          id="notes"
          v-model="notes"
          v-bind="notesAttrs"
          autocomplete="off"
        />
      </div>
    </div>
    <template #footer>
      <Button severity="secondary" :label="t('Cancel')" :disabled="isSubmitting" @click="emit('update:visible', false)" />
      <Button severity="primary" :label="t('Open Shift')" :loading="isSubmitting" icon="fa fa-lock-open" @click="submit" />
    </template>
  </Dialog>
</template>