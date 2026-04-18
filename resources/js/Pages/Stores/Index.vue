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
  SelectButton,
  Tag,
  useToast,
  useConfirm,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { StoreResponse } from "@/Types/store-types";

// Layout
defineOptions({ layout: AppLayout });
// Props from Inertia
const props = defineProps<{
  stores: {
    data: StoreResponse[];
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
const status = ref(props.filters.status ?? "active");
const sortField = ref(props.filters.order_by ?? "name");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);

const statusOptions = computed(() => [
  { label: t("Active"), value: "active" },
  { label: t("Inactive"), value: "inactive" },
  { label: t("Archived"), value: "archived" },
]);

// Formatted rows
const stores = computed(() => props.stores.data);

// Debounced filter watch
let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, (val) => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    router.visit(route("stores"), {
      data: {
        filter: val,
        status: status.value,
        order_by: sortField.value,
        order_direction: sortOrder.value === -1 ? "desc" : "asc",
      },
      preserveState: true,
      replace: true,
    });
  }, 300);
});

watch(status, (val) => {
  router.visit(route("stores"), {
    data: {
      status: val,
      filter: filter.value,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
    },
    preserveState: true,
    replace: true,
  });
});

const onPage = (event: DataTablePageEvent) => {
  router.visit(route("stores"), {
    data: {
      page: event.page + 1,
      per_page: event.rows,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      filter: filter.value,
      status: status.value,
    },
    preserveState: true,
    replace: true,
  });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "name";
  sortOrder.value = event.sortOrder ?? 1;
  router.visit(route("stores"), {
    data: {
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      filter: filter.value,
      status: status.value,
    },
    preserveState: true,
    replace: true,
  });
};

// Row actions
const deleteStore = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to delete this store?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("stores.destroy", id), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Store deleted successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not delete store"), life: 3000 });
        },
      });
    },
  });
};

const restoreStore = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to restore this store?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Restore"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.put(route("stores.restore", id), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Store restored successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not restore store"), life: 3000 });
        },
      });
    },
  });
};
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Stores") }}
      </h2>
      <Button
        v-can="'store.create'"
        :label="t('Add Store')"
        class="ml-2 uppercase"
        icon="fa fa-add"
        raised
        @click="router.visit(route('stores.create'))"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="stores"
          resizable-columns
          lazy
          :total-records="props.stores.meta.total"
          :rows="props.stores.meta.per_page"
          :first="(props.stores.meta.current_page - 1) * props.stores.meta.per_page"
          paginator
          sort-field="name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ t("No stores found") }}
          </template>
          <template #header>
            <div class="grid grid-cols-12 gap-2">
              <div class="md:col-span-6 col-span-12 flex md:justify-start justify-center">
                <SelectButton v-model="status" :allow-empty="false" :options="statusOptions" option-label="label" option-value="value" />
              </div>
              <div
                class="flex xl:col-span-3 xl:col-start-10 lg:col-span-4 lg:col-start-9 md:col-span-6 md:col-start-7 col-span-12 md:justify-end justify-center"
              >
                <IconField icon-position="left" class="w-full">
                  <InputIcon class="fa fa-search" />
                  <InputText v-model="filter" :placeholder="t('Search')" class="w-full" />
                </IconField>
              </div>
            </div>
          </template>
          <Column field="name" :header="t('Name')" sortable>
            <template #body="{ data }">
              <div class="flex items-center">
                <div class="flex flex-col items-start">
                  <span class="text-sm font-bold">{{ data.name }}</span>
                  <span class="text-xs text-gray-500">{{ data.code }}</span>
                </div>
              </div>
            </template>
          </Column>
          <Column field="address" :header="t('Address')" sortable>
            <template #body="{ data }">
              <div class="flex items-center">
                {{ data.address ?? "—" }}
              </div>
            </template>
          </Column>
          <Column field="phone" :header="t('Phone')">
            <template #body="{ data }">
              {{ data.phone ?? "—" }}
            </template>
          </Column>
          <Column field="status" :header="t('Status')" sortable>
            <template #body="{ data }">
              <Tag v-if="data.status === 'active'" severity="success">{{ t("Active") }}</Tag>
              <Tag v-else-if="data.status === 'inactive'" severity="warn">{{ t("Inactive") }}</Tag>
              <Tag v-else severity="danger">{{ t("Archived") }}</Tag>
            </template>
          </Column>
          <Column field="users_count" :header="t('Users')" sortable>
            <template #body="{ data }">
              {{ data.users_count ?? 0 }}
            </template>
          </Column>
          <Column field="actions" :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="row">
              <div class="flex justify-center gap-2">
                <Button
                  v-show="row.data.deleted_at === null"
                  v-can="'store.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="large"
                  rounded
                  @click="router.visit(route('stores.edit', row.data.id))"
                />
                <Button
                  v-show="row.data.deleted_at !== null"
                  v-can="'store.edit'"
                  v-tooltip.top="t('Restore')"
                  icon="fa fa-trash-arrow-up"
                  text
                  size="large"
                  rounded
                  @click="restoreStore(row.data.id)"
                />
                <Button
                  v-show="row.data.deleted_at === null"
                  v-can="'store.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  size="large"
                  rounded
                  @click="deleteStore(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
