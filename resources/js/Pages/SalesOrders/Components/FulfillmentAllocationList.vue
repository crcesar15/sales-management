<script setup lang="ts">
import { Badge, Column, DataTable } from "primevue";
import { useI18n } from "vue-i18n";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import type { SalesOrderHandoverAllocation } from "@/Types/sales-order-types";

defineProps<{
  allocations: SalesOrderHandoverAllocation[];
  compact?: boolean;
}>();

const { t } = useI18n();
const { formatDateOnly } = useDatetimeFormatter();
</script>

<template>
  <div>
    <div :class="{ 'xl:hidden': !compact }">
      <ul class="divide-y divide-surface-200 border-y border-surface-200 dark:divide-surface-700 dark:border-surface-700">
        <li
          v-for="allocation in allocations"
          :key="`${allocation.sales_order_item_id}-${allocation.batch_id}`"
          class="flex flex-col gap-4 px-1 py-4"
        >
          <div class="flex min-w-0 items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
              <span class="truncate text-[16px] font-bold text-surface-900 dark:text-surface-50">{{ allocation.product }}</span>
              <Badge v-if="allocation.variant" :value="allocation.variant" severity="primary" class="text-[14px] font-semibold" />
            </div>
            <span v-if="allocation.brand" class="shrink-0 text-right text-[14px] font-semibold">
              <i class="fa fa-tag" aria-hidden="true" />
              {{ allocation.brand }}
            </span>
          </div>

          <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-[14px]">
            <div class="min-w-0">
              <span class="block text-surface-500 dark:text-surface-400">{{ t("Quantity") }}</span>
              <span class="font-medium text-surface-900 dark:text-surface-50">{{ allocation.quantity }} {{ allocation.base_unit }}</span>
            </div>
            <div class="min-w-0 text-right">
              <span class="block text-surface-500 dark:text-surface-400">{{ t("Batch Identifier") }}</span>
              <span class="font-medium text-surface-900 dark:text-surface-50">{{ allocation.batch_identifier }}</span>
            </div>
            <div class="col-span-2 flex items-center justify-between border-t border-surface-200 pt-3 dark:border-surface-700">
              <span class="font-medium text-surface-600 dark:text-surface-300">{{ t("Expiry") }}</span>
              <span class="font-medium text-surface-900 dark:text-surface-50">
                {{ allocation.expiry_date ? formatDateOnly(allocation.expiry_date) : "---" }}
              </span>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <div v-if="!compact" class="hidden xl:block">
      <DataTable :value="allocations" striped-rows>
        <Column field="product" :header="t('Product')" :pt="{ headerCell: { class: '!bg-surface-200 dark:!bg-surface-800' } }">
          <template #body="{ data }">
            <div class="flex min-w-0 flex-col items-start gap-2">
              <span class="truncate text-[16px] font-bold text-surface-900 dark:text-surface-50">{{ data.product }}</span>
              <Badge v-if="data.variant" :value="data.variant" severity="primary" class="text-[14px] font-semibold" />
            </div>
          </template>
        </Column>
        <Column field="brand" :header="t('Brand')" :pt="{ headerCell: { class: '!bg-surface-200 dark:!bg-surface-800' } }" />
        <Column :header="t('Quantity')" :pt="{ headerCell: { class: '!bg-surface-200 dark:!bg-surface-800' } }">
          <template #body="{ data }">
            {{ data.quantity }}
            <span class="text-sm font-medium text-surface-400 dark:text-surface-500">({{ data.base_unit }})</span>
          </template>
        </Column>
        <Column
          field="batch_identifier"
          :header="t('Batch Identifier')"
          :pt="{ headerCell: { class: '!bg-surface-200 dark:!bg-surface-800' } }"
        />
        <Column :header="t('Expiry')" :pt="{ headerCell: { class: '!bg-surface-200 dark:!bg-surface-800' } }">
          <template #body="{ data }">{{ data.expiry_date ? formatDateOnly(data.expiry_date) : "---" }}</template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>
