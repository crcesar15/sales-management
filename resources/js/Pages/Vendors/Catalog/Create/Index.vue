<script setup lang="ts">
import { Button, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import { ref } from "vue";
import AppLayout from "@layouts/admin.vue";
import CatalogEntryForm from "../Components/CatalogEntryForm.vue";
import type { VendorResponse } from "@/Types/vendor-types";
import type { CatalogPayload } from "@/Types/catalog-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  vendor: VendorResponse;
  existingCatalogKeys: string[];
}>();

const toast = useToast();
const { t } = useI18n();
const formRef = ref<InstanceType<typeof CatalogEntryForm> | null>(null);

const handleSubmit = (payload: CatalogPayload) => {
  router.post(route("vendors.catalog.store", props.vendor.id), payload, {
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("Catalog entry created successfully"),
        life: 3000,
      });
      router.visit(route("vendors.catalog", props.vendor.id));
    },
    onError: (errs: Record<string, string>) => {
      formRef.value?.handleError(errs);
    },
  });
};

const goBack = () => {
  router.visit(route("vendors.catalog", props.vendor.id));
};
</script>

<template>
  <div>
    <div class="flex items-center justify-between gap-2 mb-3">
      <div class="flex min-w-0 items-center gap-2 sm:gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="goBack" />
        <h2 class="m-0 min-w-0 text-2xl font-bold leading-tight sm:text-2xl sm:leading-normal">
          <span class="hidden sm:inline">{{ vendor.fullname }} —</span>
          {{ t("Add Catalog Entry") }}
        </h2>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" raised class="shrink-0 uppercase" @click="formRef?.submit" />
    </div>

    <CatalogEntryForm ref="formRef" :vendor="vendor" :existing-catalog-keys="existingCatalogKeys" @submit="handleSubmit" />
  </div>
</template>
