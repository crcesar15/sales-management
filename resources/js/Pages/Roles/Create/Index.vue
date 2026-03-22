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
          {{ t('add role') }}
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
      <div class="md:col-span-4 col-span-12">
        <Card class="mb-4">
          <template #title>
            {{ t('Users') }}
          </template>
          <template #content>
            Assigned users to this role
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
  Button
} from "primevue";

import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string } from "yup";
import { ref } from "vue";
import { PermissionGroupedAccordion } from "@/Types/permission-types";
import { route } from "ziggy-js";
import { router } from "@inertiajs/vue3";

import AppLayout from "@layouts/admin.vue";

// Set composables
const toast = useToast();
const { t } = useI18n();

// Layout
defineOptions({
  layout: AppLayout
});

// Props from Inertia
const props = defineProps<{
  availablePermissions: { id: number; name: string; category: string }[];
}>();

// VeeValidate + Yup schema (messages come from configureYupLocale in app.ts)
const schema = toTypedSchema(
  object({
    name: string().required().max(255),
  })
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors } = useForm({
  validationSchema: schema,
});

const [name, nameAttrs] = defineField("name");

// Permission accordion
interface PermissionGrouped extends PermissionGroupedAccordion {
  permissions: Array<{ id: number; name: string; enabled: boolean }>;
}

const groupPermissions = (permissions: { id: number; name: string; category: string }[]): PermissionGrouped[] => {
  const formattedPermissions: PermissionGrouped[] = [];
  let value = 0;

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

const availablePermissions = ref<PermissionGrouped[]>(groupPermissions(props.availablePermissions));

const getEnabledPermissions = (permissionsGroups: PermissionGrouped[]): string[] => {
  const enabledPermissions: string[] = [];

  permissionsGroups.forEach((group) => {
    group.permissions.forEach((permission) => {
      if (permission.enabled) {
        enabledPermissions.push(permission.name);
      }
    });
  });

  return enabledPermissions;
};

const submit = handleSubmit((values) => {
  router.post(
    route("roles.store"),
    {
      name: values.name,
      permissions: getEnabledPermissions(availablePermissions.value),
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
