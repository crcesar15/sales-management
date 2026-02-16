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
          resizableColumns
          lazy
          :totalRecords="pagination.total"
          :rows="pagination.total"
          :first="pagination.first"
          :loading="loading"
          paginator
          sortField="name"
          :sortOrder="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ t('No roles found') }}
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div
                class="
                  flex
                  lg:col-span-3
                  lg:col-start-10
                  md:col-span-6
                  md:col-start-7
                  col-span-12
                  md:justify-content-end
                  justify-center
                "
              >
                <IconField
                  iconPosition="left"
                  class="w-full"
                >
                  <InputIcon class="fa fa-search" />
                  <InputText
                    v-model="pagination.filter"
                    :placeholder="t('Search')"
                    class="w-full"
                  />
                </IconField>
              </div>
            </div>
          </template>
          <Column
            field="name"
            :header="t('Name')"
            sortable
          />
          <Column
            field="created_at"
            :header="t('Created At')"
            sortable
          />
          <Column
            field="updated_at"
            :header="t('Updated At')"
            sortable
          />
          <Column
            field="actions"
            :header="t('Actions')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="row">
              <div class="flex justify-center gap-2">
                <Button
                  v-tooltip.top="t('Edit')"
                  v-can="'role.edit'"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="router.visit(route('roles.edit', row.data.id))"
                />
                <Button
                  v-tooltip.top="t('Delete')"
                  v-can="'role.delete'"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
                  size="sm"
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
  DataTablePageEvent,
  DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { useI18n } from "vue-i18n";
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { RoleResponse } from "@/Types/role-types";
import { useRoleClient } from "@/Composables/useRoleClient";

// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Layout
defineOptions({
  layout: AppLayout,
});

// List roles
const pagination = ref({
  total: 0,
  first: 0,
  page: 1,
  perPage: 10,
  sortField: "name",
  sortOrder: 1,
  filter: "",
});

const { loading, fetchRolesApi } = useRoleClient();

const roles = ref<RoleResponse[]>();

const fetchRoles = async () => {
  const params = new URLSearchParams();

  params.append("per_page", pagination.value.perPage.toString());
  params.append("page", pagination.value.page.toString());
  params.append("order_by", pagination.value.sortField);
  params.append("order_direction", pagination.value.sortOrder === -1 ? "desc" : "asc");

  if (pagination.value.filter) {
    params.append("filter", pagination.value.filter);
  }

  try {
    const response = await fetchRolesApi(params.toString());
    roles.value = response.data.data.map((item:RoleResponse) => ({
      ...item,
      created_at: useDatetimeFormatter(item.created_at),
      updated_at: useDatetimeFormatter(item.updated_at),
    }));
    pagination.value.total = response.data.meta.total;
  } catch (error:any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: error.response.data.message,
      life: 3000,
    });
  }
};

const onPage = (event: DataTablePageEvent) => {
  pagination.value.page = event.page + 1;
  pagination.value.perPage = event.rows;
  fetchRoles();
};
const onSort = (event: DataTableSortEvent) => {
  pagination.value.sortField = typeof event.sortField === "string" ? event.sortField : "name";
  pagination.value.sortOrder = event.sortOrder ?? 0;
  fetchRoles();
};

watch(
  () => pagination.value.filter,
  () => {
    pagination.value.page = 1;
    fetchRoles();
  },
  {
    immediate: true,
    deep: true,
  },
);

// Delete Role
const deleteRole = (id:number) => {
  const { destroyRoleApi } = useRoleClient();

  confirm.require({
    message: t("Are you sure you want to delete this role?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: async () => {
      try {
        await destroyRoleApi(id);
        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("Role deleted successfully"),
          life: 3000,
        });
        fetchRoles();
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: error.response.data.message,
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
