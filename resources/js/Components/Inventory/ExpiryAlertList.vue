<script setup lang="ts">
import { DataTable, Column, Tag, Chip } from "primevue";
import { useI18n } from "vue-i18n";
import type { ExpiryAlertItem } from "@/Types/stock-alert-types";

defineProps<{
  alerts: ExpiryAlertItem[];
}>();

const { t, d } = useI18n();

function daysRemaining(item: ExpiryAlertItem): number {
  if (!item.expiry_date) return 0;
  const expiry = new Date(item.expiry_date);
  const now = new Date();
  const diff = expiry.getTime() - now.getTime();
  return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

function expirySeverity(item: ExpiryAlertItem): "danger" | "warning" {
  const days = daysRemaining(item);
  return days <= 7 ? "danger" : "warning";
}

function expiryLabel(item: ExpiryAlertItem): string {
  const days = daysRemaining(item);
  if (days <= 0) return t("Expired");
  if (days === 1) return t("1 day");
  return t(":days days", { days });
}
</script>

<template>
  <DataTable :value="alerts" striped-rows>
    <template #empty>
      <div class="text-center py-6 text-surface-500">
        {{ t("No expiry alerts") }}
      </div>
    </template>

    <Column field="product_variant.product.name" :header="t('Product')">
      <template #body="{ data }">
        <span class="font-medium">{{ data.product_variant?.product?.name ?? "—" }}</span>
        <div v-if="data.product_variant?.name" class="text-sm text-surface-500 mt-0.5">
          {{ data.product_variant.name }}
        </div>
      </template>
    </Column>

    <Column field="product_variant.product.brand.name" :header="t('Brand')">
      <template #body="{ data }">
        {{ data.product_variant?.product?.brand?.name ?? "—" }}
      </template>
    </Column>

    <Column field="store.name" :header="t('Store')">
      <template #body="{ data }">
        <Chip v-if="data.store" :label="data.store.name" size="small" />
        <span v-else>—</span>
      </template>
    </Column>

    <Column field="remaining_quantity" :header="t('Remaining')">
      <template #body="{ data }">
        <span class="font-medium">{{ data.remaining_quantity }}</span>
      </template>
    </Column>

    <Column field="expiry_date" :header="t('Expiry Date')">
      <template #body="{ data }">
        {{ d(new Date(data.expiry_date), "short") }}
      </template>
    </Column>

    <Column :header="t('Days Remaining')" style="width: 140px">
      <template #body="{ data }">
        <Tag :value="expiryLabel(data)" :severity="expirySeverity(data)" />
      </template>
    </Column>
  </DataTable>
</template>
