<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Toast,
  Button,
  InputText,
  IconField,
  InputIcon,
  Select,
  Popover,
  Badge,
  ConfirmDialog,
  useToast,
  useConfirm,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import ItemEditor from "@pages/MeasurementUnits/List/ItemEditor.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { MeasurementUnitResponse } from "@/Types/measurement-unit-types";
import { useI18n } from "vue-i18n";

// Set Layout
defineOptions({ layout: AppLayout });
// Props from Inertia
const props = defineProps<{
  measurementUnits: {
    data: MeasurementUnitResponse[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
  filters: {
    filter?: string | null;
    status?: string;
    order_by?: string;
    order_direction?: string;
    per_page?: number;
  };
}>();
// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Local filter/sort state
const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "all");
const sortField = ref(props.filters.order_by ?? "name");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);
const filterPopover = ref();

const statusOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Active"), value: "active" },
  { label: t("Archived"), value: "archived" },
]);

const hasActiveFilters = computed(
  () => status.value !== "all" || filter.value !== "",
);

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== "all") count++;
  if (filter.value !== "") count++;
  return count;
});

// Formatted rows
const measurementUnits = computed(() =>
  props.measurementUnits.data.map((item) => ({
    ...item,
    created_at: useDatetimeFormatter(item.created_at),
    updated_at: useDatetimeFormatter(item.updated_at),
  })),
);

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("measurement-units"), {
    data: {
      filter: filter.value,
      status: status.value === "all" ? null : status.value,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      ...overrides,
    },
    preserveState: true,
    replace: true,
  });
}

function resetFilters() {
  status.value = "all";
  filter.value = "";
  applyFilters();
}

let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, () => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => applyFilters(), 300);
});

watch(status, () => applyFilters());

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "name";
  sortOrder.value = event.sortOrder ?? 1;
  applyFilters();
};

// Create/Edit Measurement Units
let showModal = ref(false);
let selectedMeasurementUnit = ref<MeasurementUnitResponse | null>(null);

const addMeasurementUnit = () => {
  selectedMeasurementUnit.value = null;
  showModal.value = true;
};

const editMeasurementUnit = (measurementUnit: MeasurementUnitResponse) => {
  selectedMeasurementUnit.value = measurementUnit;
  showModal.value = true;
};

// Delete Measurement Unit
const deleteMeasurementUnit = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to delete this measurement unit?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("measurement-units.destroy", id), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Measurement Unit deleted successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not delete measurement unit"), life: 3000 });
        },
      });
    },
  });
};

// Restore Measurement Unit
const restoreMeasurementUnit = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to restore this measurement unit?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Restore"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.put(route("measurement-units.restore", id), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Measurement Unit restored successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not restore measurement unit"), life: 3000 });
        },
      });
    },
  });
};
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Measurement Units") }}
      </h2>
      <Button
        v-can="'measurement_unit.create'"
        :label="t('Add Measurement Unit')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="addMeasurementUnit"
      />
    </div>
    <ConfirmDialog />
    <Toast />
    <Card>
      <template #content>
        <DataTable
          :value="measurementUnits"
          resizable-columns
          lazy
          :total-records="props.measurementUnits.meta.total"
          :rows="props.measurementUnits.meta.per_page"
          :first="(props.measurementUnits.meta.current_page - 1) * props.measurementUnits.meta.per_page"
          paginator
          sort-field="name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No measurement units found") }}</span>
            </div>
          </template>
          <template #header>
            <div class="flex items-center gap-2">
              <Button
                type="button"
                icon="fa fa-filter"
                :label="t('Filters')"
                :severity="hasActiveFilters ? 'primary' : 'secondary'"
                outlined
                :pt="{ label: { class: 'hidden sm:inline' } }"
                @click="filterPopover.toggle($event)"
              />
              <Badge v-if="activeFilterCount > 0" :value="activeFilterCount" severity="primary" />
              <IconField icon-position="left" class="flex-1 sm:flex-none sm:w-80 sm:ml-auto">
                <InputIcon class="fa fa-search" />
                <InputText v-model="filter" :placeholder="t('Search')" class="w-full" />
              </IconField>
            </div>

            <Popover ref="filterPopover">
              <div class="flex flex-col gap-4 p-4 min-w-72">
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Status") }}</label>
                  <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div class="flex justify-end pt-2 border-t border-surface-200 dark:border-surface-700">
                  <Button
                    type="button"
                    :label="t('Clear')"
                    icon="fa fa-times"
                    severity="secondary"
                    text
                    size="small"
                    :disabled="!hasActiveFilters"
                    @click="resetFilters"
                  />
                </div>
              </div>
            </Popover>
          </template>
          <Column field="name" :header="t('Name')" sortable />
          <Column field="abbreviation" :header="t('Abbreviation')" sortable />
          <Column field="created_at" :header="t('Created At')" sortable />
          <Column field="updated_at" :header="t('Updated At')" sortable />
          <Column field="actions" :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="row">
              <div class="flex justify-center gap-2">
                <Button
                  v-if="!row.data.deleted_at"
                  v-can="'measurement_unit.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="large"
                  rounded
                  @click="editMeasurementUnit(row.data)"
                />
                <Button
                  v-if="row.data.deleted_at"
                  v-can="'measurement_unit.restore'"
                  v-tooltip.top="t('Restore')"
                  icon="fa fa-trash-arrow-up"
                  text
                  size="large"
                  rounded
                  @click="restoreMeasurementUnit(row.data.id)"
                />
                <Button
                  v-if="!row.data.deleted_at"
                  v-can="'measurement_unit.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  size="large"
                  rounded
                  @click="deleteMeasurementUnit(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <ItemEditor v-model:show-modal="showModal" :measurement-unit="selectedMeasurementUnit" />
  </div>
</template>
