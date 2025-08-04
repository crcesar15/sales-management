<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <Button
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="router.visit(route('users'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ $t('Edit User') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <Button
          icon="fa fa-save"
          :label="$t('Save')"
          class="uppercase"
          raised
          @click="submit()"
        />
      </div>
    </div>
    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="first-name">{{ $t('First Name') }}</label>
                  <InputText
                    id="first-name"
                    v-model="firstName"
                    autocomplete="off"
                    :class="{'p-invalid': v$.firstName.$invalid && v$.firstName.$dirty}"
                    @blur="v$.firstName.$touch"
                  />
                  <small
                    v-if="v$.firstName.$invalid && v$.firstName.$dirty"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ v$.firstName.$errors[0].$message }}
                  </small>
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="last-name">{{ $t('Last Name') }}</label>
                  <InputText
                    id="last-name"
                    v-model="lastName"
                    autocomplete="off"
                    :class="{'p-invalid': v$.lastName.$invalid && v$.lastName.$dirty}"
                    @blur="v$.lastName.$touch"
                  />
                  <small
                    v-if="v$.lastName.$invalid && v$.lastName.$dirty"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ v$.lastName.$errors[0].$message }}
                  </small>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="email">{{ $t('Email') }}</label>
              <InputText
                id="email"
                v-model="email"
                autocomplete="off"
                :class="{'p-invalid': v$.email.$invalid && v$.email.$dirty}"
                @blur="v$.email.$touch"
              />
              <small
                v-if="v$.email.$invalid && v$.email.$dirty"
                class="text-red-400 dark:text-red-300"
              >
                {{ v$.email.$errors[0].$message }}
              </small>
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            {{ $t('Additional Information') }}
          </template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="phone">{{ $t('Phone Number') }}</label>
                  <InputText
                    id="phone"
                    v-model="phone"
                    autocomplete="off"
                  />
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="date-of-birth">{{ $t('Date of Birth') }}</label>
                  <DatePicker
                    id="date-of-birth"
                    v-model="dateOfBirth"
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
              <label for="status">{{ $t('Status') }}</label>
              <Select
                id="status"
                v-model="status"
                :options="[
                  { name: $t('Active'), value: 'active' },
                  { name: $t('Inactive'), value: 'inactive' },
                ]"
                option-label="name"
                option-value="value"
              />
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="roles">{{ $t('Roles') }}</label>
              <MultiSelect
                id="roles"
                v-model="roles"
                display="chip"
                :options="availableRoles"
                option-label="name"
                option-value="id"
                :class="{'p-invalid': v$.roles.$invalid && v$.roles.$dirty}"
                @blur="v$.roles.$touch"
              />
              <small
                v-if="v$.roles.$invalid && v$.roles.$dirty"
                class="text-red-400 dark:text-red-300"
              >
                {{ v$.roles.$errors[0].$message }}
              </small>
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            {{ $t('Credentials') }}
          </template>
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="username">{{ $t('Username') }}</label>
              <InputText
                id="username"
                v-model="username"
                autocomplete="off"
                :class="{'p-invalid': v$.username.$invalid && v$.username.$dirty}"
                @blur="v$.username.$touch"
              />
              <small
                v-if="v$.username.$invalid && v$.username.$dirty"
                class="text-red-400 dark:text-red-300"
              >
                {{ v$.username.$errors[0].$message }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="username">{{ $t('New Password') }}</label>
              <Password
                id="password"
                v-model="password"
                :class="{'p-invalid': v$.password.$invalid && v$.password.$dirty}"
                :prompt-label="$t('Choose a password')"
                :weak-label="$t('Weak')"
                :medium-label="$t('Medium')"
                :strong-label="$t('Strong')"
                toggle-mask
                @blur="v$.password.$touch"
              />
              <small
                v-if="v$.password.$invalid && v$.password.$dirty"
                class="text-red-400 dark:text-red-300"
              >
                {{ v$.password.$errors[0].$message }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="password-confirmation">{{ $t('Confirm Password') }}</label>
              <Password
                id="password-confirmation"
                v-model="passwordConfirmation"
                autocomplete="off"
                :class="{'p-invalid': v$.passwordConfirmation.$invalid && v$.passwordConfirmation.$dirty}"
                :prompt-label="$t('Re-enter password')"
                :weak-label="$t('Weak')"
                :medium-label="$t('Medium')"
                :strong-label="$t('Strong')"
                toggle-mask
                @blur="v$.passwordConfirmation.$touch"
              />
              <small
                v-if="v$.passwordConfirmation.$invalid && v$.passwordConfirmation.$dirty"
                class="text-red-400 dark:text-red-300"
              >
                {{ v$.passwordConfirmation.$errors[0].$message }}
              </small>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useVuelidate } from "@vuelidate/core";
import {
  required,
  requiredIf,
  email as emailValidator,
  createI18nMessage,
  sameAs,
  minLength,
} from "@vuelidate/validators";

import {
  Button,
  MultiSelect,
  Card,
  InputText,
  Select,
  DatePicker,
  Password,
  useToast,
} from "primevue";

import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";

import AppLayout from "../../../Layouts/admin.vue";

// Set composables
const toast = useToast();
const { t } = useI18n();
const withI18nMessage = createI18nMessage({
  t,
  messagesPath: "validations",
});

// Layout
defineOptions({
  layout: AppLayout,
});

// Get Roles and user
const { availableRoles, user } = defineProps(
  {
    availableRoles: { type: Object, required: true },
    user: { type: Object, required: true },
  },
);

// Set variables
const firstName = ref("");
const lastName = ref("");
const email = ref("");
const status = ref("active");
const roles = ref([]);
const phone = ref("");
const dateOfBirth = ref("");
const username = ref("");
const password = ref("");
const passwordConfirmation = ref("");

// Set rules
// Custom rule: must allow only letters, numbers, dashes, underscores and dots
const validateUsername = (value) => {
  if (!value) return true;

  return /^[a-zA-Z0-9_.-]*$/.test(value);
};

const rules = computed(() => ({
  firstName: {
    required: withI18nMessage(required),
  },
  lastName: {
    required: withI18nMessage(required),
  },
  email: {
    required: withI18nMessage(required),
    email: withI18nMessage(emailValidator),
  },
  username: {
    required: withI18nMessage(required),
    minLength: withI18nMessage(minLength(6)),
    username: withI18nMessage(validateUsername),
  },
  roles: {
    required: withI18nMessage(required),
  },
  password: {
    minLength: withI18nMessage(minLength(6)),
  },
  passwordConfirmation: {
    requiredIf: withI18nMessage(requiredIf(password.value.length >= 6)),
    minLength: withI18nMessage(minLength(6)),
    sameAsPassword: withI18nMessage(sameAs(password)),
  },
}));

const v$ = useVuelidate(
  rules,
  {
    firstName,
    lastName,
    email,
    status,
    roles,
    phone,
    dateOfBirth,
    username,
    password,
    passwordConfirmation,
  },
);

watch(
  user.value,
  () => {
    firstName.value = user.first_name;
    lastName.value = user.last_name;
    email.value = user.email;
    status.value = user.status;
    phone.value = user.phone;
    dateOfBirth.value = user.date_of_birth;
    username.value = user.username;

    if (typeof (user.roles) === "object" && user.roles.length > 0) {
      roles.value = user.roles.map((role) => role.id);
    } else {
      roles.value = [];
    }
  },
  {
    immediate: true,
  },
);

// Submit
const submit = () => {
  v$.value.$touch();

  if (!v$.value.$invalid) {
    const validatedUser = {
      first_name: firstName.value,
      last_name: lastName.value,
      email: email.value,
      status: status.value,
      roles: roles.value,
      phone: phone.value,
      date_of_birth: dateOfBirth.value
        ? window.moment(dateOfBirth.value).format("YYYY-MM-DD")
        : null,
      username: username.value,
    };

    if (password.value) {
      validatedUser.password = password.value;
      validatedUser.password_confirmation = passwordConfirmation.value;
    }

    axios.put(route("api.users.update", user.id), validatedUser).then(() => {
      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("User has been updated successfully"),
        life: 3000,
      });

      router.visit(route("users"));
    }).catch((error) => {
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t(error.response.data.message),
        life: 3000,
      });
    });
  } else {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t("Please review the errors in the form"),
      life: 3000,
    });
  }
};
</script>

<style>
.p-password > input {
  width: 100% !important;
}
</style>
