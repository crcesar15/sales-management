<template>
  <div>
    <Dialog
      v-model:visible="showModal"
      :header="t('Brand')"
      :breakpoints="{ '1100px': '60vw', '750px': '75vw', '500px': '90vw' }"
      :style="{ width: '30vw' }"
      modal
      @hide="onHide"
    >
      <div class="flex flex-col gap-2 mb-3">
        <label for="name">
          {{ t('Name') }} <span class="text-red-500">*</span>
        </label>
        <InputText
          id="name"
          v-model="name"
          v-bind="nameAttrs"
          autocomplete="off"
          :class="{ 'p-invalid': errors.name }"
        />
        <small v-if="errors.name" class="text-red-400 dark:text-red-300">
          {{ errors.name }}
        </small>
      </div>
      <template #footer>
        <Button
          severity="secondary"
          :label="t('Cancel')"
          :disabled="isSubmitting"
          @click="showModal = false"
        />
        <Button
          severity="primary"
          :label="t('Save')"
          :loading="isSubmitting"
          @click="submit"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import {
  Dialog,
  InputText,
  Button,
  useToast,
} from "primevue"
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string } from "yup";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { watch, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { type BrandResponse } from "@/Types/brand-types";

const toast = useToast();
const { t } = useI18n();

// Define v-model
const showModal = defineModel("show-modal", { type: Boolean, required: true });

// Define props
const props = defineProps<{
  brand: BrandResponse | null;
}>();

// Schema
const schema = toTypedSchema(
  object({
    name: string().required(t("Name is required")).max(50, t("Name must be at most 50 characters")),
  })
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors, resetForm } = useForm({
  validationSchema: schema,
  initialValues: {
    name: "",
  },
});

const [name, nameAttrs] = defineField("name");

// Reset form when dialog opens
watch(showModal, async (val) => {
  if (val) {
    resetForm({ values: { name: props.brand?.name ?? "" } });
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
      detail: props.brand ? t("Brand updated successfully") : t("Brand created successfully"),
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

  if (props.brand === null) {
    router.post(route("brands.store"), values, { onSuccess, onError });
  } else {
    router.put(route("brands.update", props.brand.id), values, { onSuccess, onError });
  }
});
</script>
