<template>
  <div>
    <div class="flex justify-between mb-2">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          @click="$inertia.visit(route('suppliers'))"
        />
        <h4 class="ml-2">
          {{ $t("Add Supplier") }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <PButton
          icon="fa fa-save"
          :label="$t('Save')"
          style="text-transform: uppercase;"
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
                <div class="flex flex-col gap-2 mb-2">
                  <label for="fullname">{{ $t("Fullname") }}</label>
                  <InputText
                    id="fullname"
                    v-model="fullname"
                    autocomplete="off"
                    :class="{'p-invalid': v$.fullname.$invalid && v$.fullname.$dirty}"
                    @blur="v$.fullname.$touch"
                  />
                  <small
                    v-if="v$.fullname.$invalid && v$.fullname.$dirty"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ v$.fullname.$errors[0].$message }}
                  </small>
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-2">
                  <label for="phone">{{ $t("Phone") }}</label>
                  <InputText
                    id="phone"
                    v-model="phone"
                    autocomplete="off"
                    :class="{'p-invalid': v$.phone.$invalid && v$.phone.$dirty}"
                    @blur="v$.phone.$touch"
                  />
                  <small
                    v-if="v$.phone.$invalid && v$.phone.$dirty"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ v$.phone.$errors[0].$message }}
                  </small>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="email">{{ $t("Email") }}</label>
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
            {{ $t("Additional Information") }}
          </template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-2">
                  <label for="address">{{ $t("Address") }}</label>
                  <InputText
                    id="address"
                    v-model="address"
                    autocomplete="off"
                  />
                </div>
              </div>
              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-2">
                  <label for="details">{{ $t("Details") }}</label>
                  <Textarea
                    id="details"
                    v-model="details"
                    rows="3"
                  />
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
      <div class="md:col-span-4 col-span-12">
        <Card>
          <template #title>
            {{ $t("Configuration") }}
          </template>
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
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script>
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import PButton from "primevue/button";

import { useVuelidate } from "@vuelidate/core";
import {
  required,
  email,
  createI18nMessage,
} from "@vuelidate/validators";
import axios from "axios";
import AppLayout from "../../../layouts/admin.vue";
import i18n from "../../../app";

export default {
  components: {
    Card,
    InputText,
    Select,
    PButton,
    Textarea,
  },
  layout: AppLayout,
  setup() {
    return {
      v$: useVuelidate(),
    };
  },
  data() {
    return {
      fullname: "",
      address: "",
      details: "",
      email: "",
      phone: "",
      status: "active",
    };
  },
  validations() {
    const { t } = i18n.global;

    const withI18nMessage = createI18nMessage({
      t,
      messagesPath: "validations",
    });

    return {
      fullname: {
        required: withI18nMessage(required),
      },
      phone: {
        required: withI18nMessage(required),
      },
      email: {
        email: withI18nMessage(email),
      },
    };
  },
  methods: {
    submit() {
      this.v$.$touch();
      if (!this.v$.$invalid) {
        const supplier = {
          fullname: this.fullname,
          phone: this.phone,
          email: this.email,
          address: this.address,
          details: this.details,
          status: this.status,
        };

        axios.post(route("api.suppliers.store"), supplier).then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Supplier has been created successfully"),
            life: 3000,
          });

          this.$inertia.visit(route("suppliers"));
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
