<script setup lang="ts">
import { Card, Button } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { computed } from "vue";
import type { SalesOrderResponse } from "@/Types/sales-order-types";
import OrderStatusBadge from "../Components/OrderStatusBadge.vue";
import OrderItemsTable from "../Components/OrderItemsTable.vue";
import OrderTotalsCard from "../Components/OrderTotalsCard.vue";
import OrderPaymentsTable from "../Components/OrderPaymentsTable.vue";
import StatusTransitionButtons from "../Components/StatusTransitionButtons.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  order: SalesOrderResponse;
}>();

const { t } = useI18n();
const { formatDatetime } = useDatetimeFormatter();

const canEdit = computed(() => props.order.status === "draft");

function goBack() {
  router.visit(route("sales-orders"));
}

function goToEdit() {
  router.visit(route("sales-orders.edit", props.order.id));
}

function shiftLabel(): string {
  const shift = props.order.cash_register_shift;
  if (!shift) return "---";
  const register = shift.cash_register;
  const registerName = register ? register.name : "---";
  const openedAt = shift.opened_at ? formatDatetime(shift.opened_at) : "---";
  return `${registerName} (${openedAt})`;
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded @click="goBack" />
        <h2 class="text-2xl font-bold m-0">{{ t("Sales Order") }} #{{ order.id }}</h2>
        <OrderStatusBadge :status="order.status" />
      </div>
      <div class="flex items-center gap-2">
        <Button v-if="canEdit" v-can="'sales.manage'" :label="t('Edit')" icon="fa fa-pen" @click="goToEdit" />
      </div>
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 lg:col-span-8">
        <Card class="mb-4">
          <template #title>{{ t("Order Details") }}</template>
          <template #content>
            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-user text-surface-400 w-4 text-center" />
                  {{ t("Customer") }}
                </span>
                <span class="font-medium">{{ order.customer?.display_name ?? t("Walk-in") }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-user-tie text-surface-400 w-4 text-center" />
                  {{ t("Cashier") }}
                </span>
                <span class="font-medium">{{ order.user?.full_name ?? "---" }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-building text-surface-400 w-4 text-center" />
                  {{ t("Store") }}
                </span>
                <span class="font-medium">{{ order.store?.name ?? "---" }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-clock text-surface-400 w-4 text-center" />
                  {{ t("Created At") }}
                </span>
                <span class="font-medium">{{ formatDatetime(order.created_at) }}</span>
              </div>
              <div v-if="order.cash_register_shift" class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-cash-register text-surface-400 w-4 text-center" />
                  {{ t("Shift") }}
                </span>
                <span class="font-medium">{{ shiftLabel() }}</span>
              </div>
              <div v-if="order.token" class="flex flex-col gap-1">
                <span class="text-sm text-surface-500 flex items-center gap-1.5">
                  <i class="fa fa-key text-surface-400 w-4 text-center" />
                  {{ t("Token") }}
                </span>
                <span class="font-medium font-mono text-sm">{{ order.token }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Items") }}</template>
          <template #content>
            <OrderItemsTable :items="order.items ?? []" />
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Payments") }}</template>
          <template #content>
            <OrderPaymentsTable :payments="order.payments ?? []" :total="order.total" />
          </template>
        </Card>

        <Card v-if="order.notes" class="mb-4">
          <template #title>{{ t("Notes") }}</template>
          <template #content>
            <p class="m-0 whitespace-pre-line text-surface-700 dark:text-surface-300">{{ order.notes }}</p>
          </template>
        </Card>
      </div>

      <div class="col-span-12 lg:col-span-4">
        <OrderTotalsCard
          :sub-total="order.sub_total"
          :discount="order.discount"
          :tax-amount="order.tax_amount"
          :total="order.total"
          :discount-type="order.discount_type"
          :discount-value="order.discount_value"
        />

        <div class="mt-4">
          <StatusTransitionButtons :order="order" @transitioned="router.reload()" />
        </div>
      </div>
    </div>
  </div>
</template>
