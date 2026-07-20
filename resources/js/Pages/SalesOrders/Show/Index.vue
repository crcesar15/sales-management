<script setup lang="ts">
import { Button } from "primevue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import AppLayout from "@layouts/admin.vue";
import type { SalesOrderResponse } from "@/Types/sales-order-types";
import OrderTotalsCard from "../Components/OrderTotalsCard.vue";
import SalesOrderStatusStepper from "../Components/SalesOrderStatusStepper.vue";
import SalesOrderSummaryCard from "../Components/SalesOrderSummaryCard.vue";

defineOptions({ layout: AppLayout });

defineProps<{ order: SalesOrderResponse }>();
const { t } = useI18n();
</script>

<template>
  <div>
    <div class="mb-3 flex items-center gap-3">
      <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="router.visit(route('sales-orders'))" />
      <h2 class="m-0 text-2xl font-bold">{{ t("Sales Order") }} #{{ order.id }}</h2>
    </div>

    <SalesOrderStatusStepper :status="order.status" />

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 flex flex-col gap-4 lg:col-span-8">
        <SalesOrderSummaryCard :order="order" />
      </div>
      <div class="col-span-12 lg:col-span-4"><OrderTotalsCard :sub-total="order.sub_total" :discount="order.discount" :tax-amount="order.tax_amount" :total="order.total" :discount-type="order.discount_type" :discount-value="order.discount_value" /></div>
    </div>
  </div>
</template>
