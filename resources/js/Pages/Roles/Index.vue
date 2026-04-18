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
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { useI18n } from "vue-i18n";
import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { RoleResponse } from "@/Types/role-types";

// Layout
defineOptions({
  layout: AppLayout,
});
// Props from Inertia
const props = defineProps<{
  roles: {
    data: RoleResponse[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
  filters: {
    filter?: string;
    order_by?: string;
    order_direction?: string;
    per_page?: number;
  };
}>();
// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Local filter state
const filter = ref(props.filters.filter ?? "");
const sortField = ref(props.filters.order_by ?? "name");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);

// Debounced filter watch
let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, (val) => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    router.visit(route("roles"), {
      data: {
        filter: val,
        order_by: sortField.value,
        order_direction: sortOrder.value === -1 ? "desc" : "asc",
      },
      preserveState: true,
      replace: true,
    });
  }, 300);
});

// Formatted rows
const roles = computed(() =>
  props.roles.data.map((item: RoleResponse) => ({
    ...item,
    created_at: useDatetimeFormatter(item.created_at),
    updated_at: useDatetimeFormatter(item.updated_at),
  })),
);

const onPage = (event: DataTablePageEvent) => {
  router.visit(route("roles"), {
    data: {
      page: event.page + 1,
      per_page: event.rows,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      filter: filter.value,
    },
    preserveState: true,
    replace: true,
  });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "name";
  sortOrder.value = event.sortOrder ?? 1;
  router.visit(route("roles"), {
    data: {
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      filter: filter.value,
    },
    preserveState: true,
    replace: true,
  });
};

// Delete Role
const deleteRole = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to delete this role?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("roles.destroy", id), {
        onSuccess: () => {
          toast.add({
            severity: "success",
            summary: t("Success"),
            detail: t("Role deleted successfully"),
            life: 3000,
          });
        },
        onError: () => {
          toast.add({
            severity: "error",
            summary: t("Error"),
            detail: t("Could not delete role"),
            life: 3000,
          });
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
        {{ t("Roles") }}
      </h2>
      <Button
        v-can="'role.create'"
        :label="t('add role')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="router.visit(route('roles.create'))"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="roles"
          resizable-columns
          lazy
          :total-records="props.roles.meta.total"
          :rows="props.roles.meta.per_page"
          :first="(props.roles.meta.current_page - 1) * props.roles.meta.per_page"
          paginator
          sort-field="name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ t("No roles found") }}
          </template>
          <template #header>
            <div class="grid grid-cols-12 gap-2">
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
          <Column field="name" :header="t('Name')" sortable />
          <Column field="created_at" :header="t('Created At')" sortable />
          <Column field="updated_at" :header="t('Updated At')" sortable />
          <Column field="actions" :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="row">
              <div class="flex justify-center gap-2">
                <Button
                  v-tooltip.top="t('Edit')"
                  v-can="'role.edit'"
                  icon="fa fa-edit"
                  text
                  size="large"
                  rounded
                  @click="router.visit(route('roles.edit', row.data.id))"
                />
                <Button
                  v-tooltip.top="t('Delete')"
                  v-can="'role.delete'"
                  icon="fa fa-trash"
                  text
                  size="large"
                  rounded
                  @click="deleteRole(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

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
.p-datatable .p-datatable-tbody > tr.no-expander > td .p-row-toggler {
  display: none;
}
</style>
