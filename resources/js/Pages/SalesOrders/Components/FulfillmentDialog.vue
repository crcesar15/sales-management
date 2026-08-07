<script setup lang="ts">
import { Badge, Button, Column, DataTable, Dialog } from "primevue";
import { useI18n } from "vue-i18n";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import type { SalesOrderHandoverPreview } from "@/Types/sales-order-types";

defineProps<{ visible: boolean; preview: SalesOrderHandoverPreview | null; processing?: boolean }>();
const emit = defineEmits<{ "update:visible": [visible: boolean]; confirm: []; regenerate: [] }>();
const { t } = useI18n();
const { formatDate } = useDatetimeFormatter();
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="t('Product Handover')"
    class="w-full max-w-4xl"
    @update:visible="emit('update:visible', $event)"
  >
    <template v-if="preview">
      <p class="mb-4 text-surface-500 dark:text-surface-400 text-[14px]">{{ t("Review the allocated stock before confirming handover.") }}</p>
      <div class="xl:hidden">
        <ul class="divide-y divide-surface-200 border-y border-surface-200 dark:divide-surface-700 dark:border-surface-700">
          <li v-for="allocation in preview.allocations" :key="`${allocation.sales_order_item_id}-${allocation.batch_id}`" class="flex flex-col gap-4 px-1 py-4">
            <div class="flex min-w-0 items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex min-w-0 items-center gap-2">
                  <span class="truncate !text-[16px] font-bold text-surface-900 dark:text-surface-50">{{ allocation.product }}</span>
                  <Badge :value="allocation.variant" severity="primary" class="!text-[14px] !font-semibold" />
                </div>
              </div>
              <span v-if="allocation.brand" class="shrink-0 text-right text-[14px] font-semibold">
                <i class="fa fa-tag" aria-hidden="true"></i> {{ allocation.brand }}
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
                <span class="font-medium text-surface-900 dark:text-surface-50">{{ allocation.expiry_date ? formatDate(allocation.expiry_date) : "---" }}</span>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <div class="hidden xl:block">
        <DataTable :value="preview.allocations" striped-rows>
          <Column field="product" :header="t('Product')" />
          <Column :header="t('Quantity')">
            <template #body="{ data }">
              {{ data.quantity }}
              <span class="text-sm font-medium text-surface-400 dark:text-surface-500">({{ data.base_unit }})</span>
            </template>
          </Column>
          <Column field="batch_identifier" :header="t('Batch Identifier')" />
          <Column :header="t('Expiry')">
            <template #body="{ data }">{{ data.expiry_date ? formatDate(data.expiry_date) : "---" }}</template>
          </Column>
        </DataTable>
      </div>
    </template>
    <p v-else class="m-0 text-surface-500 dark:text-surface-400">
      {{ t("The handover list is no longer available. Generate a new list.") }}
    </p>
    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" @click="emit('update:visible', false)" />
      <Button
        :label="preview ? t('Confirm Handover') : t('Regenerate Handover List')"
        :loading="processing"
        @click="preview ? emit('confirm') : emit('regenerate')"
      />
    </template>
  </Dialog>
</template>
