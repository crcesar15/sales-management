<script setup lang="ts">
import { Badge, DataTable, Column, Card, Button, Tag, Toast, ConfirmDialog, SelectButton, useConfirm, useToast } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import { computed, ref } from "vue";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import formatDateTime from "@/Composables/useDatetimeFormatter";
import type { CatalogShowProps, CatalogResponse } from "@/Types/catalog-types";

defineOptions({ layout: AppLayout });

const props = defineProps<CatalogShowProps>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const confirm = useConfirm();
const toast = useToast();

const selectedUnit = ref<number | null>(null);

const isRealVariant = computed(() => props.productVariant.values?.length > 0);

const productStatusSeverity = computed(() => {
  const map: Record<string, "success" | "warn" | "danger"> = {
    active: "success",
    inactive: "warn",
    archived: "danger",
  };
  return map[props.productVariant.status] ?? "info";
});

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
  const map: Record<string, string> = { debit: t("Cash"), credit: t("Credit"), both: t("Cash / Credit") };
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
          :label="t('Add Vendor')"
          icon="fa fa-plus"
          raised
          class="uppercase"
          @click="router.visit(route('vendors'))"
        />
      </div>
    </div>

    <Toast />
    <ConfirmDialog />

    <div class="grid grid-cols-12 gap-4 mb-4">
      <!-- Primary Card: Product Details -->
      <div class="col-span-12 lg:col-span-8">
        <Card>
          <template #title>
            <!-- Product name + status -->
            <div class="flex flex-row items-center justify-between gap-2">
              <span class="text-2xl font-bold">{{ productVariant.product?.name ?? "\u2014" }}</span>
              <div v-if="isRealVariant">
                <div class="flex gap-2">
                  <Badge size="large" v-for="opt in productVariant.values" :key="opt.id" :value="`${opt.value}`" rounded />
                </div>
              </div>
            </div>
          </template>
          <template #content>
            <!-- Metadata grid -->
            <div class="grid grid-cols-2 gap-4">
              <!-- Brand -->
              <div>
                <span class="text-surface-500 block mb-1">{{ t("Brand") }}</span>
                <span class="font-medium">{{ productVariant.product?.brand?.name ?? "\u2014" }}</span>
              </div>

              <!-- Measurement Unit -->
              <div>
                <span class="text-surface-500 block mb-1">{{ t("Measurement Unit") }}</span>
                <span class="font-medium">{{ productVariant.product?.measurement_unit?.name ?? "\u2014" }}</span>
              </div>

              <!-- Categories -->
              <div v-if="productVariant.product?.categories?.length" class="col-span-2">
                <span class="text-surface-500 block mb-1">{{ t("Categories") }}</span>
                <div class="flex flex-wrap gap-2">
                  <Badge
                    size="large"
                    severity="secondary"
                    class="!capitalize"
                    v-for="cat in productVariant.product.categories"
                    :key="cat.id"
                    rounded
                  >
                    <i class="fa fa-tags mr-2" />
                    {{ cat.name }}
                  </Badge>
                </div>
              </div>

              <!-- Identifier -->
              <div v-if="productVariant.identifier">
                <span class="text-surface-500 block mb-1">{{ t("Identifier") }}</span>
                <span class="font-medium font-mono">{{ productVariant.identifier }}</span>
              </div>

              <!-- Barcode -->
              <div v-if="productVariant.barcode">
                <span class="text-surface-500 block mb-1">{{ t("Barcode") }}</span>
                <span class="font-medium font-mono">{{ productVariant.barcode }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Card class="mt-4">
          <template #title>
            <div class="flex justify-between items-center">
              <span>{{ t("Vendors") }}</span>
              <SelectButton v-model="selectedUnit" :options="unitOptions" option-label="label" option-value="value" :allow-empty="false" />
            </div>
          </template>
          <template #content>
            <DataTable :value="filteredEntries" resizable-columns class="border-t-2">
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

              <Column field="payment_terms" :header="t('Payment')">
                <template #body="{ data }">
                  <Badge severity="secondary" size="xlarge" class="capitalize" :value="formatPaymentTerms(data.payment_terms)" />
                </template>
              </Column>

              <Column field="purchase_unit.name" :header="t('Purchase Unit')">
                <template #body="{ data }">
                  <Badge class="capitalize" size="xlarge" severity="secondary" :value="data.purchase_unit?.name ?? t('Base unit')" />
                </template>
              </Column>

              <Column :header="t('Equivalence')">
                <template #body="{ data }">
                  <span v-if="data.purchase_unit" class="text-sm">
                    1 {{ data.purchase_unit.name }} =
                    <span class="font-medium">{{ data.purchase_unit.conversion_factor }}</span>
                    {{ productVariant.product?.measurement_unit?.abbreviation ?? t("units") }}
                  </span>
                  <span v-else class="text-surface-400">&mdash;</span>
                </template>
              </Column>

              <Column field="status" :header="t('Status')">
                <template #body="{ data }">
                  <Tag
                    :value="t(data.status === 'active' ? 'Active' : 'Inactive')"
                    :severity="data.status === 'active' ? 'success' : 'warn'"
                    rounded
                  />
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

      <!-- Side Card: Summary -->
      <div class="col-span-12 lg:col-span-4">
        <Card>
          <template #title>{{ t("Summary") }}</template>
          <template #content>
            <div class="flex flex-col gap-4 mb-3">
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Status") }}</span>
                <Tag
                  :value="t(productVariant.status.charAt(0).toUpperCase() + productVariant.status.slice(1))"
                  :severity="productStatusSeverity"
                  rounded
                />
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Base Price") }}</span>
                <span class="font-bold">{{ formatCurrency(String(productVariant.price)) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Stock") }}</span>
                <span class="font-bold">{{ productVariant.stock }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Options") }}</span>
                <span class="font-bold">{{ catalogEntries.length }}</span>
              </div>
              <div class="border-t border-surface-200 pt-3 flex justify-between">
                <span class="text-surface-500">{{ t("Created") }}</span>
                <span class="font-medium">{{ formatDateTime(productVariant.created_at) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Updated") }}</span>
                <span class="font-medium">{{ formatDateTime(productVariant.updated_at) }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>
