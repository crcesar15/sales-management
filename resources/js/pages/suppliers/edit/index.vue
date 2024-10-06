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
          {{ $t("Edit Supplier") }}
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
                  <label for="fullname">{{ $t("Full Name") }}</label>
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
            <div class="flex flex-col gap-2 mb-2">
              <label for="address">{{ $t("Address") }}</label>
              <InputText
                id="address"
                v-model="address"
                autocomplete="off"
              />
            </div>
            <div class="flex flex-col gap-2 mb-2">
              <label for="details">{{ $t("Details") }}</label>
              <Textarea
                id="details"
                v-model="details"
                rows="3"
              />
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            <div class="flex flex-row justify-between items-center">
              <div>
                {{ $t("Additional Contacts") }}
              </div>
              <div class="flex items-center">
                <label
                  for="hasAdditionalContacts"
                  class="mr-3 text-primary"
                  style="font-size: 14px;"
                >
                  {{ $t("This supplier has additional contacts?") }}
                </label>
                <ToggleSwitch
                  v-model="hasAdditionalContacts"
                  input-id="hasAdditionalContacts"
                />
              </div>
            </div>
          </template>
          <template #content>
            <div
              v-show="hasAdditionalContacts"
              class="mt-3"
            >
              <div
                v-for="(contact, index) in additionalContacts"
                :key="index"
                class="border-2 rounded-border border-surface p-2 mb-2"
              >
                <div>
                  <div
                    class="flex justify-end items-center"
                    style="margin-bottom: -20px;"
                  >
                    <PButton
                      v-tooltip.top="$t('Delete')"
                      icon="fa fa-trash"
                      size="small"
                      text
                      class="!p-3 !m-0 !w-[21px] !h-[21px]"
                      @click="additionalContacts.splice(index, 1)"
                    />
                  </div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                  <div class="lg:col-span-4 md:col-span-6 col-span-12">
                    <div class="flex flex-col gap-2 mb-2">
                      <label for="fullname">{{ $t("Full Name") }}</label>
                      <InputText
                        v-model="contact.fullname"
                        autocomplete="off"
                        :class="{'p-invalid': v$.additionalContacts.$each.$response.$errors[index].fullname.length > 0}"
                        @blur="v$.additionalContacts.$each.$response.$errors[index].fullname.$touch"
                      />
                      <small
                        v-if="v$.additionalContacts.$each.$response.$errors[index].fullname.length > 0"
                        class="text-red-400 dark:text-red-300"
                      >
                        {{ v$.additionalContacts.$each.$response.$errors[index].fullname[0].$message }}
                      </small>
                    </div>
                  </div>
                  <div class="lg:col-span-4 md:col-span-6 col-span-12">
                    <div class="flex flex-col gap-2 mb-2">
                      <label for="phone">{{ $t("Phone") }}</label>
                      <InputText
                        v-model="contact.phone"
                        autocomplete="off"
                        :class="{'p-invalid': v$.additionalContacts.$each.$response.$errors[index].phone.length > 0}"
                        @blur="v$.additionalContacts.$each.$response.$errors[index].phone.$touch"
                      />
                      <small
                        v-if="v$.additionalContacts.$each.$response.$errors[index].phone.length > 0"
                        class="text-red-400 dark:text-red-300"
                      >
                        {{ v$.additionalContacts.$each.$response.$errors[index].phone[0].$message }}
                      </small>
                    </div>
                  </div>
                  <div class="lg:col-span-4 col-span-12">
                    <div class="flex flex-col gap-2 mb-2">
                      <label for="email">{{ $t("Email") }}</label>
                      <InputText
                        v-model="contact.email"
                        autocomplete="off"
                        :class="{'p-invalid': v$.additionalContacts.$each.$response.$errors[index].email.length > 0}"
                        @blur="v$.additionalContacts.$each.$response.$errors[index].email.$touch"
                      />
                      <small
                        v-if="v$.additionalContacts.$each.$response.$errors[index].email.length > 0"
                        class="text-red-400 dark:text-red-300"
                      >
                        {{ v$.additionalContacts.$each.$response.$errors[index].email[0].$message }}
                      </small>
                    </div>
                  </div>
                </div>
              </div>
              <small
                v-show="additionalContacts.length === 0"
                class="text-red-400 dark:text-red-300"
              >
                {{ $t("You need to add at least one additional contact") }}
              </small>
              <div class="flex justify-end">
                <PButton
                  icon="fa fa-plus"
                  text
                  :label="$t('Add More Contacts')"
                  @click="additionalContacts.push({ fullname: '', phone: '', email: '' })"
                />
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
import ToggleSwitch from "primevue/toggleswitch";

import { useVuelidate } from "@vuelidate/core";
import {
  required,
  requiredIf,
  email,
  createI18nMessage,
  helpers,
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
    ToggleSwitch,
  },
  layout: AppLayout,
  props: {
    supplier: {
      type: Object,
      required: true,
    },
  },
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
      hasAdditionalContacts: false,
      additionalContacts: [
        {
          fullname: "",
          phone: "",
          email: "",
        },
      ],
    };
  },
  watch: {
    supplier: {
      handler() {
        this.fullname = this.supplier.fullname;
        this.phone = this.supplier.phone;
        this.email = this.supplier.email;
        this.address = this.supplier.address;
        this.details = this.supplier.details;
        this.status = this.supplier.status;

        this.hasAdditionalContacts = this.supplier.additional_contacts?.length > 0;

        if (this.hasAdditionalContacts) {
          this.additionalContacts = this.supplier.additional_contacts;
        }
      },
      immediate: true,
    },
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
      additionalContacts: {
        required: withI18nMessage(requiredIf(() => this.hasAdditionalContacts)),
        $each: helpers.forEach({
          fullname: {
            required: withI18nMessage(required, { messagePath: () => ("validations.required") }),
          },
          phone: {
            required: withI18nMessage(required, { messagePath: () => ("validations.required") }),
          },
          email: {
            email: withI18nMessage(email, { messagePath: () => ("validations.email") }),
          },
        }),
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

        if (this.hasAdditionalContacts) {
          supplier.additional_contacts = this.additionalContacts;
        } else {
          supplier.additional_contacts = null;
        }

        axios.put(route("api.suppliers.update", this.supplier.id), supplier).then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Supplier has been updated successfully"),
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
