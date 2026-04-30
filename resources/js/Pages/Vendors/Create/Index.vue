<script setup lang="ts">
import {
  Card,
  InputText,
  Textarea,
  Select,
  Button,
  ToggleSwitch,
  Toast,
  useToast,
} from "primevue";
import AppLayout from "@layouts/admin.vue";
import AdditionalContactsEditor from "@components/Vendors/AdditionalContactsEditor.vue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string } from "yup";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { nextTick, ref } from "vue";
import { useI18n } from "vue-i18n";
import type { AdditionalContact } from "@/Types/vendor-types";

defineOptions({ layout: AppLayout });

const toast = useToast();
const { t } = useI18n();

const hasAdditionalContacts = ref(false);
const additionalContacts = ref<AdditionalContact[]>([]);

const statusOptions = [
  { name: t("Active"), value: "active" },
  { name: t("Inactive"), value: "inactive" },
  { name: t("Archived"), value: "archived" },
];

const schema = toTypedSchema(
  object({
    fullname: string().required().max(255),
    email: string().nullable().email().max(255),
    phone: string().nullable().max(50),
    address: string().nullable().max(500),
    details: string().nullable().max(1000),
    status: string().required().oneOf(["active", "inactive", "archived"]),
  }),
);

const {
  handleSubmit,
  errors,
  defineField,
  isSubmitting,
  setErrors,
} = useForm({
  validationSchema: schema,
  initialValues: {
    fullname: "",
    email: "",
    phone: "",
    address: "",
    details: "",
    status: "active",
  },
});

const [fullname, fullnameAttrs] = defineField("fullname");
const [email, emailAttrs] = defineField("email");
const [phone, phoneAttrs] = defineField("phone");
const [address, addressAttrs] = defineField("address");
const [details, detailsAttrs] = defineField("details");
const [status, statusAttrs] = defineField("status");

const goBack = () => router.visit(route("vendors"));

const submit = handleSubmit((formValues) => {
  const payload = {
    ...formValues,
    email: formValues.email || null,
    phone: formValues.phone || null,
    address: formValues.address || null,
    details: formValues.details || null,
    additional_contacts: hasAdditionalContacts.value ? additionalContacts.value : null,
  };

  router.post(route("vendors.store"), payload, {
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("Vendor created successfully"),
        life: 3000,
      });
      router.visit(route("vendors"));
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
          {{ t("Add Vendor") }}
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
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-1">
                  <label for="fullname">{{ t("Full Name") }} <span class="text-red-500">*</span></label>
                  <InputText
                    id="fullname"
                    v-model="fullname"
                    v-bind="fullnameAttrs"
                    autocomplete="off"
                    :class="{ 'p-invalid': errors.fullname }"
                  />
                  <small v-if="errors.fullname" class="text-red-400 dark:text-red-300">
                    {{ errors.fullname }}
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
                    :class="{ 'p-invalid': errors.phone }"
                  />
                  <small v-if="errors.phone" class="text-red-400 dark:text-red-300">
                    {{ errors.phone }}
                  </small>
                </div>
              </div>
            </div>
            <div class="flex flex-col gap-1 mt-4">
              <label for="email">{{ t("Email") }}</label>
              <InputText
                id="email"
                v-model="email"
                v-bind="emailAttrs"
                autocomplete="off"
                :class="{ 'p-invalid': errors.email }"
              />
              <small v-if="errors.email" class="text-red-400 dark:text-red-300">
                {{ errors.email }}
              </small>
            </div>
            <div class="flex flex-col gap-1 mt-4">
              <label for="address">{{ t("Address") }}</label>
              <InputText
                id="address"
                v-model="address"
                v-bind="addressAttrs"
                autocomplete="off"
                :class="{ 'p-invalid': errors.address }"
              />
              <small v-if="errors.address" class="text-red-400 dark:text-red-300">
                {{ errors.address }}
              </small>
            </div>
            <div class="flex flex-col gap-1 mt-4">
              <label for="details">{{ t("Details") }}</label>
              <Textarea
                id="details"
                v-model="details"
                v-bind="detailsAttrs"
                rows="3"
                :class="{ 'p-invalid': errors.details }"
              />
              <small v-if="errors.details" class="text-red-400 dark:text-red-300">
                {{ errors.details }}
              </small>
            </div>
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>
            <div class="flex flex-row justify-between items-center">
              <span>{{ t("Additional Contacts") }}</span>
              <div class="flex items-center gap-2">
                <label for="hasAdditionalContacts" class="text-primary text-sm">
                  {{ t("This vendor has additional contacts?") }}
                </label>
                <ToggleSwitch v-model="hasAdditionalContacts" input-id="hasAdditionalContacts" />
              </div>
            </div>
          </template>
          <template #content>
            <AdditionalContactsEditor v-if="hasAdditionalContacts" v-model="additionalContacts" />
            <div v-else class="text-surface-400 text-center py-4">
              {{ t("No additional contacts added") }}
            </div>
          </template>
        </Card>
      </div>

      <div class="md:col-span-4 col-span-12">
        <Card>
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
                :class="{ 'p-invalid': errors.status }"
              />
              <small v-if="errors.status" class="text-red-400 dark:text-red-300">
                {{ errors.status }}
              </small>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>
