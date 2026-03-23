<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Users") }}
      </h2>
      <Button
        v-can="'user.create'"
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
          :total-records="props.users.meta.total"
          :rows="props.users.meta.per_page"
          :first="(props.users.meta.current_page - 1) * props.users.meta.per_page"
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
            <div class="grid grid-cols-12 gap-2">
              <div class="md:col-span-6 col-span-12 flex md:justify-start justify-center">
                <SelectButton
                  v-model="status"
                  :allow-empty="false"
                  :options="statusOptions"
                  option-label="label"
                  option-value="value"
                />
              </div>
              <div
                class="
                  flex
                  xl:col-span-3 xl:col-start-10
                  lg:col-span-4 lg:col-start-9
                  md:col-span-6 md:col-start-7
                  col-span-12
                  md:justify-end justify-center
                "
              >
                <IconField icon-position="left" class="w-full">
                  <InputIcon class="fa fa-search" />
                  <InputText
                    v-model="filter"
                    :placeholder="t('Search')"
                    class="w-full"
                  />
                </IconField>
              </div>
            </div>
          </template>
          <Column field="first_name" :header="t('First Name')" sortable />
          <Column field="last_name" :header="t('Last Name')" sortable />
          <Column field="username" :header="t('Username')" sortable />
          <Column field="roles" :header="t('Roles')">
            <template #body="{ data }">
              <div style="height: 55px;" class="flex items-center">
                <Tag v-if="data.roles" severity="secondary">
                  {{ data.roles }}
                </Tag>
              </div>
            </template>
          </Column>
          <Column field="status" :header="t('Status')" sortable>
            <template #body="{ data }">
              <div style="height: 55px;" class="flex items-center">
                <Tag v-if="data.status === 'active'" severity="success">{{ t('Active') }}</Tag>
                <Tag v-else-if="data.status === 'inactive'" severity="warn">{{ t('Inactive') }}</Tag>
                <Tag v-else severity="danger">{{ t('Archived') }}</Tag>
              </div>
            </template>
          </Column>
          <Column field="created_at" :header="t('Created At')" sortable />
          <Column
            field="actions"
            :header="t('Actions')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="row">
              <div class="flex justify-center gap-2">
                <Button
                  v-show="row.data.status !== 'archived'"
                  v-can="'user.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="router.visit(route('users.edit', row.data.id))"
                />
                <Button
                  v-show="row.data.status === 'archived'"
                  v-can="'user.edit'"
                  v-tooltip.top="t('Restore')"
                  icon="fa fa-trash-arrow-up"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="restoreUser(row.data.id)"
                />
                <Button
                  v-show="row.data.status !== 'archived'"
                  v-can="'user.delete'"
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
  DataTablePageEvent,
  DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { useI18n } from "vue-i18n";
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { UserResponse } from "@/Types/user-types";

// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Layout
defineOptions({ layout: AppLayout });

// Props from Inertia
const props = defineProps<{
  users: {
    data: (UserResponse & { roles: string[] })[];
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

// Current user
const currentUser = usePage().props.auth.user as UserResponse;
const isCurrentUser = (id: number) => currentUser.id === id;

// Local filter/sort state
const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "all");
const sortField = ref(props.filters.order_by ?? "first_name");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);

const statusOptions = computed(() => [
  { label: t('All'), value: 'all' },
  { label: t('Active'), value: 'active' },
  { label: t('Inactive'), value: 'inactive' },
  { label: t('Archived'), value: 'archived' },
]);

// Formatted rows
const users = computed(() =>
  props.users.data.map((item) => ({
    ...item,
    roles: item.roles.join(', '),
    created_at: useDatetimeFormatter(item.created_at),
  }))
);

// Debounced filter watch
let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, (val) => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    router.visit(route("users"), {
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
  router.visit(route("users"), {
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
  router.visit(route("users"), {
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
  sortField.value = typeof event.sortField === "string" ? event.sortField : "first_name";
  sortOrder.value = event.sortOrder ?? 1;
  router.visit(route("users"), {
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
const deleteUser = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to delete this user?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("users.destroy", id), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("User deleted successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not delete user"), life: 3000 });
        },
      });
    },
  });
};

const restoreUser = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to restore this user?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Restore"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.put(route("users.restore", id), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("User restored successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not restore user"), life: 3000 });
        },
      });
    },
  });
};
</script>
