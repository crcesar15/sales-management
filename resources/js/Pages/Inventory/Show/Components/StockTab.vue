<script setup lang="ts">
import { DataTable, Column } from "primevue";

import { useI18n } from "vue-i18n";
import type { StockStoreBreakdown } from "@/Types/stock-overview-types";

defineProps<{
  stores: StockStoreBreakdown[];
}>();

const { t } = useI18n();
</script>

<template>
  <DataTable :value="stores" resizable-rows class="border-t-2 border-surface-200 dark:border-surface-700">
    <template #empty>
      {{ t("No stock records found") }}
    </template>

    <Column field="store_name" :header="t('Store')">
      <template #body="{ data }">
        <span class="text-900 font-medium">{{ data.store_name }}</span>
        <span class="text-color-secondary text-sm ml-2">({{ data.store_code }})</span>
      </template>
    </Column>

    <Column field="store_code" :header="t('Code')" class="hidden md:table-cell">
      <template #body="{ data }">
        {{ data.store_code }}
      </template>
    </Column>

    <Column field="quantity" :header="t('Quantity')">
      <template #body="{ data }">
        <span class="font-bold" :class="{ 'text-red-500': data.quantity <= 0 }">{{ data.quantity }}</span>
      </template>
    </Column>
  </DataTable>
</template>