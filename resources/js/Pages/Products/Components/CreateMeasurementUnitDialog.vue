<script setup lang="ts">
import { Button, Dialog, InputText, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string } from "yup";
import axios from "axios";
import { nextTick, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useMeasurementUnitClient } from "@composables/useMeasurementUnitClient";
import type { MeasurementUnitResponse } from "@app-types/measurement-unit-types";

const emit = defineEmits<{
  created: [measurementUnit: MeasurementUnitResponse];
}>();
const showModal = defineModel("show-modal", { type: Boolean, required: true });
const toast = useToast();
const { t } = useI18n();
const { storeMeasurementUnitApi } = useMeasurementUnitClient();

const schema = toTypedSchema(
  object({
    name: string().required(t("Name is required")).max(100, t("Name must be at most 100 characters")),
    abbreviation: string().required(t("Abbreviation is required")).max(10, t("Abbreviation must be at most 10 characters")),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors, resetForm, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: { name: "", abbreviation: "" },
});

const [name, nameAttrs] = defineField("name");
const [abbreviation, abbreviationAttrs] = defineField("abbreviation");

watch(showModal, async (visible) => {
  if (visible) {
    resetForm();
    await nextTick();
    document.getElementById("product-measurement-unit-name")?.focus();
  }
});

const onHide = () => {
  resetForm();
};

const submit = handleSubmit(async (values) => {
  try {
    const { data: measurementUnit } = await storeMeasurementUnitApi(values);
    emit("created", measurementUnit);
    showModal.value = false;
    toast.add({ severity: "success", summary: t("Success"), detail: t("Measurement Unit created successfully"), life: 3000 });
  } catch (error) {
    if (axios.isAxiosError<{ errors?: Record<string, string[]> }>(error)) {
      const apiErrors = error.response?.data.errors ?? {};
      setErrors(Object.fromEntries(Object.entries(apiErrors).map(([field, messages]) => [field, messages[0] ?? ""])));
      await nextTick();
      document.querySelector<HTMLInputElement>(".p-invalid")?.focus();
      return;
    }

    toast.add({ severity: "error", summary: t("Error"), detail: t("An error occurred"), life: 3000 });
  }
});
</script>

<template>
  <Dialog
    v-model:visible="showModal"
    :header="t('Add Measurement Unit')"
    :closable="false"
    :breakpoints="{ '1100px': '60vw', '750px': '75vw', '500px': '90vw' }"
    :style="{ width: '30vw' }"
    modal
    @hide="onHide"
    @keydown.ctrl.enter="submit"
  >
    <div class="flex flex-col gap-2 mb-3">
      <label for="product-measurement-unit-name">
        {{ t("Name") }}
        <span class="text-red-500">*</span>
      </label>
      <InputText
        id="product-measurement-unit-name"
        v-model="name"
        v-bind="nameAttrs"
        autocomplete="off"
        :class="{ 'p-invalid': submitCount > 0 && !!errors.name }"
      />
      <small v-if="submitCount > 0 && errors.name" class="text-red-400 dark:text-red-300">
        {{ errors.name }}
      </small>
    </div>
    <div class="flex flex-col gap-2 mb-3">
      <label for="product-measurement-unit-abbreviation">
        {{ t("Abbreviation") }}
        <span class="text-red-500">*</span>
      </label>
      <InputText
        id="product-measurement-unit-abbreviation"
        v-model="abbreviation"
        v-bind="abbreviationAttrs"
        autocomplete="off"
        :class="{ 'p-invalid': submitCount > 0 && !!errors.abbreviation }"
      />
      <small class="text-surface-500">{{ t("Short form (e.g., kg, ltr, pcs)") }}</small>
      <small v-if="submitCount > 0 && errors.abbreviation" class="text-red-400 dark:text-red-300">
        {{ errors.abbreviation }}
      </small>
    </div>
    <template #footer>
      <Button severity="secondary" :label="t('Cancel')" :disabled="isSubmitting" @click="showModal = false" />
      <Button severity="primary" :label="t('Save')" :loading="isSubmitting" @click="submit" />
    </template>
  </Dialog>
</template>
