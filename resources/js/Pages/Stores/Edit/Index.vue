<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <Button icon="fa fa-arrow-left" text severity="secondary" class="hover:shadow-md mr-2" @click="router.visit(route('stores'))" />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ t("Edit Store") }}
        </h4>
      </div>
      <div class="flex gap-2">
        <Button icon="fa fa-save" :label="t('Save')" class="uppercase" raised :loading="isSubmitting" @click="submit()" />
      </div>
    </div>
    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>
            {{ t("Store Information") }}
          </template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="name">{{ t("Store Name") }}</label>
                  <InputText id="name" v-model="name" v-bind="nameAttrs" autocomplete="off" :class="{ 'p-invalid': errors.name }" />
                  <small v-if="errors.name" class="text-red-400 dark:text-red-300">
                    {{ errors.name }}
                  </small>
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="code">{{ t("Code") }}</label>
                  <InputText id="code" v-model="code" v-bind="codeAttrs" autocomplete="off" :class="{ 'p-invalid': errors.code }" />
                  <small class="text-gray-500">{{ t("Short identifier used in reports and receipts (e.g., HQ, BRANCH1)") }}</small>
                  <small v-if="errors.code" class="text-red-400 dark:text-red-300">
                    {{ errors.code }}
                  </small>
                </div>
              </div>
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            {{ t("Address") }}
          </template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="address">{{ t("Address") }}</label>
                  <InputText id="address" v-model="address" v-bind="addressAttrs" autocomplete="off" />
                </div>
              </div>
              <div class="md:col-span-4 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="city">{{ t("City") }}</label>
                  <InputText id="city" v-model="city" v-bind="cityAttrs" autocomplete="off" />
                </div>
              </div>
              <div class="md:col-span-4 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="state">{{ t("State") }}</label>
                  <InputText id="state" v-model="state" v-bind="stateAttrs" autocomplete="off" />
                </div>
              </div>
              <div class="md:col-span-4 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="zip-code">{{ t("Zip Code") }}</label>
                  <InputText id="zip-code" v-model="zipCode" v-bind="zipCodeAttrs" autocomplete="off" />
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="phone">{{ t("Phone") }}</label>
                  <InputText id="phone" v-model="phone" v-bind="phoneAttrs" autocomplete="off" />
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="email">{{ t("Email") }}</label>
                  <InputText id="email" v-model="email" v-bind="emailAttrs" autocomplete="off" :class="{ 'p-invalid': errors.email }" />
                  <small v-if="errors.email" class="text-red-400 dark:text-red-300">
                    {{ errors.email }}
                  </small>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
      <div class="md:col-span-4 col-span-12">
        <Card class="mb-4">
          <template #title>
            {{ t("Status") }}
          </template>
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="status">{{ t("Status") }}</label>
              <Select
                id="status"
                v-model="status"
                v-bind="statusAttrs"
                :options="[
                  { name: t('Active'), value: 'active' },
                  { name: t('Inactive'), value: 'inactive' },
                ]"
                option-label="name"
                option-value="value"
              />
            </div>
          </template>
        </Card>
        <Card v-if="!props.store.deleted_at" class="mb-4">
          <template #title>
            <div class="flex items-center justify-between">
              <span>{{ t("Assigned Users") }}</span>
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
                  <Avatar :label="option.full_name.charAt(0).toUpperCase()" size="small" shape="circle" />
                  <div class="font-medium">{{ option.full_name }}</div>
                </div>
              </template>
            </AutoComplete>

            <div v-if="assignedUsers.length === 0" class="text-center text-surface-400 py-4 text-sm">
              {{ t("No users assigned yet") }}
            </div>

            <div
              v-for="user in assignedUsers"
              :key="user.id"
              class="flex items-center gap-3 p-2 mb-2 rounded-lg bg-surface-50 dark:bg-surface-800"
            >
              <Avatar :label="user.full_name.charAt(0).toUpperCase()" shape="circle" />
              <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ user.full_name }}</div>
              </div>
              <Button icon="fa fa-trash" text rounded severity="primary" size="small" @click="removeUser(user.id)" />
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button, Card, InputText, Select, AutoComplete, Avatar, Badge, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string } from "yup";
import { route } from "ziggy-js";
import { ref, nextTick } from "vue";
import AppLayout from "@layouts/admin.vue";

interface SimpleUser {
  id: number;
  full_name: string;
  email: string;
}

// Set composables
const toast = useToast();
const { t } = useI18n();

// Layout
defineOptions({ layout: AppLayout });

// Props from Inertia
const props = defineProps<{
  store: {
    id: number;
    name: string;
    code: string;
    address: string | null;
    city: string | null;
    state: string | null;
    zip_code: string | null;
    phone: string | null;
    email: string | null;
    status: string;
    deleted_at: string | null;
    users?: SimpleUser[];
  };
  availableUsers: SimpleUser[];
}>();

// Schema
const schema = toTypedSchema(
  object({
    name: string().required().max(100),
    code: string().required().max(20),
    address: string().nullable().optional().max(255),
    city: string().nullable().optional().max(100),
    state: string().nullable().optional().max(100),
    zip_code: string().nullable().optional().max(20),
    phone: string().nullable().optional().max(30),
    email: string().nullable().optional().email().max(150),
    status: string().required().oneOf(["active", "inactive"]),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors } = useForm({
  validationSchema: schema,
  initialValues: {
    name: props.store.name,
    code: props.store.code,
    address: props.store.address ?? "",
    city: props.store.city ?? "",
    state: props.store.state ?? "",
    zip_code: props.store.zip_code ?? "",
    phone: props.store.phone ?? "",
    email: props.store.email ?? "",
    status: props.store.status,
  },
});

const [name, nameAttrs] = defineField("name");
const [code, codeAttrs] = defineField("code");
const [address, addressAttrs] = defineField("address");
const [city, cityAttrs] = defineField("city");
const [state, stateAttrs] = defineField("state");
const [zipCode, zipCodeAttrs] = defineField("zip_code");
const [phone, phoneAttrs] = defineField("phone");
const [email, emailAttrs] = defineField("email");
const [status, statusAttrs] = defineField("status");

// User assignment (local state, submitted with form — same pattern as Roles Edit)
const assignedUsers = ref<SimpleUser[]>([...(props.store.users ?? [])]);
const userSearch = ref<SimpleUser | null>(null);
const filteredUsers = ref<SimpleUser[]>([]);

const onSearchUsers = (event: { query: string }) => {
  const q = event.query.toLowerCase();
  filteredUsers.value = props.availableUsers.filter(
    (u) => !assignedUsers.value.find((a) => a.id === u.id) && (u.full_name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)),
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

// Submit — sends user IDs along with store data (same as Roles Edit)
const submit = handleSubmit((values) => {
  router.put(
    route("stores.update", props.store.id),
    {
      ...values,
      users: assignedUsers.value.map((u) => u.id),
    },
    {
      onSuccess: () => {
        router.visit(route("stores"));
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
    },
  );
});
</script>
