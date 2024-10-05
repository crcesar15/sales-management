<template>
  <div>
    <div class="flex justify-between mb-2">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          @click="$inertia.visit(route('users'))"
        />
        <h4 class="ml-2">
          {{ $t('Add User') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <PButton
          icon="fa fa-save"
          :label="$t('Save')"
          style="text-transform: uppercase"
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
                  <label for="first_name">{{ $t('First Name') }}</label>
                  <InputText
                    id="first_name"
                    v-model="first_name"
                    autocomplete="off"
                    :class="{'p-invalid': v$.first_name.$invalid && v$.first_name.$dirty}"
                    @blur="v$.first_name.$touch"
                  />
                  <small
                    v-if="v$.first_name.$invalid && v$.first_name.$dirty"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ v$.first_name.$errors[0].$message }}
                  </small>
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="last_name">{{ $t('Last Name') }}</label>
                  <InputText
                    id="last_name"
                    v-model="last_name"
                    autocomplete="off"
                    :class="{'p-invalid': v$.last_name.$invalid && v$.last_name.$dirty}"
                    @blur="v$.last_name.$touch"
                  />
                  <small
                    v-if="v$.last_name.$invalid && v$.last_name.$dirty"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ v$.last_name.$errors[0].$message }}
                  </small>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="first_name">{{ $t('Email') }}</label>
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
                  <label for="date_of_birth">{{ $t('Date of Birth') }}</label>
                  <DatePicker
                    id="date_of_birth"
                    v-model="date_of_birth"
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
              <label for="status">{{ $t('Role') }}</label>
              <Select
                v-model="role"
                :options="roles"
                option-label="name"
                option-value="id"
                :class="{'p-invalid': v$.role.$invalid && v$.role.$dirty}"
                @blur="v$.role.$touch"
              />
              <small
                v-if="v$.role.$invalid && v$.role.$dirty"
                class="text-red-400 dark:text-red-300"
              >
                {{ v$.role.$errors[0].$message }}
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
              <label for="username">{{ $t('Password') }}</label>
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
              <label for="password_confirmation">{{ $t('Confirm Password') }}</label>
              <Password
                id="password_confirmation"
                v-model="password_confirmation"
                autocomplete="off"
                :class="{'p-invalid': v$.password_confirmation.$invalid && v$.password_confirmation.$dirty}"
                :prompt-label="$t('Re-enter password')"
                :weak-label="$t('Weak')"
                :medium-label="$t('Medium')"
                :strong-label="$t('Strong')"
                toggle-mask
                @blur="v$.password_confirmation.$touch"
              />
              <small
                v-if="v$.password_confirmation.$invalid && v$.password_confirmation.$dirty"
                class="text-red-400 dark:text-red-300"
              >
                {{ v$.password_confirmation.$errors[0].$message }}
              </small>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script>
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import PButton from "primevue/button";
import DatePicker from "primevue/datepicker";
import Password from "primevue/password";

import { useVuelidate } from "@vuelidate/core";
import {
  required,
  email,
  createI18nMessage,
  sameAs,
  minLength,
} from "@vuelidate/validators";
import AppLayout from "../../../layouts/admin.vue";
import i18n from "../../../app";

const username = (value) => {
  if (!value) return true;

  // must allow only letters, numbers, dashes, underscores and dots
  return /^[a-zA-Z0-9_.-]*$/.test(value);
};

export default {
  components: {
    Card,
    InputText,
    Select,
    PButton,
    DatePicker,
    Password,
  },
  layout: AppLayout,
  props: {
    roles: {
      type: Array,
      default: () => [],
    },
  },
  setup() {
    return {
      v$: useVuelidate(),
    };
  },
  data() {
    return {
      first_name: "",
      last_name: "",
      email: "",
      status: "active",
      role: "",
      phone: "",
      date_of_birth: "",
      username: "",
      password: "",
      password_confirmation: "",
    };
  },
  validations() {
    const { t } = i18n.global;

    const withI18nMessage = createI18nMessage({
      t,
      messagesPath: "validations",
    });

    return {
      first_name: {
        required: withI18nMessage(required),
      },
      last_name: {
        required: withI18nMessage(required),
      },
      email: {
        required: withI18nMessage(required),
        email: withI18nMessage(email),
      },
      username: {
        required: withI18nMessage(required),
        minLength: withI18nMessage(minLength(6)),
        username: withI18nMessage(username),
      },
      role: {
        required: withI18nMessage(required),
      },
      password: {
        required: withI18nMessage(required),
        minLength: withI18nMessage(minLength(6)),
      },
      password_confirmation: {
        required: withI18nMessage(required),
        minLength: withI18nMessage(minLength(6)),
        sameAsPassword: withI18nMessage(sameAs(this.password)),
      },
    };
  },
  methods: {
    submit() {
      this.v$.$touch();

      if (!this.v$.$invalid) {
        const user = {
          first_name: this.first_name,
          last_name: this.last_name,
          email: this.email,
          status: this.status,
          role_id: this.role,
          phone: this.phone,
          date_of_birth: moment(this.date_of_birth).format("YYYY-MM-DD"),
          username: this.username,
          password: this.password,
          password_confirmation: this.password_confirmation,
        };

        axios.post(route("api.users.store"), user).then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("User has been created successfully"),
            life: 3000,
          });

          this.$inertia.visit(route("users"));
        }).catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: this.$t("Error"),
            detail: this.$t(error.response.data.message),
            life: 3000,
          });
        });
      } else {
        this.$toast.add({
          severity: "error",
          summary: this.$t("Error"),
          detail: this.$t("Please review the errors in the form"),
          life: 3000,
        });
      }
    },
  },
};
</script>

<style>
.p-password > input {
  width: 100% !important;
}
</style>
