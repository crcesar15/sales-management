<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="router.visit(route('roles'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0 capitalize">
          {{ t('add role') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <PButton
          icon="fa fa-save"
          :label="t('Save')"
          class="uppercase"
          raised
          @click="submit()"
        />
      </div>
    </div>
    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-4 col-span-12">
        <Card class="mb-4">
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="name">{{ t('Name') }}</label>
                  <InputText
                    id="name"
                    v-model="name"
                    autocomplete="off"
                    :class="{'p-invalid': v$.name.$invalid && v$.name.$dirty}"
                    @blur="v$.name.$touch"
                  />
                  <small
                    v-if="v$.name.$invalid && v$.name.$dirty"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ v$.name.$errors[0].$message }}
                  </small>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #content>
            <Accordion
              :value="['0']"
              multiple
            >
              <AccordionPanel
                v-for="item in availablePermissions"
                :key="item.category"
                :value="item.value"
              >
                <AccordionHeader
                  :pt="{root: {class: '!bg-surface-100 dark:!bg-surface-800 !rounded-md !mb-2'}}"
                >
                  <div class="w-full">
                    <span class="uppercase font-extrabold">{{ t(item.category) }}</span>
                  </div>
                </AccordionHeader>
                <AccordionContent>
                  <div class="grid grid-cols-12 gap-4">
                    <div
                      v-for="permission in item.permissions"
                      :key="permission.id"
                      class="md:col-span-6 col-span-12 flex items-center flex-wrap gap-2"
                    >
                      <ToggleSwitch
                        v-model="permission.enabled"
                        :input-id="`tg-${permission.name}`"
                      />
                      <label
                        :for="`tg-${permission.name}`"
                        class="first-letter:uppercase"
                      >{{ t(permission.name) }}</label>
                    </div>
                  </div>
                </AccordionContent>
              </AccordionPanel>
            </Accordion>
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
  ToggleSwitch,
  Accordion,
  AccordionPanel,
  AccordionHeader,
  AccordionContent,
  useToast,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { usePermissionClient} from '@composables/usePermissionClient';
import { required, createI18nMessage } from "@vuelidate/validators";
import { useVuelidate } from "@vuelidate/core";
import { computed, onMounted, ref } from "vue";
import { Permission, PermissionGroupedAccordion } from "@/Types/permission-types";
import { Role } from "@/Types/role-types";
import { useRoleClient } from "@/Composables/useRoleClient";
import { route } from "ziggy-js";
import { router } from "@inertiajs/vue3";

// Set composables
const toast = useToast();
const { t } = useI18n();

//get from .json file
const withI18nMessage = createI18nMessage({t});
// Layout
defineOptions({
  layout: AppLayout
});

// Rules
const rules = computed(() => ({
  name: {
    required: withI18nMessage(required),
  },
}));

// From variables
const name = ref("");
const availablePermissions = ref<PermissionGroupedAccordion[]>([]);

// Validator
const v$ = useVuelidate(
  rules,
  {
    name
  }
);

const groupPermissions = (permissions: Permission[]) => {
  const formattedPermissions:PermissionGroupedAccordion[] = [];
  let value = 0; // used for accordion

  permissions.forEach((item) => {
    const indexFound = formattedPermissions.findIndex((i) => i.category === item.category);

    if (indexFound >= 0) {
      formattedPermissions[indexFound].permissions.push({ id: item.id, name: item.name, enabled: false });
    } else {
      formattedPermissions.push({
        value: value.toString(),
        category: item.category,
        permissions: [{ id: item.id, name: item.name, enabled: false }],
      });
      value += 1;
    }
  });

  return formattedPermissions;
};

const fetchPermissions = async () => {
  try {
    const params = {
      select: "id,name,category",
      per_page: 200,
      order_by: "category",
      order_direction: "asc",
    };

    const {fetchPermissionsApi} = usePermissionClient();

    const response = await fetchPermissionsApi(params.toString());

    const permissions = response.data.data.map((item: Permission) => ({ ...item, enabled: false }));

    availablePermissions.value = groupPermissions(permissions);
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error.response.data.message),
    });
  }
}

onMounted(() => {
  fetchPermissions();
})

const getEnabledPermissions = (permissionsGroups:PermissionGroupedAccordion[]) => {
  const enabledPermissions:string[] = [];

  permissionsGroups.forEach((group) => {
    group.permissions.forEach((permission) => {
      if (permission.enabled) {
        enabledPermissions.push(permission.name);
      }
    });
  });

  return enabledPermissions;
}

const submit = async () => {
  v$.value.$touch();

  if (!v$.value.$invalid) {
    const body = {
      name: name.value,
      permissions: getEnabledPermissions(availablePermissions.value),
    };

    const {storeRoleApi} = useRoleClient();

    try {
      await storeRoleApi(body);

      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("Role created successfully"),
        life: 3000,
      });

      router.visit(route('roles'));
    } catch (error:any) {
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t(error.response.data.message),
        life: 3000,
      });
    }
  }
}
</script>