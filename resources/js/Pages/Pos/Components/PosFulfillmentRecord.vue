<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import FulfillmentAllocationList from "@pages/SalesOrders/Components/FulfillmentAllocationList.vue";
import type { SalesOrderHandoverAllocation, SalesOrderResponse } from "@/Types/sales-order-types";

const props = defineProps<{
  order: SalesOrderResponse;
}>();

const { t } = useI18n();
const allocations = computed<SalesOrderHandoverAllocation[]>(() =>
  (props.order.items ?? []).flatMap((item) => {
    const product = item.product_variant?.product;

    return (item.stock_allocations ?? []).map((allocation) => ({
      sales_order_item_id: item.id,
      batch_id: allocation.batch_id,
      quantity: allocation.quantity,
      product: product?.name ?? t("Product"),
      variant: item.product_variant?.name ?? item.product_variant?.identifier ?? "---",
      brand: product?.brand?.name ?? null,
      base_unit: product?.measurement_unit?.name ?? t("Unit"),
      batch_identifier: allocation.batch?.identifier ?? `#${allocation.batch_id}`,
      expiry_date: allocation.batch?.expiry_date ?? null,
    }));
  }),
);
</script>

<template>
  <section
    v-if="allocations.length > 0"
    class="rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900"
    :aria-label="t('Fulfilled batches')"
  >
    <header class="mb-4 flex items-start gap-3">
      <i class="fa fa-circle-check mt-1 text-lg text-green-600 dark:text-green-400" aria-hidden="true" />
      <div>
        <h2 class="m-0 text-lg font-bold">{{ t("Fulfilled batches") }}</h2>
        <p class="m-0 mt-1 text-sm text-surface-500 dark:text-surface-400">{{ t("These batches were deducted from inventory.") }}</p>
      </div>
    </header>
    <FulfillmentAllocationList :allocations="allocations" compact />
  </section>
</template>
