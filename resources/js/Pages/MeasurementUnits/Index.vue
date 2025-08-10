<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Measurement Units") }}
      </h2>
      <p-button
        v-can="'measurement-units-create'"
        :label="$t('Add Measurement Unit')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="addMeasurementUnit"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="measurementUnits"
          resizable-columns
          lazy
          :total-records="pagination.total"
          :rows="pagination.perPage"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ $t('No measurement units found') }}
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div
                class="
                  flex
                  lg:col-span-3
                  lg:col-start-10
                  md:col-span-4
                  md:col-start-9
                  col-span-12
                  md:justify-end
                  justify-center
                "
              >
                <IconField
                  icon-position="left"
                  class="w-full"
                >
                  <InputIcon class="fa fa-search" />
                  <InputText
                    v-model="pagination.filter"
                    :placeholder="$t('Search')"
                    class="w-full"
                  />
                </IconField>
              </div>
            </div>
          </template>
          <Column
            field="name"
            :header="$t('Name')"
            sortable
          />
          <Column
            field="abbreviation"
            :header="$t('Abbreviation')"
            sortable
          />
          <Column
            field="created_at"
            :header="$t('Created At')"
            sortable
          />
          <Column
            field="updated_at"
            :header="$t('Updated At')"
            sortable
          />
          <Column
            field="actions"
            :header="$t('Actions')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="row">
              <div class="flex justify-center gap-2">
                <Button
                  v-can="'measurement-units-edit'"
                  v-tooltip.top="$t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editMeasurementUnit(row.data)"
                />
                <Button
                  v-can="'measurement-units-delete'"
                  v-tooltip.top="$t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="deleteMeasurementUnit(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <ItemEditor
      v-model:show-modal="showModal"
      :measurement-unit="selectedMeasurementUnit"
      @submitted="saveMeasurementUnit"
    />
  </div>
</template>

<script setup lang="ts">

import {
  DataTable,
  Card,
  Column,
  Button,
  InputText,
  IconField,
  InputIcon,
  ConfirmDialog,
  useToast,
  useConfirm,
  DataTableSortEvent,
  DataTablePageEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import ItemEditor from "@pages/MeasurementUnits/List/ItemEditor.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";

import { useI18n } from "vue-i18n";
import { MeasurementUnit } from '@app-types/measurement-unit-types';
import { ref, watch } from "vue";
import { useMeasurementUnitClient } from "@/Composables/useMeasurementUnitClient";

// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Layout
defineOptions({
  layout: AppLayout,
});

// List brands
const pagination = ref({
  total: 0,
  first: 0,
  page: 1,
  perPage: 10,
  sortField: "name",
  sortOrder: 1,
  filter: "",
});

const measurementUnits = ref<MeasurementUnit[]>([]);

const {fetchMeasurementUnitsApi, loading} = useMeasurementUnitClient();

const fetchMeasurementUnits = async () => {
  try {
      const params = new URLSearchParams();

      params.append("per_page", pagination.value.perPage.toString());
      params.append("page", pagination.value.page.toString());
      params.append("order_by", pagination.value.sortField);
      params.append("order_direction", pagination.value.sortOrder === -1 ? "desc" : "asc");

      if (pagination.value.filter) {
        params.append("filter", pagination.value.filter);
      }

      const response = await fetchMeasurementUnitsApi(params.toString());
      measurementUnits.value = response.data.data.map((item: MeasurementUnit) => ({
        ...item,
        created_at: useDatetimeFormatter(item.created_at),
        updated_at: useDatetimeFormatter(item.updated_at),
      }));
      pagination.value.total = response.data.meta.total;
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error?.response?.data?.message || error),
      life: 3000,
    });
  }
}

const onPage = (event:DataTablePageEvent ) => {
  pagination.value.page = event.page + 1;
  pagination.value.perPage = event.rows;
  fetchMeasurementUnits();
};
const onSort = (event:DataTableSortEvent) => {
  pagination.value.sortField = typeof event.sortField === 'string' ? event.sortField : 'name';
  pagination.value.sortOrder = event.sortOrder ?? 0;
  fetchMeasurementUnits();
};

watch(
  () => pagination.value.filter,
  () => {
    pagination.value.page = 1;
    fetchMeasurementUnits();
  },
  {
    immediate: true,
    deep: true,
  },
);

// Add/Edit Measurement Unit
const showModal = ref(false);
const selectedMeasurementUnit = ref<MeasurementUnit | Pick<MeasurementUnit, 'id' | 'name' | 'abbreviation'> | null>(null);

const addMeasurementUnit = () => {
  showModal.value = true;
  selectedMeasurementUnit.value = null;
};

const editMeasurementUnit = (measurementUnit: MeasurementUnit) => {
  showModal.value = true;
  selectedMeasurementUnit.value = measurementUnit;
};

const createMeasurementUnit = async (measurementUnit: Pick<MeasurementUnit, 'id' | 'name' | 'abbreviation'>) => {
  const {storeMeasurementUnitApi} = useMeasurementUnitClient();

  try {
    await storeMeasurementUnitApi(measurementUnit);

    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("Measurement Unit created successfully"),
      life: 3000,
    });

    fetchMeasurementUnits();
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error?.response?.data?.message || error),
      life: 3000,
    });
  }
};

const updateMeasurementUnit = async (id: number, measurementUnit: MeasurementUnit) => {
  const {updateMeasurementUnitApi} = useMeasurementUnitClient();

  try {
    await updateMeasurementUnitApi(id, measurementUnit);

    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("Measurement Unit updated successfully"),
      life: 3000,
    });

    fetchMeasurementUnits();
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error?.response?.data?.message || error),
      life: 3000,
    });
  }
};

const saveMeasurementUnit = (measurementUnit: MeasurementUnit | Pick<MeasurementUnit, 'id' | 'name' | 'abbreviation'>) => {
  if (measurementUnit?.id !== undefined) {
    updateMeasurementUnit(measurementUnit.id, measurementUnit as MeasurementUnit);
  } else {
    createMeasurementUnit(measurementUnit);
  }
};

const deleteMeasurementUnit = async (id: number) => {
  const {destroyMeasurementUnitApi} = useMeasurementUnitClient();

  confirm.require({
    message: t("Are you sure you want to delete this measurement unit?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: async () => {
      try {
        await destroyMeasurementUnitApi(id);

        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("Measurement Unit deleted successfully"),
          life: 3000,
        });

        fetchMeasurementUnits();
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t(error?.response?.data?.message || error),
          life: 3000,
        });
      }
    },
  });
};
</script>

<style>
.sortable-column [data-pc-section="sort"] {
  padding-left: 0.2rem;
}

.sortable-column [data-pc-section="headercontent"] {
  display: flex;
  align-items: center;
}
.sortable-column th:hover {
  cursor: pointer;
  background-color: #ccc;
}
.p-datatable .p-datatable-tbody>tr.no-expander>td .p-row-toggler {
  display: none;
}
</style>
