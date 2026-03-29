<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <Button
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="router.visit(route('roles'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0 capitalize">
          {{ t('edit role') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <Button
          icon="fa fa-save"
          :label="t('Save')"
          class="uppercase"
          raised
          :loading="isSubmitting"
          @click="submit()"
        />
      </div>
    </div>
    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="name">{{ t('Name') }}</label>
                  <InputText
                    id="name"
                    v-model="name"
                    v-bind="nameAttrs"
                    autocomplete="off"
                    :class="{'p-invalid': errors.name}"
                  />
                  <small
                    v-if="errors.name"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ errors.name }}
                  </small>
                </div>
              </div>
            </div>
          </template>
        </Card>
        <PermissionMatrix
          :matrix="filteredMatrix"
          :search-query="searchQuery"
          :total-permissions="totalPermissions"
          :total-enabled="totalEnabled"
          :is-all-selected="isAllSelected"
          :is-category-all-selected="isCategoryAllSelected"
          :get-category-enabled-count="getCategoryEnabledCount"
          @update:search-query="searchQuery = $event"
          @toggle-permission="togglePermission"
          @toggle-category-all="toggleCategoryAll"
          @toggle-all="toggleAll"
        />
      </div>
      <div class="md:col-span-4 col-span-12">
        <Card class="mb-4">
          <template #title>
            <div class="flex items-center justify-between">
              <span>{{ t('Users') }}</span>
              <Badge :value="assignedUsers.length" severity="secondary" />
            </div>
          </template>
          <template #content>
            <AutoComplete
              v-model="userSearch"
              :suggestions="filteredUsers"
              option-label="full_name"
              :placeholder="t('Search users...')"
              force-selection
              fluid
              class="mb-3"
              @complete="onSearchUsers"
              @option-select="onAddUser"
            >
              <template #option="{ option }">
                <div class="flex items-center gap-2">
                  <Avatar
                    :label="option.full_name.charAt(0).toUpperCase()"
                    size="small"
                    shape="circle"
                  />
                  <div class="font-medium">{{ option.full_name }}</div>
                </div>
              </template>
            </AutoComplete>

            <div v-if="assignedUsers.length === 0" class="text-center text-surface-400 py-4 text-sm">
              {{ t('No users assigned yet') }}
            </div>

            <div
              v-for="user in assignedUsers"
              :key="user.id"
              class="flex items-center gap-3 p-2 mb-2 rounded-lg bg-surface-50 dark:bg-surface-800"
            >
              <Avatar
                :label="user.full_name.charAt(0).toUpperCase()"
                shape="circle"
              />
              <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ user.full_name }}</div>
              </div>
              <Button
                icon="fa fa-trash"
                text
                rounded
                severity="primary"
                size="small"
                @click="removeUser(user.id)"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Card,
  InputText,
  AutoComplete,
  Avatar,
  Badge,
  useToast,
  Button
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string } from "yup";
import { ref, nextTick } from "vue";
import { RoleResponse } from "@/Types/role-types";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

import PermissionMatrix from "@/Components/PermissionMatrix.vue";
import { usePermissions } from "@/Composables/usePermissions";

// Set composables
const toast = useToast();
const { t } = useI18n();

// Layout
defineOptions({
  layout: AppLayout
});

// Props from Inertia
interface SimpleUser { id: number; full_name: string; email: string }

const props = defineProps<{
  role: RoleResponse;
  permissions: string[];
  availablePermissions: { id: number; name: string; category: string }[];
  availableUsers: SimpleUser[];
  assignedUsers: SimpleUser[];
}>();

// VeeValidate + Yup schema
const schema = toTypedSchema(
  object({
    name: string().required().max(255),
  })
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors } = useForm({
  validationSchema: schema,
  initialValues: {
    name: props.role.name,
  },
});

const [name, nameAttrs] = defineField("name");

// Permissions
const {
  filteredMatrix,
  searchQuery,
  enabledPermissionNames,
  totalPermissions,
  totalEnabled,
  togglePermission,
  toggleCategoryAll,
  isCategoryAllSelected,
  getCategoryEnabledCount,
  toggleAll,
  isAllSelected,
} = usePermissions(props.availablePermissions, props.permissions);

// User assignment
const assignedUsers = ref<SimpleUser[]>([...props.assignedUsers]);
const userSearch = ref<SimpleUser | null>(null);
const filteredUsers = ref<SimpleUser[]>([]);

const onSearchUsers = (event: { query: string }) => {
  const q = event.query.toLowerCase();
  filteredUsers.value = props.availableUsers.filter(
    (u) => !assignedUsers.value.find((a) => a.id === u.id) &&
      (u.full_name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q))
  );
};

const onAddUser = async (event: { value: SimpleUser }) => {
  if (!assignedUsers.value.find((u) => u.id === event.value.id)) {
    assignedUsers.value.push(event.value);
  }
  await nextTick();
  userSearch.value = null;
};

const removeUser = (id: number) => {
  assignedUsers.value = assignedUsers.value.filter((u) => u.id !== id);
};

const submit = handleSubmit((values) => {
  router.put(
    route("roles.update", props.role.id),
    {
      name: values.name,
      permissions: enabledPermissionNames.value,
      users: assignedUsers.value.map((u) => u.id),
    },
    {
      onSuccess: () => {
        router.visit(route("roles"));
      },
      onError: (errs) => {
        setErrors(errs);
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t(Object.values(errs)[0] ?? "An error occurred"),
          life: 3000,
        });
      },
    }
  );
});
</script>
