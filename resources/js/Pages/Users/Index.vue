<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Users") }}
      </h2>
      <Button
        v-can="'users-create'"
        :label="t('Add User')"
        class="ml-2 uppercase"
        icon="fa fa-add"
        raised
        @click="router.visit(route('users.create'))"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="users"
          resizable-columns
          lazy
          :total-records="pagination.total"
          :rows="pagination.perPage"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="first_name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ t('No users found') }}
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div
                class="
                  md:col-span-6
                  col-span-12
                  flex
                  md:justify-start
                  justify-center
                "
              >
                <SelectButton
                  v-model="status"
                  :allow-empty="false"
                  :options="[{
                    label: t('All'),
                    value: 'all',
                  }, {
                    label: t('Active'),
                    value: 'active',
                  }, {
                    label: t('Inactive'),
                    value: 'inactive',
                  }, {
                    label: t('Archived'),
                    value: 'archived',
                  }]"
                  option-label="label"
                  option-value="value"
                  aria-labelledby="basic"
                />
              </div>
              <div
                class="
                  flex
                  xl:col-span-3
                  xl:col-start-10
                  lg:col-span-4
                  lg:col-start-9
                  md:col-span-6
                  md:col-start-7
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
                    :placeholder="t('Search')"
                    class="w-full"
                  />
                </IconField>
              </div>
            </div>
          </template>
          <Column
            field="first_name"
            :header="t('First Name')"
            sortable
          />
          <Column
            field="last_name"
            :header="t('Last Name')"
            sortable
          />
          <Column
            field="username"
            :header="t('Username')"
            sortable
          />
          <Column
            field="role.name"
            :header="t('Roles')"
          >
            <template #body="{ data }">
              <div
                style="height: 55px;"
                class="flex items-center"
              >
                <Tag
                  v-if="data.roles.length > 0"
                  severity="secondary"
                >
                  {{ data.roles }}
                </Tag>
              </div>
            </template>
          </Column>
          <Column
            field="status"
            :header="t('Status')"
            sortable
          >
            <template #body="{ data }">
              <div
                style="height: 55px;"
                class="flex items-center"
              >
                <Tag
                  v-if="data.status === 'active'"
                  severity="success"
                >
                  {{ t('Active') }}
                </Tag>
                <Tag
                  v-else-if="data.status === 'inactive'"
                  severity="warn"
                >
                  {{ t('Inactive') }}
                </Tag>
                <Tag
                  v-else
                  severity="danger"
                >
                  {{ t('Archived') }}
                </Tag>
              </div>
            </template>
          </Column>
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
                  v-show="row.data.status !== 'archived'"
                  v-can="'users-edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editUser(row.data)"
                />
                <Button
                  v-show="row.data.status === 'archived'"
                  v-can="'users-edit'"
                  v-tooltip.top="t('Restore')"
                  icon="fa fa-trash-arrow-up"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="restoreUser(row.data)"
                />
                <Button
                  v-show="row.data.status !== 'archived'"
                  v-can="'users-delete'"
                  v-tooltip.top="t('Delete')"
                  :disabled="isCurrentUser(row.data.id)"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
                  size="sm"
                  :severity="isCurrentUser(row.data.id) ? 'secondary' : 'primary'"
                  @click="deleteUser(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup>
import {
  DataTable, Card, Column, useToast, useConfirm, Button, InputText, IconField, InputIcon, ConfirmDialog, SelectButton, Tag,
} from "primevue";
import { usePage, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import AppLayout from "../../Layouts/admin.vue";
import useDatetimeFormatter from "../../Composables/useDatetimeFormatter";

// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Layout
defineOptions({
  layout: AppLayout,
});

// List users
const pagination = ref({
  total: 0,
  first: 0,
  page: 1,
  perPage: 10,
  sortField: "first_name",
  sortOrder: 1,
  filter: "",
});

const currentUser = usePage().props.auth.user;
const isCurrentUser = (id) => currentUser.id === id;

let users = [];

const loading = ref(false);
const status = ref("all");

const fetchUsers = () => {
  loading.value = true;

  const params = new URLSearchParams();

  params.append("include", "roles");
  params.append("per_page", pagination.value.perPage);
  params.append("page", pagination.value.page);
  params.append("order_by", pagination.value.sortField);
  params.append("order_direction", pagination.value.sortOrder === -1 ? "desc" : "asc");

  if (status.value !== "all") {
    params.append("status", status.value);
  }
  if (pagination.value.filter) {
    params.append("filter", pagination.value.filter);
  }
  const url = `${route("api.users")}?${params.toString()}`;

  axios.get(url)
    .then((response) => {
      users = response.data.data.map((item) => ({
        ...item,
        created_at: useDatetimeFormatter(item.created_at),
        updated_at: useDatetimeFormatter(item.updated_at),
        roles: item.roles.map((role) => role.name).join(", "),
      }));
      pagination.value.total = response.data.meta.total;
      loading.value = false;
    })
    .catch((error) => {
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: error.response,
        life: 3000,
      });
      loading.value = false;
    });
};

watch(
  () => pagination.value.filter,
  () => {
    pagination.value.page = 1;
    fetchUsers();
  },
  {
    immediate: true,
  },
);

watch(
  status,
  () => {
    pagination.value.page = 1;
    fetchUsers();
  },
);

const onPage = (event) => {
  pagination.value.page = event.page + 1;
  pagination.value.perPage = event.rows;
  fetchUsers();
};
const onSort = (event) => {
  pagination.value.sortField = event.sortField;
  pagination.value.sortOrder = event.sortOrder;
  fetchUsers();
};

// Row actions
const editUser = (id) => {
  router.visit(route("users.edit", id));
};

const restoreUser = (id) => {
  confirm.require({
    message: t("Are you sure you want to restore this user?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Restore"),
    rejectClass: "p-button-secondary",
    accept: () => {
      axios.put(route("api.users.restore", id))
        .then(() => {
          toast.add({
            severity: "success",
            summary: t("Success"),
            detail: t("User restored successfully"),
            life: 3000,
          });
          fetchUsers();
        })
        .catch((error) => {
          toast.add({
            severity: "error",
            summary: t("Error"),
            detail: error.response.data.message,
            life: 3000,
          });
        });
    },
  });
};
const deleteUser = (id) => {
  confirm.require({
    message: t("Are you sure you want to delete this user?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      axios.delete(route("api.users.destroy", id))
        .then(() => {
          toast.add({
            severity: "success",
            summary: "Success",
            detail: t("User deleted successfully"),
            life: 3000,
          });
          fetchUsers();
        })
        .catch((error) => {
          toast.add({
            severity: "error",
            summary: t("Error"),
            detail: error.response.data.message,
            life: 3000,
          });
        });
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
