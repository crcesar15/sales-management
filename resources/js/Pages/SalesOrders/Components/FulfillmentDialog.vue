<script setup lang="ts">
import { Button, Column, DataTable, Dialog } from "primevue";
import { computed } from "vue";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { useI18n } from "vue-i18n";
import type { SalesOrderResponse } from "@/Types/sales-order-types";

const props = defineProps<{ visible: boolean; order: SalesOrderResponse; processing?: boolean }>();
const emit = defineEmits<{ "update:visible": [visible: boolean]; confirm: [] }>();
const { t } = useI18n();
const { formatDate } = useDatetimeFormatter();

const allocations = computed(() =>
  (props.order.items ?? []).flatMap((item) =>
    (item.stock_allocations ?? []).map((allocation) => ({
      id: `${item.id}-${allocation.batch_id}`,
      product: item.product_variant?.product?.name ?? item.product_variant?.name ?? "---",
      quantity: allocation.quantity,
      baseUnit: item.product_variant?.product?.measurement_unit?.name ?? t("Unit"),
      batch: allocation.batch?.identifier ?? `#${allocation.batch_id}`,
      expiry: allocation.batch?.expiry_date ?? null,
    })),
  ),
);
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="t('Product Handover')"
    class="w-full max-w-4xl"
    @update:visible="emit('update:visible', $event)"
  >
    <p class="mb-4 text-surface-500 dark:text-surface-400">{{ t("Review the allocated stock before confirming handover.") }}</p>
    <DataTable :value="allocations" data-key="id" striped-rows>
      <Column field="product" :header="t('Product')" />
      <Column :header="t('Quantity')">
        <template #body="{ data }">
          {{ data.quantity }}
          <span class="text-surface-400 dark:text-surface-500 font-medium text-sm">({{ data.baseUnit }})</span>
        </template>
      </Column>
      <Column field="batch" :header="t('Batch Identifier')" />
      <Column :header="t('Expiry')">
        <template #body="{ data }">{{ data.expiry ? formatDate(data.expiry) : "---" }}</template>
      </Column>
    </DataTable>
    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" @click="emit('update:visible', false)" />
      <Button :label="t('Confirm Handover')" :loading="processing" @click="emit('confirm')" />
    </template>
  </Dialog>
</template>
