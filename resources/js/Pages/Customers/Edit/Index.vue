<script setup lang="ts">
import { Card, InputText, Select, Button, Toast, useToast } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string } from "yup";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { nextTick } from "vue";
import { useI18n } from "vue-i18n";
import type { Customer } from "@/Types/customer-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  customer: Customer;
}>();

const toast = useToast();
const { t } = useI18n();

const statusOptions = [
  { name: t("Active"), value: "active" },
  { name: t("Inactive"), value: "inactive" },
];

const schema = toTypedSchema(
  object({
    first_name: string().nullable().max(100),
    last_name: string().nullable().max(100),
    email: string().nullable().email().max(255),
    phone: string().nullable().max(50),
    tax_id: string().required().max(50),
    tax_id_name: string().required().max(100),
    status: string().required().oneOf(["active", "inactive"]),
  }),
);

const {
  handleSubmit,
  errors,
  defineField,
  isSubmitting,
  setErrors,
  submitCount,
} = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    first_name: props.customer.first_name ?? "",
    last_name: props.customer.last_name ?? "",
    email: props.customer.email ?? "",
    phone: props.customer.phone ?? "",
    tax_id: props.customer.tax_id ?? "",
    tax_id_name: props.customer.tax_id_name ?? "",
    status: props.customer.status ?? "active",
  },
});

const [firstName, firstNameAttrs] = defineField("first_name");
const [lastName, lastNameAttrs] = defineField("last_name");
const [email, emailAttrs] = defineField("email");
const [phone, phoneAttrs] = defineField("phone");
const [taxId, taxIdAttrs] = defineField("tax_id");
const [taxIdName, taxIdNameAttrs] = defineField("tax_id_name");
const [status, statusAttrs] = defineField("status");

const goBack = () => router.visit(route("customers"));

const submit = handleSubmit((formValues) => {
  const payload = {
    ...formValues,
    first_name: formValues.first_name || null,
    last_name: formValues.last_name || null,
    email: formValues.email || null,
    phone: formValues.phone || null,
    tax_id: formValues.tax_id,
    tax_id_name: formValues.tax_id_name,
  };

  router.put(route("customers.update", props.customer.id), payload, {
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("Customer updated successfully"),
        life: 3000,
      });
      router.visit(route("customers"));
    },
    onError: (errs: Record<string, string>) => {
      setErrors(errs);
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t("Please review the errors in the form"),
        life: 3000,
      });
      nextTick(() => {
        document.querySelector<HTMLInputElement>(".p-invalid")?.focus();
      });
    },
  });
});
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <div class="flex">
        <Button
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="goBack"
        />
        <h2 class="text-2xl font-bold flex items-center m-0">
          {{ t("Edit Customer") }}
        </h2>
      </div>
      <div class="flex flex-col justify-center">
        <Button icon="fa fa-save" :label="t('Save')" class="uppercase" raised :loading="isSubmitting" @click="submit" />
      </div>
    </div>
    <Toast />

    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>
            {{ t("Customer Information") }}
          </template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-1">
                  <label for="first_name">{{ t("First Name") }}</label>
                  <InputText
                    id="first_name"
                    v-model="firstName"
                    v-bind="firstNameAttrs"
                    autocomplete="off"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.first_name }"
                  />
                  <small v-if="submitCount > 0 && errors.first_name" class="text-red-400 dark:text-red-300">
                    {{ errors.first_name }}
                  </small>
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-1">
                  <label for="last_name">{{ t("Last Name") }}</label>
                  <InputText
                    id="last_name"
                    v-model="lastName"
                    v-bind="lastNameAttrs"
                    autocomplete="off"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.last_name }"
                  />
                  <small v-if="submitCount > 0 && errors.last_name" class="text-red-400 dark:text-red-300">
                    {{ errors.last_name }}
                  </small>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-12 gap-4 mt-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-1">
                  <label for="email">{{ t("Email") }}</label>
                  <InputText
                    id="email"
                    v-model="email"
                    v-bind="emailAttrs"
                    autocomplete="off"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.email }"
                  />
                  <small v-if="submitCount > 0 && errors.email" class="text-red-400 dark:text-red-300">
                    {{ errors.email }}
                  </small>
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-1">
                  <label for="phone">{{ t("Phone") }}</label>
                  <InputText
                    id="phone"
                    v-model="phone"
                    v-bind="phoneAttrs"
                    autocomplete="off"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.phone }"
                  />
                  <small v-if="submitCount > 0 && errors.phone" class="text-red-400 dark:text-red-300">
                    {{ errors.phone }}
                  </small>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="md:col-span-4 col-span-12">
        <Card class="mb-4">
          <template #title>
            {{ t("Configuration") }}
          </template>
          <template #content>
            <div class="flex flex-col gap-1">
              <label for="status">{{ t("Status") }} <span class="text-red-500">*</span></label>
              <Select
                id="status"
                v-model="status"
                v-bind="statusAttrs"
                :options="statusOptions"
                option-label="name"
                option-value="value"
                :class="{ 'p-invalid': submitCount > 0 && !!errors.status }"
              />
              <small v-if="submitCount > 0 && errors.status" class="text-red-400 dark:text-red-300">
                {{ errors.status }}
              </small>
            </div>
          </template>
        </Card>

        <Card>
          <template #title>
            {{ t("Tax Information") }}
          </template>
          <template #content>
            <div class="flex flex-col gap-1">
              <label for="tax_id">{{ t("Tax ID") }}</label>
              <InputText
                id="tax_id"
                v-model="taxId"
                v-bind="taxIdAttrs"
                autocomplete="off"
                :class="{ 'p-invalid': submitCount > 0 && !!errors.tax_id }"
              />
              <small v-if="submitCount > 0 && errors.tax_id" class="text-red-400 dark:text-red-300">
                {{ errors.tax_id }}
              </small>
            </div>
            <div class="flex flex-col gap-1 mt-4">
              <label for="tax_id_name">{{ t("Tax ID Name") }}</label>
              <InputText
                id="tax_id_name"
                v-model="taxIdName"
                v-bind="taxIdNameAttrs"
                autocomplete="off"
                :class="{ 'p-invalid': submitCount > 0 && !!errors.tax_id_name }"
              />
              <small v-if="submitCount > 0 && errors.tax_id_name" class="text-red-400 dark:text-red-300">
                {{ errors.tax_id_name }}
              </small>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>