<script setup lang="ts">
import { DataTable, Column, Tag, Badge, ProgressBar, Button } from "primevue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { LowStockAlertItem } from "@/Types/stock-alert-types";

defineProps<{
  alerts: LowStockAlertItem[];
}>();

const { t } = useI18n();

function severity(item: LowStockAlertItem): "danger" | "warning" {
  return item.total_stock === 0 ? "danger" : "warning";
}

function severityLabel(item: LowStockAlertItem): string {
  return item.total_stock === 0 ? t("Out of Stock") : t("Low Stock");
}

function deficitPercentage(item: LowStockAlertItem): number {
  if (!item.minimum_stock_level || item.minimum_stock_level === 0) return 0;
  return Math.round((item.total_stock / item.minimum_stock_level) * 100);
}

function deficitSeverity(item: LowStockAlertItem): "danger" | "warning" | "info" {
  const pct = deficitPercentage(item);
  if (pct === 0) return "danger";
  if (pct <= 50) return "danger";
  return "warning";
}
</script>

<template>
  <DataTable :value="alerts" striped-rows>
    <template #empty>
      <div class="text-center py-6 text-surface-500">
        {{ t("No low stock alerts") }}
      </div>
    </template>

    <Column field="product_name" :header="t('Product')">
      <template #body="{ data }">
        <span class="font-medium">{{ data.product_name ?? "—" }}</span>
        <div v-if="data.values?.length" class="flex flex-wrap gap-1 mt-1">
          <Badge v-for="val in data.values" :key="val.id" :value="`${val.option_name}: ${val.value}`" severity="secondary" />
        </div>
      </template>
    </Column>

    <Column field="brand_name" :header="t('Brand')">
      <template #body="{ data }">
        {{ data.brand_name ?? "—" }}
      </template>
    </Column>

    <Column field="identifier" :header="t('Identifier')">
      <template #body="{ data }">
        {{ data.identifier ?? "—" }}
      </template>
    </Column>

    <Column field="total_stock" :header="t('Current Stock')">
      <template #body="{ data }">
        <span class="font-bold" :class="data.total_stock === 0 ? 'text-red-500' : 'text-orange-500'">
          {{ data.total_stock }}
        </span>
      </template>
    </Column>

    <Column field="minimum_stock_level" :header="t('Minimum Stock')">
      <template #body="{ data }">
        {{ data.minimum_stock_level ?? "—" }}
      </template>
    </Column>

    <Column :header="t('Stock Level')">
      <template #body="{ data }">
        <ProgressBar
          :value="deficitPercentage(data)"
          :severity="deficitSeverity(data)"
          :show-value="true"
          :style="{ height: '12px' }"
        />
      </template>
    </Column>

    <Column :header="t('Status')" style="width: 120px">
      <template #body="{ data }">
        <Tag :value="severityLabel(data)" :severity="severity(data)" />
      </template>
    </Column>

    <Column :header="t('Actions')" style="width: 80px">
      <template #body="{ data }">
        <Button
          v-tooltip.top="t('Stock Details')"
          icon="fa-solid fa-eye"
          text
          rounded
          size="small"
          :aria-label="t('Stock Details')"
          @click="router.visit(route('inventory.stock.show', { variant: data.id }))"
        />
      </template>
    </Column>
  </DataTable>
</template>
