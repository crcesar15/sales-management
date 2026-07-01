<script setup lang="ts">
import { Button, Card, InputText, InputNumber, Select, Tabs, Tab, TabList, TabPanel, TabPanels, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string, number } from "yup";
import { route } from "ziggy-js";
import AppLayout from "@layouts/admin.vue";
import { ref } from "vue";

// Layout
defineOptions({ layout: AppLayout });
// Props from Inertia
const props = defineProps<{
  settings: Record<string, Record<string, string>>;
}>();
// Set composables
const toast = useToast();
const { t } = useI18n();

// Timezone options
const timezoneOptions = Intl.supportedValuesOf("timeZone").map((tz) => ({
  label: tz.replace(/_/g, " "),
  value: tz,
}));

// Format options
const dateFormatOptions = [
  { label: "YYYY-MM-DD", value: "YYYY-MM-DD" },
  { label: "DD/MM/YYYY", value: "DD/MM/YYYY" },
  { label: "MM/DD/YYYY", value: "MM/DD/YYYY" },
  { label: "DD-MM-YYYY", value: "DD-MM-YYYY" },
];

const datetimeFormatOptions = [
  { label: "YYYY-MM-DD HH:mm", value: "YYYY-MM-DD HH:mm" },
  { label: "DD/MM/YYYY HH:mm", value: "DD/MM/YYYY HH:mm" },
  { label: "MM/DD/YYYY HH:mm", value: "MM/DD/YYYY HH:mm" },
  { label: "DD-MM-YYYY HH:mm", value: "DD-MM-YYYY HH:mm" },
];

const activeTab = ref("general");

// ─── General Form ──────────────────────────────────────────────────────────

const generalSchema = toTypedSchema(
  object({
    business_name: string().required().max(100),
    business_address: string().nullable().optional().max(500),
    business_phone: string().nullable().optional().max(30),
    timezone: string().required(),
    date_format: string().required(),
    datetime_format: string().required(),
  }),
);

const {
  handleSubmit: handleSubmitGeneral,
  errors: generalErrors,
  defineField: generalDefineField,
  isSubmitting: generalIsSubmitting,
  setErrors: generalSetErrors,
  submitCount: generalSubmitCount,
} = useForm({
  validationSchema: generalSchema,
  validateOnMount: false,
  initialValues: {
    business_name: props.settings.general?.business_name ?? "My Store",
    business_address: props.settings.general?.business_address ?? "",
    business_phone: props.settings.general?.business_phone ?? "",
    timezone: props.settings.general?.timezone ?? "UTC",
    date_format: props.settings.general?.date_format ?? "YYYY-MM-DD",
    datetime_format: props.settings.general?.datetime_format ?? "YYYY-MM-DD HH:mm",
  },
});

const [generalBusinessName, generalBusinessNameAttrs] = generalDefineField("business_name");
const [generalBusinessAddress, generalBusinessAddressAttrs] = generalDefineField("business_address");
const [generalBusinessPhone, generalBusinessPhoneAttrs] = generalDefineField("business_phone");
const [generalTimezone, generalTimezoneAttrs] = generalDefineField("timezone");
const [generalDateFormat, generalDateFormatAttrs] = generalDefineField("date_format");
const [generalDatetimeFormat, generalDatetimeFormatAttrs] = generalDefineField("datetime_format");

const onSubmitGeneral = handleSubmitGeneral((values) => {
  router.put(route("settings.general.update"), values, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: t("Saved"),
        detail: t("Settings updated successfully"),
        life: 3000,
      });
    },
    onError: (errors) => {
      generalSetErrors(errors);
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t("An unexpected error occurred."),
        life: 3000,
      });
    },
  });
});

// ─── Tax Form ──────────────────────────────────────────────────────────────

const taxSchema = toTypedSchema(
  object({
    tax_rate: number().required().min(0).max(100),
  }),
);

const {
  handleSubmit: handleSubmitTax,
  errors: taxErrors,
  defineField: taxDefineField,
  isSubmitting: taxIsSubmitting,
  setErrors: taxSetErrors,
  submitCount: taxSubmitCount,
} = useForm({
  validationSchema: taxSchema,
  validateOnMount: false,
  initialValues: {
    tax_rate: parseFloat(props.settings.tax?.tax_rate ?? "0") || 0,
  },
});

