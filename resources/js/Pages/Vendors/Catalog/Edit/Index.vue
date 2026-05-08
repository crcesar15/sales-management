<script setup lang="ts">
import { Button, Toast, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
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
    onError: (_errs: Record<string, string>) => {
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t("Please review the errors in the form"),
        life: 3000,
      });
    },
  });
};

const handleCancel = () => {
  router.visit(route("vendors.catalog", props.vendor.id));
};

const goBack = () => {
  router.visit(route("vendors.catalog", props.vendor.id));
};
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <div class="flex">
        <Button
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="goBack"
        />
        <h2 class="text-2xl font-bold flex items-center m-0">
          {{ vendor.fullname }} — {{ t("Edit Catalog Entry") }}
        </h2>
      </div>
    </div>

    <Toast />

    <CatalogEntryForm
      :vendor="vendor"
      :initial-values="catalog"
      :is-editing="true"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </div>
</template>
