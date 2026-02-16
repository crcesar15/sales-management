<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Settings") }}
      </h2>
      <Button
        icon="fa fa-save"
        :label="$t('Save')"
        class="uppercase"
        raised
        @click="submit()"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <div class="grid grid-cols-12 gap-4">
          <div
            v-for="setting in settings"
            :key="setting.id"
            class="col-span-12 md:col-span-6"
          >
            <div class="flex flex-col gap-2 mb-3">
              <label :for="setting.key">{{ setting.name }}</label>
              <InputText
                :id="setting.key"
                v-model="setting.value"
                autocomplete="off"
              />
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import {
  Card,
  Button,
  InputText,
  ConfirmDialog,
  useToast,
  useConfirm,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import {onMounted, ref } from "vue";
import { SettingResponse } from "@/Types/setting-types";
import { useSettingClient } from "@/Composables/useSettingClient";

// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Layout
defineOptions({
  layout: AppLayout,
});

// List settings
const { fetchSettingsApi, updateSettingApi, loading } = useSettingClient();

const settings = ref<SettingResponse[]>();

const fetchSettings = async () => {
  const params = new URLSearchParams();
  params.append('per_page', '100');

  try {
    const response = await fetchSettingsApi(params.toString());
    settings.value = response.data.data;
  } catch (error) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t("Failed to fetch settings"),
      life: 3000,
    });
  }
}

const submit = async () => {
  confirm.require({
    message: t("Are you sure you want to save these settings?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Save"),
    rejectClass: "p-button-secondary",
    accept: async () => {
      try {
        await updateSettingApi(settings.value as SettingResponse[]);

        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("Settings updated successfully"),
          life: 3000,
        });
      } catch (error:any) {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: error.response.data.message,
          life: 3000,
        });
      }
    }
  });
}

onMounted(() => {
  fetchSettings();
})
</script>