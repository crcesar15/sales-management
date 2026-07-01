<script setup lang="ts">
import { Dialog, DataTable, Column, Tag, Badge, useToast } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { usePurchaseOrderClient } from "@/Composables/usePurchaseOrderClient";
import type { CatalogVariantVendor } from "@/Types/catalog-types";
import { ref, watch } from "vue";

const props = defineProps<{
  visible: boolean;
  productVariantId: number | null;
  productName: string;
  variantLabel: string;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const { t } = useI18n();
const toast = useToast();
const { formatCurrency } = useCurrencyFormatter();
const { fetchVariantVendorsApi } = usePurchaseOrderClient();

const vendors = ref<CatalogVariantVendor[]>([]);
const loading = ref(false);

async function loadVendors() {
  if (!props.productVariantId) return;
  loading.value = true;
  try {
    const response = await fetchVariantVendorsApi(props.productVariantId);
    vendors.value = response.data?.data || [];
  } catch {
    toast.add({ severity: "error", summary: t("Error"), detail: t("Failed to load vendors"), life: 3000 });
    vendors.value = [];
  } finally {
    loading.value = false;
  }
}

watch(
  () => props.visible,
  (newVal) => {
    if (newVal) loadVendors();
  },
);

function formatPaymentTerms(terms: string | null): string {
  if (!terms) return "\u2014";
  const map: Record<string, string> = { debit: t("Cash"), credit: t("Credit"), both: t("Cash / Credit") };
  return map[terms] ?? terms;
}
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :style="{ width: '850px' }"
    :breakpoints="{ '960px': '95vw' }"
    @update:visible="emit('update:visible', $event)"
  >
    <template #header>
      <div>
        <span class="font-semibold text-lg">{{ t("Vendors") }}</span>
        <div class="text-sm text-surface-500 mt-1">{{ productName }} &mdash; {{ variantLabel }}</div>
      </div>
    </template>

    <DataTable :value="vendors" :loading="loading" striped-rows row-hover sort-field="price" :sort-order="1" class="border-t-2">
      <template #empty>
        <div class="flex flex-col items-center py-8 text-surface-400">
          <i class="fa fa-store-slash text-4xl mb-3"></i>
          <span>{{ t("No vendors found for this product") }}</span>
        </div>
      </template>

      <Column field="vendor.fullname" :header="t('Vendor')" sortable>
        <template #body="{ data }">
          <span class="font-semibold">{{ data.vendor?.fullname ?? "\u2014" }}</span>
        </template>
      </Column>

      <Column field="price" :header="t('Price')" sortable>
        <template #body="{ data }">
          {{ formatCurrency(String(data.price)) }}
        </template>
      </Column>

      <Column field="payment_terms" :header="t('Payment')">
        <template #body="{ data }">
          <Badge severity="secondary" size="xlarge" class="capitalize" :value="formatPaymentTerms(data.payment_terms)" />
        </template>
      </Column>

      <Column field="unit.name" :header="t('Purchase Unit')">
        <template #body="{ data }">
          <Badge
            class="capitalize"
            size="xlarge"
            severity="secondary"
            :value="data.unit?.name ?? data.measurement_unit?.name ?? '\u2014'"
          />
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

      <Column field="status" :header="t('Status')">
        <template #body="{ data }">
          <Tag
            :value="t(data.status === 'active' ? 'Active' : 'Inactive')"
            :severity="data.status === 'active' ? 'success' : 'warn'"
            rounded
          />
        </template>
      </Column>
    </DataTable>
  </Dialog>
</template>
