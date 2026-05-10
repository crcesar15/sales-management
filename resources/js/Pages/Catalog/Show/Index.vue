<script setup lang="ts">
import { DataTable, Column, Card, Button, Tag, Badge, Toast, ConfirmDialog, SelectButton, useConfirm, useToast } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import { computed, ref } from "vue";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import CatalogStatusTag from "../Components/CatalogStatusTag.vue";
import type { CatalogShowProps, CatalogResponse } from "@/Types/catalog-types";

defineOptions({ layout: AppLayout });

const props = defineProps<CatalogShowProps>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const confirm = useConfirm();
const toast = useToast();

const selectedUnit = ref<number | null>(null);

const unitOptions = computed(() => {
  const units = new Map<number, string>();
  for (const entry of props.catalogEntries) {
    if (entry.purchase_unit) {
      units.set(entry.purchase_unit.id, entry.purchase_unit.name);
    }
  }
  return [{ label: t("All"), value: null }, ...Array.from(units.entries()).map(([id, name]) => ({ label: name, value: id }))];
});

const filteredEntries = computed(() => {
  if (selectedUnit.value === null) return props.catalogEntries;
  return props.catalogEntries.filter((entry) => entry.purchase_unit?.id === selectedUnit.value);
});

const goBack = () => router.visit(route("catalog"));

const editEntry = (entry: CatalogResponse) => {
  router.visit(route("vendors.catalog.edit", [entry.vendor_id, entry.id]));
};

const deleteEntry = (entry: CatalogResponse) => {
  confirm.require({
    message: t("Are you sure you want to delete this catalog entry?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("vendors.catalog.destroy", [entry.vendor_id, entry.id]), {
        onSuccess: () => {
          toast.add({
            severity: "success",
            summary: t("Success"),
            detail: t("Catalog entry deleted successfully"),
            life: 3000,
          });
        },
        onError: () => {
          toast.add({
            severity: "error",
            summary: t("Error"),
            detail: t("Could not delete catalog entry"),
            life: 3000,
          });
        },
      });
    },
  });
};

const formatPaymentTerms = (terms: string | null): string => {
  if (!terms) return "\u2014";
  const map: Record<string, string> = { debit: t("Cash"), credit: t("Credit"), both: t("Both") };
  return map[terms] ?? terms;
};
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ t("Product Catalog Details") }}</h2>
      </div>
      <div class="flex flex-col justify-center">
        <Button
          v-can="'catalog.create'"
          :label="t('Add Entry')"
          icon="fa fa-plus"
          raised
          class="uppercase"
          @click="router.visit(route('vendors'))"
        />
      </div>
    </div>

    <Toast />
    <ConfirmDialog />

    <Card class="mb-4">
      <template #title>{{ t("Product Details") }}</template>
      <template #content>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <span class="text-sm text-surface-500 block">{{ t("Product") }}</span>
            <span class="font-medium">{{ productVariant.product?.name ?? "\u2014" }}</span>
          </div>
          <div>
            <span class="text-sm text-surface-500 block">{{ t("Variant") }}</span>
            <span class="font-medium">{{ productVariant.name }}</span>
          </div>
          <div v-if="productVariant.product?.brand">
            <span class="text-sm text-surface-500 block">{{ t("Brand") }}</span>
            <span class="font-medium">{{ productVariant.product.brand.name }}</span>
          </div>
        </div>
        <div v-if="productVariant.values?.length" class="flex flex-wrap gap-1 mt-3">
          <Badge v-for="opt in productVariant.values" :key="opt.id" :value="`${opt.option_name}: ${opt.value}`" />
        </div>
      </template>
    </Card>

    <Card>
      <template #title>
        <div class="flex justify-between items-center">
          <span>{{ t("Vendors") }}</span>
          <SelectButton
            v-model="selectedUnit"
            :options="unitOptions"
            option-label="label"
            option-value="value"
            :allow-empty="false"
          />
        </div>
      </template>
      <template #content>
        <DataTable :value="filteredEntries" resizable-columns>
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No catalog entries found") }}</span>
            </div>
          </template>

          <Column field="vendor.fullname" :header="t('Vendor')" sortable>
            <template #body="{ data }">
              <span class="font-semibold">{{ data.vendor?.fullname ?? "\u2014" }}</span>
            </template>
          </Column>

          <Column field="price" :header="t('Price')" sortable>
            <template #body="{ data }">
              <span class="font-medium">{{ formatCurrency(String(data.price)) }}</span>
            </template>
          </Column>

          <Column field="purchase_unit.name" :header="t('Purchase Unit')">
            <template #body="{ data }">
              <Badge severity="secondary" :value="data.purchase_unit?.name ?? t('Base unit')" />
            </template>
          </Column>

          <Column field="minimum_order_quantity" :header="t('MOQ')">
            <template #body="{ data }">
              <Tag v-if="data.minimum_order_quantity" rounded severity="secondary" :value="String(data.minimum_order_quantity)" />
              <span v-else class="text-surface-400">&mdash;</span>
            </template>
          </Column>

          <Column field="lead_time_days" :header="t('Lead Time')">
            <template #body="{ data }">
              <Tag v-if="data.lead_time_days" rounded severity="secondary" :value="`${data.lead_time_days}d`" />
              <span v-else class="text-surface-400">&mdash;</span>
            </template>
          </Column>

          <Column field="payment_terms" :header="t('Payment')">
            <template #body="{ data }">
              <Badge severity="secondary" :value="formatPaymentTerms(data.payment_terms)" />
            </template>
          </Column>

          <Column field="status" :header="t('Status')">
            <template #body="{ data }">
              <CatalogStatusTag :status="data.status" />
            </template>
          </Column>

          <Column :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center gap-2">
                <Button
                  v-can="'catalog.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  size="large"
                  @click="editEntry(data)"
                />
                <Button
                  v-can="'catalog.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  size="large"
                  class="btn-danger"
                  @click="deleteEntry(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
