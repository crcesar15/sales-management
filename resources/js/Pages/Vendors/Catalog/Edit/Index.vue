<script setup lang="ts">
import { Button, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import { ref } from "vue";
import AppLayout from "@layouts/admin.vue";
import CatalogEntryForm from "../Components/CatalogEntryForm.vue";
import type { VendorResponse } from "@/Types/vendor-types";
import type { CatalogResponse, CatalogPayload } from "@/Types/catalog-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  vendor: VendorResponse;
  catalog: CatalogResponse;
}>();

const toast = useToast();
const { t } = useI18n();
const formRef = ref<InstanceType<typeof CatalogEntryForm> | null>(null);

const handleSubmit = (payload: CatalogPayload) => {
  router.put(route("vendors.catalog.update", [props.vendor.id, props.catalog.id]), payload, {
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("Catalog entry updated successfully"),
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
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ vendor.fullname }} — {{ t("Edit Catalog Entry") }}</h2>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" raised class="uppercase" @click="formRef?.submit" />
    </div>

    <CatalogEntryForm ref="formRef" :vendor="vendor" :initial-values="catalog" :is-editing="true" @submit="handleSubmit" />
  </div>
</template>
