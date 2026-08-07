<script setup lang="ts">
import { Badge, DataTable, Column } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import type { SalesOrderItem } from "@/Types/sales-order-types";

defineProps<{ items: SalesOrderItem[] }>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
</script>

<template>
  <div class="xl:hidden">
    <div v-if="items.length === 0" class="flex flex-col items-center py-10 text-surface-400">
      <i class="fa fa-box-open mb-3 text-4xl" aria-hidden="true"></i>
      <span class="text-lg font-medium">{{ t("No items") }}</span>
    </div>

    <ul v-else class="divide-y divide-surface-200 border-y border-surface-200 dark:divide-surface-700 dark:border-surface-700">
      <li v-for="item in items" :key="item.id" class="flex flex-col gap-4 px-1 py-4">
        <div class="flex min-w-0 items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex min-w-0 items-center gap-2">
              <span class="truncate text-[16px] font-semibold text-surface-900 dark:text-surface-50">
                {{ item.product_variant?.product?.name ?? "---" }}
              </span>
              <Badge
                v-if="item.product_variant?.option_values ?? item.product_variant?.identifier"
                :value="item.product_variant?.option_values ?? item.product_variant?.identifier"
                severity="primary"
                class="shrink-0 !text-[14px] !font-semibold"
              />
            </div>
          </div>
          <div class="flex shrink-0 flex-row items-end gap-1 text-right text-[14px]">
            <span v-if="item.product_variant?.product?.brand?.name" class="pr-2 font-semibold">
              <i class="fa fa-tag" aria-hidden="true"></i> {{ item.product_variant.product.brand.name }}
            </span>
            <span class="font-semibold text-surface-700 dark:text-surface-200">
              <i class="fa fa-weight-hanging" aria-hidden="true"></i>
              {{ item.sale_unit?.name ?? item.product_variant?.product?.measurement_unit?.name ?? "---" }}
              <span v-if="item.sale_unit && item.sale_unit.conversion_factor !== 1" class="font-normal"> ×{{ item.sale_unit.conversion_factor }} </span>
              <span v-if="item.sale_unit && item.product_variant?.product?.measurement_unit" class="pl-1 text-surface-600 dark:text-surface-300">
                {{ item.product_variant.product.measurement_unit.name }}
              </span>
            </span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-[14px]">
          <div class="min-w-0">
            <span class="block text-surface-500 dark:text-surface-400">{{ t("Quantity") }}</span>
            <span class="font-medium text-surface-900 dark:text-surface-50">{{ item.quantity }}</span>
          </div>
          <div class="min-w-0 text-right">
            <span class="block text-surface-500 dark:text-surface-400">{{ t("Unit Price") }}</span>
            <span class="font-medium tabular-nums text-surface-900 dark:text-surface-50">{{ formatCurrency(String(item.unit_price)) }}</span>
          </div>
          <div v-if="item.sale_unit && item.product_variant?.product?.measurement_unit" class="col-span-2 min-w-0 text-surface-500 dark:text-surface-400">
            1 {{ item.sale_unit.name }} = {{ item.sale_unit.conversion_factor }} {{ item.product_variant.product.measurement_unit.name }}
          </div>
          <div class="col-span-2 flex items-center justify-between border-t border-surface-200 pt-3 dark:border-surface-700 text-[16px]">
            <span class="font-medium text-surface-600 dark:text-surface-300">{{ t("Line Total") }}</span>
            <span class="font-semibold tabular-nums text-surface-900 dark:text-surface-50">
              {{ formatCurrency(String(item.line_total)) }}
            </span>
          </div>
        </div>
      </li>
    </ul>
  </div>

  <div class="hidden xl:block">
    <DataTable :value="items" data-key="id" striped-rows row-hover>
      <template #empty>
        <div class="flex flex-col items-center py-6 text-surface-400">
          <i class="fa fa-box-open mb-2 text-3xl" aria-hidden="true"></i>
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
  </div>
</template>
