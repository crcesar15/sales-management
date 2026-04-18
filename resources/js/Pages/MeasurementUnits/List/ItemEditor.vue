<script setup lang="ts">
import { Dialog, InputText, Button, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string } from "yup";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { watch, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import type { MeasurementUnitResponse } from "@/Types/measurement-unit-types";

// Define props
const props = defineProps<{
  measurementUnit: MeasurementUnitResponse | null;
}>();
const toast = useToast();
const { t } = useI18n();

// Define v-model
const showModal = defineModel("show-modal", { type: Boolean, required: true });

// Schema
const schema = toTypedSchema(
  object({
    name: string().required(t("Name is required")).max(100, t("Name must be at most 100 characters")),
    abbreviation: string().required(t("Abbreviation is required")).max(10, t("Abbreviation must be at most 10 characters")),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors, resetForm } = useForm({
  validationSchema: schema,
  initialValues: {
    name: "",
    abbreviation: "",
  },
});

const [name, nameAttrs] = defineField("name");
const [abbreviation, abbreviationAttrs] = defineField("abbreviation");

// Reset form when dialog opens
watch(showModal, async (val) => {
  if (val) {
    resetForm({
      values: {
        name: props.measurementUnit?.name ?? "",
        abbreviation: props.measurementUnit?.abbreviation ?? "",
      },
    });
    await nextTick();
    document.getElementById("name")?.focus();
  }
});

const onHide = () => {
  resetForm();
};

// Submit
const submit = handleSubmit((values) => {
  const onSuccess = () => {
    showModal.value = false;
    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: props.measurementUnit ? t("Measurement Unit updated successfully") : t("Measurement Unit created successfully"),
      life: 3000,
    });
  };

  const onError = (errs: Record<string, string>) => {
    setErrors(errs);
    nextTick(() => {
      const el = document.querySelector<HTMLInputElement>(".p-invalid");
      el?.focus();
    });
  };

  if (props.measurementUnit === null) {
    router.post(route("measurement-units.store"), values, { onSuccess, onError });
  } else {
    router.put(route("measurement-units.update", props.measurementUnit.id), values, { onSuccess, onError });
  }
});
</script>

<template>
  <div>
    <Dialog
      v-model:visible="showModal"
      :header="t('Measurement Unit')"
      :breakpoints="{ '1100px': '60vw', '750px': '75vw', '500px': '90vw' }"
      :style="{ width: '30vw' }"
      modal
      @hide="onHide"
    >
      <div class="flex flex-col gap-2 mb-3">
        <label for="name">
          {{ t("Name") }}
          <span class="text-red-500">*</span>
        </label>
        <InputText id="name" v-model="name" v-bind="nameAttrs" autocomplete="off" :class="{ 'p-invalid': errors.name }" />
        <small v-if="errors.name" class="text-red-400 dark:text-red-300">
          {{ errors.name }}
        </small>
      </div>
      <div class="flex flex-col gap-2 mb-3">
        <label for="abbreviation">
          {{ t("Abbreviation") }}
          <span class="text-red-500">*</span>
        </label>
        <InputText
          id="abbreviation"
          v-model="abbreviation"
          v-bind="abbreviationAttrs"
          autocomplete="off"
          :class="{ 'p-invalid': errors.abbreviation }"
        />
        <small class="text-surface-500">{{ t("Short form (e.g., kg, ltr, pcs)") }}</small>
        <small v-if="errors.abbreviation" class="text-red-400 dark:text-red-300">
          {{ errors.abbreviation }}
        </small>
      </div>
      <template #footer>
        <Button severity="secondary" :label="t('Cancel')" :disabled="isSubmitting" @click="showModal = false" />
        <Button severity="primary" :label="t('Save')" :loading="isSubmitting" @click="submit" />
      </template>
    </Dialog>
  </div>
</template>
