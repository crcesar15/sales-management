<script setup lang="ts">
import { DataTable, Column, Button, Tag, useConfirm, useToast } from "primevue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import type { CatalogResponse } from "@/Types/catalog-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

const props = defineProps<{
  entries: CatalogResponse[];
  lowestPrice: number;
}>();

const confirm = useConfirm();
const toast = useToast();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

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
  if (!terms) return "—";
  const map: Record<string, string> = { debit: "Cash", credit: "Credit", both: "Both" };
  return map[terms] ?? terms;
};
</script>

<template>
  <div class="p-3">
    <DataTable :value="props.entries" resizable-columns size="small">
      <Column field="vendor.fullname" :header="t('Vendor')" sortable>
        <template #body="{ data }">
          <span class="font-semibold">{{ data.vendor?.fullname ?? "—" }}</span>
        </template>
      </Column>

      <Column field="price" :header="t('Price')" sortable>
        <template #body="{ data }">
          <div class="flex items-center gap-2">
            <span :class="{ 'font-bold text-green-600 dark:text-green-400': data.price === props.lowestPrice }">
              {{ formatCurrency(String(data.price)) }}
            </span>
            <Tag v-if="data.price === props.lowestPrice && props.entries.length > 1" value="Best" severity="success" rounded class="text-xs" />
          </div>
        </template>
      </Column>

      <Column field="purchase_unit.name" :header="t('Purchase Unit')">
        <template #body="{ data }">
          <span>{{ data.purchase_unit?.name ?? t("Base unit") }}</span>
        </template>
      </Column>

      <Column field="minimum_order_quantity" :header="t('MOQ')">
        <template #body="{ data }">
          <Tag v-if="data.minimum_order_quantity" rounded severity="secondary" :value="String(data.minimum_order_quantity)" />
          <span v-else class="text-surface-400">—</span>
        </template>
      </Column>

      <Column field="lead_time_days" :header="t('Lead Time')">
        <template #body="{ data }">
          <Tag v-if="data.lead_time_days" rounded severity="secondary" :value="`${data.lead_time_days}d`" />
          <span v-else class="text-surface-400">—</span>
        </template>
      </Column>

      <Column field="payment_terms" :header="t('Payment')">
        <template #body="{ data }">
          <span>{{ formatPaymentTerms(data.payment_terms) }}</span>
        </template>
      </Column>

      <Column field="status" :header="t('Status')">
        <template #body="{ data }">
          <Tag :value="t(data.status === 'active' ? 'Active' : 'Inactive')" :severity="data.status === 'active' ? 'success' : 'warn'" rounded />
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
  </div>
</template>