const [taxTaxRate, taxTaxRateAttrs] = taxDefineField("tax_rate");

const onSubmitTax = handleSubmitTax((values) => {
  router.put(route("settings.tax.update"), values, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: t("Saved"),
        detail: t("Settings updated successfully"),
        life: 3000,
      });
    },
    onError: (errors) => {
      taxSetErrors(errors);
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t("An unexpected error occurred."),
        life: 3000,
      });
    },
  });
});

// ─── Finance Form ──────────────────────────────────────────────────────────

const currencyOptions = [
  { label: "USD ($)", value: "USD" },
  { label: "EUR (€)", value: "EUR" },
  { label: "MXN ($)", value: "MXN" },
  { label: "COP ($)", value: "COP" },
  { label: "BOB (Bs)", value: "BOB" },
];

const financeSchema = toTypedSchema(
  object({
    currency: string().required(),
    currency_symbol: string().required().max(5),
    decimal_precision: number().required().min(0).max(6),
  }),
);

const {
  handleSubmit: handleSubmitFinance,
  errors: financeErrors,
  defineField: financeDefineField,
  isSubmitting: financeIsSubmitting,
  setErrors: financeSetErrors,
  submitCount: financeSubmitCount,
} = useForm({
  validationSchema: financeSchema,
  validateOnMount: false,
  initialValues: {
    currency: props.settings.finance?.currency ?? "USD",
    currency_symbol: props.settings.finance?.currency_symbol ?? "$",
    decimal_precision: parseInt(props.settings.finance?.decimal_precision ?? "2") || 2,
  },
});

const [financeCurrency, financeCurrencyAttrs] = financeDefineField("currency");
const [financeCurrencySymbol, financeCurrencySymbolAttrs] = financeDefineField("currency_symbol");
const [financeDecimalPrecision, financeDecimalPrecisionAttrs] = financeDefineField("decimal_precision");

const onSubmitFinance = handleSubmitFinance((values) => {
  router.put(route("settings.finance.update"), values, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: t("Saved"),
        detail: t("Settings updated successfully"),
        life: 3000,
      });
    },
    onError: (errors) => {
      financeSetErrors(errors);
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t("An unexpected error occurred."),
        life: 3000,
      });
    },
  });
});
</script>

