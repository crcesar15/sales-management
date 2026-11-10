<script setup lang="ts">
import { Button, DatePicker, Select } from "primevue";
import { computed, watch } from "vue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string, number } from "yup";
import { useI18n } from "vue-i18n";
import { useDatetimeFormatter } from "@/Composables/useDatetimeFormatter";
import type { DashboardFilters, DashboardStore } from "@/Types/dashboard-types";

const props = defineProps<{ filters: DashboardFilters; stores: DashboardStore[]; loading: boolean; errors: Record<string, string> }>();
const emit = defineEmits<{ apply: [filters: DashboardFilters]; refresh: [] }>();
const { t } = useI18n();
const { parseDateOnly, toDateOnly, datePickerFormat } = useDatetimeFormatter();
const { values, setFieldValue, handleSubmit, errors, resetForm } = useForm({
  initialValues: { ...props.filters },
  validationSchema: toTypedSchema(
    object({
      store_id: number().nullable().defined(),
      preset: string().oneOf(["today", "last_7_days", "last_30_days", "month_to_date", "custom"]).required(),
      comparison: string().oneOf(["automatic", "off"]).required(),
      from: string().when("preset", { is: "custom", then: (schema) => schema.required() }),
      to: string().when("preset", { is: "custom", then: (schema) => schema.required() }),
    }),
  ),
});
watch(
  () => props.filters,
  (filters) => resetForm({ values: { ...filters } }),
);
const storeOptions = computed(() => [
  { label: t("All permitted stores"), value: null },
  ...props.stores.map((store) => ({
    label: `${store.name}${store.archived ? ` · ${t("Archived")}` : ""}`,
    value: store.id,
  })),
]);
const periods = computed(() => [
  { label: t("Today"), value: "today" },
  { label: t("Last 7 days"), value: "last_7_days" },
  { label: t("Last 30 days"), value: "last_30_days" },
  { label: t("Month to date"), value: "month_to_date" },
  { label: t("Custom range"), value: "custom" },
]);
const comparisons = computed(() => [
  { label: t("Compare automatically"), value: "automatic" },
  { label: t("No comparison"), value: "off" },
]);
const submit = handleSubmit((filters) => emit("apply", { ...props.filters, ...filters } as DashboardFilters));
</script>

<template>
  <form class="flex flex-col gap-3" @submit.prevent="submit">
    <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(180px,1fr)_minmax(170px,1fr)_minmax(190px,1fr)_auto]">
      <div class="flex min-w-0 flex-col gap-1">
        <label id="dashboard-store-label" for="dashboard-store" class="text-sm font-medium">{{ t("Store") }}</label>
        <Select
          input-id="dashboard-store"
          aria-labelledby="dashboard-store-label"
          :model-value="values.store_id"
          :options="storeOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('All permitted stores')"
          class="w-full"
          @update:model-value="setFieldValue('store_id', $event)"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label id="dashboard-period-label" for="dashboard-period" class="text-sm font-medium">{{ t("Reporting period") }}</label>
        <Select
          input-id="dashboard-period"
          aria-labelledby="dashboard-period-label"
          :model-value="values.preset"
          :options="periods"
          option-label="label"
          option-value="value"
          @update:model-value="setFieldValue('preset', $event)"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label id="dashboard-comparison-label" for="dashboard-comparison" class="text-sm font-medium">{{ t("Comparison") }}</label>
        <Select
          input-id="dashboard-comparison"
          aria-labelledby="dashboard-comparison-label"
          :model-value="values.comparison"
          :options="comparisons"
          option-label="label"
          option-value="value"
          @update:model-value="setFieldValue('comparison', $event)"
        />
      </div>
      <div class="flex gap-2">
        <Button type="submit" :label="t('Apply')" :disabled="loading" />
        <Button
          type="button"
          icon="fa fa-rotate"
          :aria-label="t('Refresh dashboard')"
          severity="secondary"
          outlined
          :loading="loading"
          @click="emit('refresh')"
        />
      </div>
    </div>
    <div v-if="values.preset === 'custom'" class="grid max-w-xl grid-cols-1 gap-3 sm:grid-cols-2">
      <div class="flex flex-col gap-1">
        <label for="dashboard-from" class="text-sm font-medium">{{ t("From") }}</label>
        <DatePicker
          input-id="dashboard-from"
          :model-value="parseDateOnly(values.from ?? null)"
          :date-format="datePickerFormat"
          show-icon
          :invalid="!!errors.from || !!props.errors.from"
          @update:model-value="setFieldValue('from', $event instanceof Date ? (toDateOnly($event) ?? '') : '')"
        />
        <small v-if="errors.from" class="text-red-600 dark:text-red-300">{{ errors.from }}</small>
      </div>
      <div class="flex flex-col gap-1">
        <label for="dashboard-to" class="text-sm font-medium">{{ t("To") }}</label>
        <DatePicker
          input-id="dashboard-to"
          :model-value="parseDateOnly(values.to ?? null)"
          :date-format="datePickerFormat"
          show-icon
          :invalid="!!errors.to || !!props.errors.to"
          @update:model-value="setFieldValue('to', $event instanceof Date ? (toDateOnly($event) ?? '') : '')"
        />
        <small v-if="errors.to" class="text-red-600 dark:text-red-300">{{ errors.to }}</small>
      </div>
    </div>
    <p v-for="(error, field) in props.errors" :key="field" role="alert" class="m-0 text-sm text-red-600 dark:text-red-300">
      {{ t(error) }}
    </p>
  </form>
</template>
