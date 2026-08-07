<script setup lang="ts">
import { DataTable, Column } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import type { SalesOrderItem } from "@/Types/sales-order-types";

defineProps<{ items: SalesOrderItem[] }>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
</script>

<template>
  <DataTable :value="items" data-key="id" striped-rows row-hover>
    <template #empty>
      <div class="flex flex-col items-center py-6 text-surface-400">
        <i class="fa fa-box-open text-3xl mb-2"></i>
        <span>{{ t("No items") }}</span>
      </div>
    </template>
    <Column :header="t('Product')" style="min-width: 200px">
      <template #body="{ data }">
        <span class="font-medium">{{ data.product_variant?.product?.name ?? "---" }}</span>
        <div class="text-sm text-surface-500">{{ data.product_variant?.name ?? data.product_variant?.identifier ?? "---" }}</div>
      </template>
    </Column>
    <Column :header="t('Unit')" style="min-width: 120px">
      <template #body="{ data }">
        <div>
          <div>{{ data.sale_unit?.name ?? data.product_variant?.product?.measurement_unit?.name ?? "---" }}</div>
          <small v-if="data.sale_unit && data.product_variant?.product?.measurement_unit" class="text-surface-500 dark:text-surface-400">
            1 {{ data.sale_unit.name }} = {{ data.sale_unit.conversion_factor }} {{ data.product_variant.product.measurement_unit.name }}
          </small>
        </div>
      </template>
    </Column>
    <Column :header="t('Quantity')" style="min-width: 80px">
      <template #body="{ data }">
        {{ data.quantity }}
      </template>
    </Column>
    <Column :header="t('Unit Price')" style="min-width: 120px">
      <template #body="{ data }">
        {{ formatCurrency(String(data.unit_price)) }}
      </template>
    </Column>
    <Column :header="t('Line Total')" style="min-width: 120px">
      <template #body="{ data }">
        <span class="font-medium">{{ formatCurrency(String(data.line_total)) }}</span>
      </template>
    </Column>
  </DataTable>
</template>
