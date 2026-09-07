<script setup lang="ts">
import { Button, Dialog, InputNumber, Select, InputText, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { number, object, string } from "yup";
import { computed, nextTick, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { usePosClient } from "@/Composables/usePosClient";
import { usePosStore } from "@/Composables/usePosStore";

const visible = defineModel<boolean>("visible", { default: false });
const { t } = useI18n();
const toast = useToast();
const { currencyCode } = useCurrencyFormatter();
const posClient = usePosClient();
const posStore = usePosStore();
const typeOptions = computed(() => [
  { label: t("Cash In"), value: "cash_in" },
  { label: t("Cash Out"), value: "cash_out" },
]);
const schema = toTypedSchema(
  object({ type: string().required(), amount: number().required().min(0.01), reason: string().required().max(255) }),
);
const { handleSubmit, defineField, errors, isSubmitting, resetForm, setErrors } = useForm({
  validationSchema: schema,
  initialValues: { type: "cash_in", amount: undefined, reason: "" },
});
const [type, typeAttrs] = defineField("type");
const [amount, amountAttrs] = defineField("amount");
const [reason, reasonAttrs] = defineField("reason");

watch(visible, (isVisible) => {
  if (!isVisible) return;
  resetForm({ values: { type: "cash_in", amount: undefined, reason: "" } });
  nextTick(() => document.getElementById("pos-movement-type")?.focus());
});

const submit = handleSubmit(async (values) => {
  try {
    const session = await posClient.addMovement({
      type: values.type as "cash_in" | "cash_out",
      amount: values.amount,
      reason: values.reason,
    });
    if (session.shift) posStore.setShift(session.shift);
    visible.value = false;
    toast.add({ severity: "success", summary: t("Success"), detail: t("Movement added successfully"), life: 3000, group: "pos" });
  } catch (error) {
    if (typeof error === "object" && error !== null && "response" in error) {
      const response = error.response as { data?: { errors?: Record<string, string> } } | undefined;
      setErrors(response?.data?.errors ?? {});
    }
    toast.add({ severity: "error", summary: t("Error"), detail: t("Unable to save movement"), life: 3000, group: "pos" });
  }
});
</script>

<template>
  <Dialog v-model:visible="visible" modal :header="t('Add Movement')" :style="{ width: '480px' }" :breakpoints="{ '768px': '98vw' }">
    <div class="flex flex-col gap-4">
      <div class="flex flex-col gap-2">
        <label for="pos-movement-type">{{ t("Movement Type") }}</label>
        <Select
          id="pos-movement-type"
          v-model="type"
          v-bind="typeAttrs"
          :options="typeOptions"
          option-label="label"
          option-value="value"
          :class="{ 'p-invalid': errors.type }"
        />
      </div>
      <div class="flex flex-col gap-2">
        <label for="pos-movement-amount">{{ t("Amount") }}</label>
        <InputNumber
          id="pos-movement-amount"
          v-model="amount"
          v-bind="amountAttrs"
          mode="currency"
          :currency="currencyCode"
          :min="0.01"
          :min-fraction-digits="2"
          :max-fraction-digits="2"
          :class="{ 'p-invalid': errors.amount }"
        />
      </div>
      <div class="flex flex-col gap-2">
        <label for="pos-movement-reason">{{ t("Reason") }}</label>
        <InputText id="pos-movement-reason" v-model="reason" v-bind="reasonAttrs" :class="{ 'p-invalid': errors.reason }" />
      </div>
    </div>
    <template #footer>
      <Button severity="secondary" :label="t('Cancel')" @click="visible = false" />
      <Button :label="t('Save')" :loading="isSubmitting" @click="submit" />
    </template>
  </Dialog>
</template>
