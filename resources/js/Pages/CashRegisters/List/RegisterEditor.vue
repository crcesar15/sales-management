<script setup lang="ts">
import { Dialog, InputText, Button, Select, ToggleSwitch, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string, boolean, number } from "yup";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { watch, nextTick, computed } from "vue";
import { useI18n } from "vue-i18n";
import type { CashRegisterResponse } from "@/Types/cash-register-types";

const props = defineProps<{
  register: CashRegisterResponse | null;
  stores: Array<{ id: number; name: string; code: string }>;
}>();

const toast = useToast();
const { t } = useI18n();

const showModal = defineModel("show-modal", { type: Boolean, required: true });

const isEditing = computed(() => props.register !== null);

const statusOptions = computed(() => [
  { label: t("Active"), value: "active" },
  { label: t("Inactive"), value: "inactive" },
]);

const storeOptions = computed(() => props.stores.map((s) => ({ label: s.name, value: s.id })));

const schema = toTypedSchema(
  object({
    store_id: number().required(t("Store is required")),
    name: string().required(t("Register name is required")).max(100, t("Register name must be at most 100 characters")),
    code: string().required(t("Register code is required")).max(20, t("Register code must be at most 20 characters")),
    status: string().required(t("Status is required")),
    is_default: boolean(),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors, resetForm, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    store_id: undefined as number | undefined,
    name: "",
    code: "",
    status: "active",
    is_default: false,
  },
});

const [storeId, storeIdAttrs] = defineField("store_id");
const [name, nameAttrs] = defineField("name");
const [code, codeAttrs] = defineField("code");
const [status, statusAttrs] = defineField("status");
const [isDefault, isDefaultAttrs] = defineField("is_default");

watch(showModal, async (val) => {
  if (val) {
    resetForm({
      values: {
        store_id: props.register?.store_id ?? undefined,
        name: props.register?.name ?? "",
        code: props.register?.code ?? "",
        status: props.register?.status ?? "active",
        is_default: props.register?.is_default ?? false,
      },
    });
    await nextTick();
    document.getElementById("name")?.focus();
  }
});

const onHide = () => {
  resetForm();
};

const submit = handleSubmit((values) => {
  const onSuccess = () => {
    showModal.value = false;
    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: props.register ? t("Register updated successfully") : t("Register created successfully"),
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

  if (props.register === null) {
    router.post(route("cash-registers.store"), values, { onSuccess, onError });
  } else {
    router.put(route("cash-registers.update", props.register.id), values, { onSuccess, onError });
  }
});
</script>

<template>
  <div>
    <Dialog
      v-model:visible="showModal"
      :header="isEditing ? t('Edit Register') : t('Add Register')"
      :closable="false"
      :breakpoints="{ '1100px': '50vw', '750px': '75vw', '500px': '90vw' }"
      :style="{ width: '35vw' }"
      modal
      @hide="onHide"
      @keydown.ctrl.enter="submit"
    >
      <div class="flex flex-col gap-4">
        <div v-if="!isEditing" class="flex flex-col gap-2">
          <label for="store_id">
            {{ t("Store") }}
            <span class="text-red-500">*</span>
          </label>
          <Select
            id="store_id"
            v-model="storeId"
            v-bind="storeIdAttrs"
            :options="storeOptions"
            option-label="label"
            option-value="value"
            :placeholder="t('Select store')"
            :empty-message="t('No available options')"
            :class="{ 'p-invalid': submitCount > 0 && !!errors.store_id }"
          />
          <small v-if="submitCount > 0 && errors.store_id" class="text-red-400 dark:text-red-300">
            {{ errors.store_id }}
          </small>
        </div>

        <div class="flex flex-col gap-2">
          <label for="name">
            {{ t("Register Name") }}
            <span class="text-red-500">*</span>
          </label>
          <InputText
            id="name"
            v-model="name"
            v-bind="nameAttrs"
            autocomplete="off"
            :class="{ 'p-invalid': submitCount > 0 && !!errors.name }"
          />
          <small v-if="submitCount > 0 && errors.name" class="text-red-400 dark:text-red-300">
            {{ errors.name }}
          </small>
        </div>

        <div class="flex flex-col gap-2">
          <label for="code">
            {{ t("Register Code") }}
            <span class="text-red-500">*</span>
          </label>
          <InputText
            id="code"
            v-model="code"
            v-bind="codeAttrs"
            autocomplete="off"
            :class="{ 'p-invalid': submitCount > 0 && !!errors.code }"
          />
          <small v-if="submitCount > 0 && errors.code" class="text-red-400 dark:text-red-300">
            {{ errors.code }}
          </small>
        </div>

        <div class="flex flex-col gap-2">
          <label for="status">
            {{ t("Status") }}
            <span class="text-red-500">*</span>
          </label>
          <Select
            id="status"
            v-model="status"
            v-bind="statusAttrs"
            :options="statusOptions"
            option-label="label"
            option-value="value"
            :empty-message="t('No available options')"
            :class="{ 'p-invalid': submitCount > 0 && !!errors.status }"
          />
          <small v-if="submitCount > 0 && errors.status" class="text-red-400 dark:text-red-300">
            {{ errors.status }}
          </small>
        </div>

        <div class="flex items-center gap-3">
          <ToggleSwitch v-model="isDefault" v-bind="isDefaultAttrs" input-id="is_default" />
          <label for="is_default">{{ t("Default Register") }}</label>
        </div>
      </div>
      <template #footer>
        <Button severity="secondary" :label="t('Cancel')" :disabled="isSubmitting" @click="showModal = false" />
        <Button severity="primary" :label="t('Save')" :loading="isSubmitting" @click="submit" />
      </template>
    </Dialog>
  </div>
</template>