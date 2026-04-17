<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <Button icon="fa fa-arrow-left" text severity="secondary" class="hover:shadow-md mr-2" @click="router.visit(route('users'))" />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ t("Add User") }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <Button icon="fa fa-save" :label="t('Save')" class="uppercase" raised :loading="isSubmitting" @click="submit()" />
      </div>
    </div>
    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="first-name">{{ t("First Name") }}</label>
                  <InputText
                    id="first-name"
                    v-model="firstName"
                    v-bind="firstNameAttrs"
                    autocomplete="off"
                    :class="{ 'p-invalid': errors.first_name }"
                  />
                  <small v-if="errors.first_name" class="text-red-400 dark:text-red-300">
                    {{ errors.first_name }}
                  </small>
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="last-name">{{ t("Last Name") }}</label>
                  <InputText
                    id="last-name"
                    v-model="lastName"
                    v-bind="lastNameAttrs"
                    autocomplete="off"
                    :class="{ 'p-invalid': errors.last_name }"
                  />
                  <small v-if="errors.last_name" class="text-red-400 dark:text-red-300">
                    {{ errors.last_name }}
                  </small>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="email">{{ t("Email") }}</label>
              <InputText id="email" v-model="email" v-bind="emailAttrs" autocomplete="off" :class="{ 'p-invalid': errors.email }" />
              <small v-if="errors.email" class="text-red-400 dark:text-red-300">
                {{ errors.email }}
              </small>
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            {{ t("Additional Information") }}
          </template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="phone">{{ t("Phone Number") }}</label>
                  <InputText id="phone" v-model="phone" v-bind="phoneAttrs" autocomplete="off" />
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="date-of-birth">{{ t("Date of Birth") }}</label>
                  <DatePicker
                    id="date-of-birth"
                    v-model="dateOfBirth"
                    v-bind="dateOfBirthAttrs"
                    :pt="{ pcInputText: { root: 'w-full' } }"
                  />
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
      <div class="md:col-span-4 col-span-12">
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="status">{{ t("Status") }}</label>
              <Select
                id="status"
                v-model="status"
                v-bind="statusAttrs"
                :options="[
                  { name: t('Active'), value: 'active' },
                  { name: t('Inactive'), value: 'inactive' },
                ]"
                option-label="name"
                option-value="value"
              />
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="roles">{{ t("Roles") }}</label>
              <MultiSelect
                id="roles"
                v-model="roles"
                v-bind="rolesAttrs"
                display="chip"
                :options="props.availableRoles"
                option-label="name"
                option-value="id"
                :class="{ 'p-invalid': errors.roles }"
              />
              <small v-if="errors.roles" class="text-red-400 dark:text-red-300">
                {{ errors.roles }}
              </small>
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            {{ t("Credentials") }}
          </template>
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="username">{{ t("Username") }}</label>
              <InputText
                id="username"
                v-model="username"
                v-bind="usernameAttrs"
                autocomplete="off"
                :class="{ 'p-invalid': errors.username }"
              />
              <small v-if="errors.username" class="text-red-400 dark:text-red-300">
                {{ errors.username }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="password">{{ t("Password") }}</label>
              <Password
                id="password"
                v-model="password"
                v-bind="passwordAttrs"
                :class="{ 'p-invalid': errors.password }"
                :prompt-label="t('Choose a password')"
                :weak-label="t('Weak')"
                :medium-label="t('Medium')"
                :strong-label="t('Strong')"
                toggle-mask
                fluid
              />
              <small v-if="errors.password" class="text-red-400 dark:text-red-300">
                {{ errors.password }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="password-confirmation">{{ t("Confirm Password") }}</label>
              <Password
                id="password-confirmation"
                v-model="passwordConfirmation"
                v-bind="passwordConfirmationAttrs"
                :class="{ 'p-invalid': errors.password_confirmation }"
                :prompt-label="t('Re-enter password')"
                :weak-label="t('Weak')"
                :medium-label="t('Medium')"
                :strong-label="t('Strong')"
                toggle-mask
                :feedback="false"
                fluid
              />
              <small v-if="errors.password_confirmation" class="text-red-400 dark:text-red-300">
                {{ errors.password_confirmation }}
              </small>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button, MultiSelect, Card, InputText, Select, DatePicker, Password, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string, array, number, date, ref as yupRef } from "yup";
import { route } from "ziggy-js";
import { type RoleResponse } from "@/Types/role-types";
import AppLayout from "@layouts/admin.vue";

// Set composables
const toast = useToast();
const { t } = useI18n();

// Layout
defineOptions({ layout: AppLayout });

// Props from Inertia
const props = defineProps<{
  availableRoles: RoleResponse[];
}>();

// Schema
const schema = toTypedSchema(
  object({
    first_name: string().required().max(50),
    last_name: string().required().max(50),
    email: string().required().email().max(100),
    username: string()
      .required()
      .min(6)
      .max(50)
      .matches(/^[a-zA-Z0-9_.-]*$/, t("Username can only contain letters, numbers, dots, dashes and underscores")),
    phone: string().nullable().optional(),
    status: string().required().oneOf(["active", "inactive"]),
    date_of_birth: date().nullable().optional(),
    roles: array().of(number().required()).required().min(1, t("At least one role is required")),
    password: string().required().min(8),
    password_confirmation: string()
      .required()
      .min(8)
      .oneOf([yupRef("password")], t("Passwords must match")),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors } = useForm({
  validationSchema: schema,
  initialValues: {
    status: "active",
    roles: [],
  },
});

const [firstName, firstNameAttrs] = defineField("first_name");
const [lastName, lastNameAttrs] = defineField("last_name");
const [email, emailAttrs] = defineField("email");
const [username, usernameAttrs] = defineField("username");
const [phone, phoneAttrs] = defineField("phone");
const [status, statusAttrs] = defineField("status");
const [dateOfBirth, dateOfBirthAttrs] = defineField("date_of_birth");
const [roles, rolesAttrs] = defineField("roles");
const [password, passwordAttrs] = defineField("password");
const [passwordConfirmation, passwordConfirmationAttrs] = defineField("password_confirmation");

// Submit
const submit = handleSubmit((values) => {
  const dateValue =
    values.date_of_birth instanceof Date ? values.date_of_birth.toISOString().split("T")[0] : (values.date_of_birth ?? null);

  router.post(
    route("users.store"),
    {
      ...values,
      date_of_birth: dateValue,
    },
    {
      onSuccess: () => {
        router.visit(route("users"));
      },
      onError: (errs) => {
        setErrors(errs);
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t(Object.values(errs)[0] ?? "An error occurred"),
          life: 3000,
        });
      },
    },
  );
});
</script>
