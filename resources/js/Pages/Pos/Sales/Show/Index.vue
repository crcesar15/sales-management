<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { Button, Card } from "primevue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import PosLayout from "@layouts/pos.vue";
import OrderItemsTable from "@pages/SalesOrders/Components/OrderItemsTable.vue";
import OrderPaymentsTable from "@pages/SalesOrders/Components/OrderPaymentsTable.vue";
import OrderTotalsCard from "@pages/SalesOrders/Components/OrderTotalsCard.vue";
import SalesOrderStatusStepper from "@pages/SalesOrders/Components/SalesOrderStatusStepper.vue";
import SalesOrderSummaryCard from "@pages/SalesOrders/Components/SalesOrderSummaryCard.vue";
import type { SalesOrderResponse } from "@/Types/sales-order-types";

defineOptions({ layout: PosLayout });

defineProps<{ order: SalesOrderResponse }>();

const { t } = useI18n();
</script>

<template>
  <div class="p-4 lg:p-6">
    <div class="mb-4 flex items-center gap-3">
      <Button
        :aria-label="t('Back')"
        icon="fa fa-arrow-left"
        text
        rounded
        severity="secondary"
        @click="router.visit(route('pos.sales.index'))"
      />
      <h1 class="m-0 text-2xl font-bold">{{ t("Sales Order") }} #{{ order.id }}</h1>
    </div>

    <SalesOrderStatusStepper :status="order.status" />

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 flex flex-col gap-4 lg:col-span-8">
        <SalesOrderSummaryCard :order="order" />
        <Card>
          <template #title>{{ t("Items") }}</template>
          <template #content><OrderItemsTable :items="order.items ?? []" /></template>
        </Card>
        <Card>
          <template #title>{{ t("Payments") }}</template>
          <template #content>
            <OrderPaymentsTable :payments="order.payments ?? []" :outstanding-balance="order.outstanding_balance" />
          </template>
        </Card>
      </div>
      <aside class="col-span-12 lg:col-span-4">
        <div class="lg:sticky lg:top-4">
          <OrderTotalsCard
            :sub-total="order.sub_total"
            :discount="order.discount"
            :tax-amount="order.tax_amount"
            :total="order.total"
            :discount-type="order.discount_type"
            :discount-value="order.discount_value"
          />
          <Card v-if="order.cancellation_reason || order.notes" class="mt-4">
            <template #title>{{ order.cancellation_reason ? t("Cancellation Reason") : t("Notes") }}</template>
            <template #content>
              <p class="m-0 whitespace-pre-wrap text-surface-700 dark:text-surface-200">{{ order.cancellation_reason ?? order.notes }}</p>
            </template>
          </Card>
        </div>
      </aside>
    </div>
  </div>
</template>
