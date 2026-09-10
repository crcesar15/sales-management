<script setup lang="ts">
import { Button, Dialog, InputText, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string } from "yup";
import axios from "axios";
import { nextTick, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useCategoryClient } from "@composables/useCategoryClient";
import type { CategoryResponse } from "@app-types/category-types";

const emit = defineEmits<{
  created: [category: CategoryResponse];
}>();
const showModal = defineModel("show-modal", { type: Boolean, required: true });
const toast = useToast();
const { t } = useI18n();
const { storeCategoryApi } = useCategoryClient();

const schema = toTypedSchema(
  object({
    name: string().required(t("Name is required")).max(50, t("Name must be at most 50 characters")),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors, resetForm, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: { name: "" },
});

const [name, nameAttrs] = defineField("name");

watch(showModal, async (visible) => {
  if (visible) {
    resetForm();
    await nextTick();
    document.getElementById("product-category-name")?.focus();
  }
});

const onHide = () => {
  resetForm();
};

const submit = handleSubmit(async (values) => {
  try {
    const { data: category } = await storeCategoryApi(values);
    emit("created", category);
    showModal.value = false;
    toast.add({ severity: "success", summary: t("Success"), detail: t("Category created successfully"), life: 3000 });
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
    :header="t('Add Category')"
    :closable="false"
    :breakpoints="{ '1100px': '60vw', '750px': '75vw', '500px': '90vw' }"
    :style="{ width: '30vw' }"
    modal
    @hide="onHide"
    @keydown.ctrl.enter="submit"
  >
    <div class="flex flex-col gap-2 mb-3">
      <label for="product-category-name">
        {{ t("Name") }}
        <span class="text-red-500">*</span>
      </label>
      <InputText
        id="product-category-name"
        v-model="name"
        v-bind="nameAttrs"
        autocomplete="off"
        :class="{ 'p-invalid': submitCount > 0 && !!errors.name }"
      />
      <small v-if="submitCount > 0 && errors.name" class="text-red-400 dark:text-red-300">
        {{ errors.name }}
      </small>
    </div>
    <template #footer>
      <Button severity="secondary" :label="t('Cancel')" :disabled="isSubmitting" @click="showModal = false" />
      <Button severity="primary" :label="t('Save')" :loading="isSubmitting" @click="submit" />
    </template>
  </Dialog>
</template>