<template>
  <div class="max-w-5xl mx-auto">
    <div class="flex justify-between mb-3">
      <h4 class="text-2xl font-bold flex items-center m-0">
        {{ t("Settings") }}
      </h4>
    </div>

    <Card>
      <template #content>
        <Tabs v-model:value="activeTab">
          <TabList
            :pt="{
              activeBar: {
                class: 'border-2 border-primary',
              },
            }"
          >
            <Tab
              value="general"
              :pt="{
                root: ({ context }: any) => ({
                  class: [{ 'bg-primary-50 !rounded-t-lg dark:bg-primary-900': context.active }],
                }),
              }"
            >
              {{ t("General") }}
            </Tab>
            <Tab
              value="tax"
              :pt="{
                root: ({ context }: any) => ({
                  class: [{ 'bg-primary-50 !rounded-t-lg dark:bg-primary-900': context.active }],
                }),
              }"
            >
              {{ t("Tax") }}
            </Tab>
            <Tab
              value="finance"
              :pt="{
                root: ({ context }: any) => ({
                  class: [{ 'bg-primary-50 !rounded-t-lg dark:bg-primary-900': context.active }],
                }),
              }"
            >
              {{ t("Finance") }}
            </Tab>
          </TabList>
          <TabPanels>
            <TabPanel value="general">
              <form @submit.prevent="onSubmitGeneral" class="space-y-4">
                <div class="grid grid-cols-12 gap-4">
                  <div class="col-span-12 md:col-span-6">
                    <div class="flex flex-col gap-2 mb-3">
                      <label for="business_name">{{ t("Business Name") }}</label>
                      <InputText
                        id="business_name"
                        v-model="generalBusinessName"
                        v-bind="generalBusinessNameAttrs"
                        autocomplete="off"
                        :class="{ 'p-invalid': generalSubmitCount > 0 && !!generalErrors.business_name }"
                      />
                      <small v-if="generalSubmitCount > 0 && generalErrors.business_name" class="text-red-400 dark:text-red-300">
                        {{ generalErrors.business_name }}
                      </small>
                    </div>
                  </div>

                  <div class="col-span-12 md:col-span-6">
                    <div class="flex flex-col gap-2 mb-3">
                      <label for="business_phone">{{ t("Business Phone") }}</label>
                      <InputText
                        id="business_phone"
                        v-model="generalBusinessPhone"
                        v-bind="generalBusinessPhoneAttrs"
                        type="tel"
                        autocomplete="off"
                        :class="{ 'p-invalid': generalSubmitCount > 0 && !!generalErrors.business_phone }"
                      />
                      <small v-if="generalSubmitCount > 0 && generalErrors.business_phone" class="text-red-400 dark:text-red-300">
                        {{ generalErrors.business_phone }}
                      </small>
                    </div>
                  </div>

                  <div class="col-span-12">
                    <div class="flex flex-col gap-2 mb-3">
                      <label for="business_address">{{ t("Business Address") }}</label>
                      <InputText
                        id="business_address"
                        v-model="generalBusinessAddress"
                        v-bind="generalBusinessAddressAttrs"
                        autocomplete="off"
                        :class="{ 'p-invalid': generalSubmitCount > 0 && !!generalErrors.business_address }"
                      />
                      <small v-if="generalSubmitCount > 0 && generalErrors.business_address" class="text-red-400 dark:text-red-300">
                        {{ generalErrors.business_address }}
                      </small>
                    </div>
                  </div>

                  <div class="col-span-12 md:col-span-6">
                    <div class="flex flex-col gap-2 mb-3">
                      <label for="timezone">{{ t("Timezone") }}</label>
                      <Select
                        id="timezone"
                        v-model="generalTimezone"
                        v-bind="generalTimezoneAttrs"
                        :options="timezoneOptions"
                        option-label="label"
                        option-value="value"
                        filter
                        :placeholder="t('Select timezone')"
                        :class="{ 'p-invalid': generalSubmitCount > 0 && !!generalErrors.timezone }"
                      />
                      <small v-if="generalSubmitCount > 0 && generalErrors.timezone" class="text-red-400 dark:text-red-300">
                        {{ generalErrors.timezone }}
                      </small>
                    </div>
                  </div>

                  <div class="col-span-12 md:col-span-6">
                    <div class="flex flex-col gap-2 mb-3">
                      <label for="date_format">{{ t("Date Format") }}</label>
                      <Select
                        id="date_format"
                        v-model="generalDateFormat"
                        v-bind="generalDateFormatAttrs"
                        :options="dateFormatOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('Select date format')"
                        :class="{ 'p-invalid': generalSubmitCount > 0 && !!generalErrors.date_format }"
                      />
                      <small v-if="generalSubmitCount > 0 && generalErrors.date_format" class="text-red-400 dark:text-red-300">
                        {{ generalErrors.date_format }}
                      </small>
                    </div>
                  </div>

                  <div class="col-span-12 md:col-span-6">
                    <div class="flex flex-col gap-2 mb-3">
                      <label for="datetime_format">{{ t("Datetime Format") }}</label>
                      <Select
                        id="datetime_format"
                        v-model="generalDatetimeFormat"
                        v-bind="generalDatetimeFormatAttrs"
                        :options="datetimeFormatOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('Select datetime format')"
                        :class="{ 'p-invalid': generalSubmitCount > 0 && !!generalErrors.datetime_format }"
                      />
                      <small v-if="generalSubmitCount > 0 && generalErrors.datetime_format" class="text-red-400 dark:text-red-300">
                        {{ generalErrors.datetime_format }}
                      </small>
                    </div>
                  </div>
                </div>

                <div class="flex justify-end mt-4">
                  <Button type="submit" :label="t('Save')" icon="fa fa-save" class="uppercase" raised :loading="generalIsSubmitting" />
                </div>
              </form>
            </TabPanel>

            <TabPanel value="tax">
              <form @submit.prevent="onSubmitTax" class="space-y-4">
                <div class="grid grid-cols-12 gap-4">
                  <div class="col-span-12 md:col-span-6">
                    <div class="flex flex-col gap-2 mb-3">
                      <label for="tax_rate">{{ t("Tax Rate (%)") }}</label>
                      <InputNumber
                        id="tax_rate"
                        v-model="taxTaxRate"
                        v-bind="taxTaxRateAttrs"
                        :min="0"
                        :max="100"
                        :min-fraction-digits="0"
                        :max-fraction-digits="2"
                        suffix=" %"
                        class="w-full"
                        :class="{ 'p-invalid': taxSubmitCount > 0 && !!taxErrors.tax_rate }"
                      />
                      <small class="text-gray-500">{{ t("Applied to all sales transactions") }}</small>
                      <small v-if="taxSubmitCount > 0 && taxErrors.tax_rate" class="text-red-400 dark:text-red-300">
                        {{ taxErrors.tax_rate }}
                      </small>
                    </div>
                  </div>
                </div>

                <div class="flex justify-end mt-4">
                  <Button type="submit" :label="t('Save')" icon="fa fa-save" class="uppercase" raised :loading="taxIsSubmitting" />
                </div>
              </form>
            </TabPanel>

            <TabPanel value="finance">
              <form @submit.prevent="onSubmitFinance" class="space-y-4">
                <div class="grid grid-cols-12 gap-4">
                  <div class="col-span-12 md:col-span-6">
                    <div class="flex flex-col gap-2 mb-3">
                      <label for="currency">{{ t("Currency Code") }}</label>
                      <Select
                        id="currency"
                        v-model="financeCurrency"
                        v-bind="financeCurrencyAttrs"
                        :options="currencyOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('Select currency')"
                        :class="{ 'p-invalid': financeSubmitCount > 0 && !!financeErrors.currency }"
                      />
                      <small v-if="financeSubmitCount > 0 && financeErrors.currency" class="text-red-400 dark:text-red-300">
                        {{ financeErrors.currency }}
                      </small>
                    </div>
                  </div>

                  <div class="col-span-12 md:col-span-6">
                    <div class="flex flex-col gap-2 mb-3">
                      <label for="currency_symbol">{{ t("Currency Symbol") }}</label>
                      <InputText
                        id="currency_symbol"
                        v-model="financeCurrencySymbol"
                        v-bind="financeCurrencySymbolAttrs"
                        autocomplete="off"
                        :class="{ 'p-invalid': financeSubmitCount > 0 && !!financeErrors.currency_symbol }"
                      />
                      <small v-if="financeSubmitCount > 0 && financeErrors.currency_symbol" class="text-red-400 dark:text-red-300">
                        {{ financeErrors.currency_symbol }}
                      </small>
                    </div>
                  </div>

                  <div class="col-span-12 md:col-span-6">
                    <div class="flex flex-col gap-2 mb-3">
                      <label for="decimal_precision">{{ t("Decimal Precision") }}</label>
                      <InputNumber
                        id="decimal_precision"
                        v-model="financeDecimalPrecision"
                        v-bind="financeDecimalPrecisionAttrs"
                        :min="0"
                        :max="6"
                        class="w-full"
                        :class="{ 'p-invalid': financeSubmitCount > 0 && !!financeErrors.decimal_precision }"
                      />
                      <small class="text-gray-500">{{ t("Applied to all monetary values") }}</small>
                      <small v-if="financeSubmitCount > 0 && financeErrors.decimal_precision" class="text-red-400 dark:text-red-300">
                        {{ financeErrors.decimal_precision }}
                      </small>
                    </div>
                  </div>
                </div>

                <div class="flex justify-end mt-4">
                  <Button type="submit" :label="t('Save')" icon="fa fa-save" class="uppercase" raised :loading="financeIsSubmitting" />
                </div>
              </form>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </template>
    </Card>
  </div>
</template>
