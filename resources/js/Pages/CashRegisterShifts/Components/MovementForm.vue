<script setup lang="ts">
import { Dialog, Select, InputNumber, InputText, Button, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string, number } from "yup";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { watch, nextTick, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

const props = defineProps<{
  visible: boolean;
  shiftId: number;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
  (e: "movement-added"): void;
}>();

const {currencyCode} = useCurrencyFormatter();
const toast = useToast();
const { t } = useI18n();

const typeOptions = computed(() => [
  { label: t("Cash In"), value: "cash_in" },
  { label: t("Cash Out"), value: "cash_out" },
]);

const schema = toTypedSchema(
  object({
    type: string().required(t("Movement type is required")),
    amount: number().required(t("Amount is required")).min(0.01, t("Amount must be at least 0.01")),
    reason: string().required(t("Reason is required")).max(255, t("Reason must not exceed 255 characters")),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors, resetForm, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    type: "cash_in",
    amount: undefined as number | undefined,
    reason: "",
  },
});

const [type, typeAttrs] = defineField("type");
const [amount, amountAttrs] = defineField("amount");
const [reason, reasonAttrs] = defineField("reason");

watch(
  () => props.visible,
  async (val) => {
    if (val) {
      resetForm({ values: { type: "cash_in", amount: undefined, reason: "" } });
      await nextTick();
      document.getElementById("movement_type")?.focus();
    }
  },
);

const submit = handleSubmit((values) => {
  const onSuccess = () => {
    emit("update:visible", false);
    toast.add({ severity: "success", summary: t("Success"), detail: t("Movement added successfully"), life: 3000 });
    emit("movement-added");
  };

  const onError = (errs: Record<string, string>) => {
    setErrors(errs);
    nextTick(() => {
      const el = document.querySelector<HTMLInputElement>(".p-invalid");
      el?.focus();
    });
  };

  router.post(route("shifts.movements.store", props.shiftId), values, { onSuccess, onError });
});
</script>

<template>
  <Dialog
    :visible="visible"
    :header="t('Add Movement')"
    modal
    :closable="true"
    :breakpoints="{ '1100px': '50vw', '750px': '75vw', '500px': '90vw' }"
    :style="{ width: '35vw' }"
    @update:visible="emit('update:visible', $event)"
    @keydown.ctrl.enter="submit"
  >
    <div class="flex flex-col gap-4">
      <div class="flex flex-col gap-2">
        <label for="movement_type">
          {{ t("Movement Type") }}
          <span class="text-red-500">*</span>
        </label>
        <Select
          id="movement_type"
          v-model="type"
          v-bind="typeAttrs"
          :options="typeOptions"
          option-label="label"
          option-value="value"
          :empty-message="t('No available options')"
          :class="{ 'p-invalid': submitCount > 0 && !!errors.type }"
        />
        <small v-if="submitCount > 0 && errors.type" class="text-red-400 dark:text-red-300">
          {{ errors.type }}
        </small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="movement_amount">
          {{ t("Amount") }}
          <span class="text-red-500">*</span>
        </label>
        <InputNumber
          id="movement_amount"
          v-model="amount"
          v-bind="amountAttrs"
          mode="currency"
          :currency="currencyCode"
          :min-fraction-digits="2"
          :max-fraction-digits="2"
          :class="{ 'p-invalid': submitCount > 0 && !!errors.amount }"
        />
        <small v-if="submitCount > 0 && errors.amount" class="text-red-400 dark:text-red-300">
          {{ errors.amount }}
        </small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="movement_reason">
          {{ t("Reason") }}
          <span class="text-red-500">*</span>
        </label>
        <InputText
          id="movement_reason"
          v-model="reason"
          v-bind="reasonAttrs"
          autocomplete="off"
          :class="{ 'p-invalid': submitCount > 0 && !!errors.reason }"
        />
        <small v-if="submitCount > 0 && errors.reason" class="text-red-400 dark:text-red-300">
          {{ errors.reason }}
        </small>
      </div>
    </div>
    <template #footer>
      <Button severity="secondary" :label="t('Cancel')" :disabled="isSubmitting" @click="emit('update:visible', false)" />
      <Button severity="primary" :label="t('Save')" :loading="isSubmitting" icon="fa fa-check" @click="submit" />
    </template>
  </Dialog>
</template>