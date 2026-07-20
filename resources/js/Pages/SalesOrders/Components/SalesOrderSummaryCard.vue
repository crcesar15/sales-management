<script setup lang="ts">
import { Card } from "primevue";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { useI18n } from "vue-i18n";
import type { SalesOrderResponse } from "@/Types/sales-order-types";
import OrderStatusBadge from "./OrderStatusBadge.vue";
import PaymentStatusBadge from "./PaymentStatusBadge.vue";

const props = defineProps<{ order: SalesOrderResponse }>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { formatDatetime } = useDatetimeFormatter();

function customerName(): string {
  return props.order.customer?.display_name ?? t("Walk-in");
}
</script>

<template>
  <Card>
    <template #title>{{ t("Summary") }}</template>
    <template #content>
      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div><dt class="text-sm text-surface-500">{{ t("Order #") }}</dt><dd class="font-medium">#{{ order.id }}</dd></div>
        <div><dt class="text-sm text-surface-500">{{ t("Status") }}</dt><dd><OrderStatusBadge :status="order.status" /></dd></div>
        <div><dt class="text-sm text-surface-500">{{ t("Payment") }}</dt><dd><PaymentStatusBadge :status="order.payment_status" /></dd></div>
        <div><dt class="text-sm text-surface-500">{{ t("Customer") }}</dt><dd class="font-medium">{{ customerName() }}</dd></div>
        <div><dt class="text-sm text-surface-500">{{ t("Store") }}</dt><dd class="font-medium">{{ order.store?.name ?? "---" }}</dd></div>
        <div><dt class="text-sm text-surface-500">{{ t("Cashier") }}</dt><dd class="font-medium">{{ order.user?.full_name ?? "---" }}</dd></div>
        <div><dt class="text-sm text-surface-500">{{ t("Created") }}</dt><dd class="font-medium">{{ formatDatetime(order.created_at) }}</dd></div>
        <div v-if="order.fulfilled_at"><dt class="text-sm text-surface-500">{{ t("Fulfillment Date") }}</dt><dd class="font-medium">{{ formatDatetime(order.fulfilled_at) }}</dd></div>
        <div><dt class="text-sm text-surface-500">{{ t("Total") }}</dt><dd class="font-semibold">{{ formatCurrency(String(order.total)) }}</dd></div>
        <div><dt class="text-sm text-surface-500">{{ t("Outstanding Balance") }}</dt><dd class="font-semibold">{{ formatCurrency(String(order.outstanding_balance)) }}</dd></div>
      </dl>
    </template>
  </Card>
</template>
