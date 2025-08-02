<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="$inertia.visit(route('roles'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0 capitalize">
          {{ $t('edit role') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <PButton
          icon="fa fa-save"
          :label="$t('Save')"
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
                  <label for="name">{{ $t('Name') }}</label>
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
                    <span class="uppercase font-extrabold">{{ $t(item.category) }}</span>
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
                      >{{ $t(permission.name) }}</label>
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

<script>
import {
  Card,
  InputText,
  ToggleSwitch,
  Accordion,
  AccordionPanel,
  AccordionHeader,
  AccordionContent,
} from "primevue";
import { required, createI18nMessage } from "@vuelidate/validators";
import { useVuelidate } from "@vuelidate/core";

import AppLayout from "../../../Layouts/admin.vue";
import i18n from "../../../app";

export default {
  components: {
    Card,
    InputText,
    ToggleSwitch,
    Accordion,
    AccordionPanel,
    AccordionHeader,
    AccordionContent,
  },
  layout: AppLayout,
  props: {
    role: {
      type: Object,
      required: true,
      default() {
        return {
          name: "test",
        };
      },
    },
    permissions: {
      type: Array,
      required: true,
      default() {
        return [];
      },
    },
  },
  setup() {
    return {
      v$: useVuelidate(),
    };
  },
  data() {
    return {
      name: "",
      availablePermissions: [],
    };
  },
  mounted() {
    this.name = this.role.name;
    this.fetchPermissions();
  },
  methods: {
    fetchPermissions() {
      const params = {
        select: "id,name,category",
        per_page: 200,
        order_by: "category",
        order_direction: "asc",
      };

      window.axios.get(route("api.permissions", params)).then((res) => {
        const permissions = res.data.data.map((item) => ({ ...item, enabled: false }));

        this.availablePermissions = this.groupPermissions(permissions);
      });
    },
    groupPermissions(permissions) {
      const formattedPermissions = [];
      let value = 0; // used for accordion

      permissions.forEach((item) => {
        const indexFound = formattedPermissions.findIndex((i) => i.category === item.category);
        const enabled = this.permissions.includes(item.name);

        if (indexFound >= 0) {
          formattedPermissions[indexFound].permissions.push({ id: item.id, name: item.name, enabled });
        } else {
          formattedPermissions.push({
            value: value.toString(),
            category: item.category,
            permissions: [{ id: item.id, name: item.name, enabled }],
          });
          value += 1;
        }
      });

      return formattedPermissions;
    },
    getEnabledPermissions(permissionsGroups) {
      const enabledPermissions = [];

      permissionsGroups.forEach((group) => {
        group.permissions.forEach((permission) => {
          if (permission.enabled) {
            enabledPermissions.push(permission.name);
          }
        });
      });

      return enabledPermissions;
    },
    submit() {
      this.v$.$touch();

      if (!this.v$.$invalid) {
        const body = {
          name: this.name,
          permissions: this.getEnabledPermissions(this.availablePermissions),
        };

        axios.put(route("api.roles.update", this.role.id), body)
          .then(() => {
            this.$toast.add({
              severity: "success",
              summary: this.$t("Success"),
              detail: this.$t("Role created successfully"),
              life: 3000,
            });
            this.$inertia.visit(route("roles"));
          })
          .catch((error) => {
            this.$toast.add({
              severity: "error",
              summary: this.$t("Error"),
              detail: this.$t(error.response.data.message),
              life: 3000,
            });
          });
      }
    },
  },
  validations() {
    const { t } = i18n.global;

    const withI18nMessage = createI18nMessage({
      t,
      messagesPath: "validations",
    });

    return {
      name: {
        required: withI18nMessage(required),
      },
    };
  },
};

</script>

<style scoped>
/* accordionheader {
  background-color: var(--p-slate-100);
} */
</style>
