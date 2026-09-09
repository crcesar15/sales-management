<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import type { SalesOrderPayment, SalesOrderResponse } from "@/Types/sales-order-types";

const props = defineProps<{
  order: SalesOrderResponse;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { formatDatetime } = useDatetimeFormatter();
const cashPayment = computed(() => props.order.payments?.find((payment) => payment.payment_method === "cash"));

function paymentLabel(payment: SalesOrderPayment): string {
  return payment.payment_method === "cash" ? t("Cash") : t("QR");
}

function itemName(index: number): string {
  const item = props.order.items?.[index];
  const product = item?.product_variant?.product?.name ?? t("Product");
  const variant = item?.product_variant?.option_values || item?.product_variant?.identifier;

  return variant ? `${product} · ${variant}` : product;
}
</script>

<template>
  <article class="receipt-sheet rounded-lg border border-surface-200 bg-surface-0 p-6 dark:border-surface-700 dark:bg-surface-900">
    <header class="border-b border-surface-200 pb-5 text-center dark:border-surface-700">
      <i class="fa fa-circle-check text-3xl text-green-600 dark:text-green-400" aria-hidden="true" />
      <h1 class="m-0 mt-2 text-2xl font-bold">{{ t("Payment complete") }}</h1>
      <p class="m-0 mt-1 text-surface-500 dark:text-surface-400">{{ t("Sale") }} #{{ order.id }} · {{ formatDatetime(order.completed_at) }}</p>
    </header>

    <dl class="grid grid-cols-2 gap-x-6 gap-y-2 border-b border-surface-200 py-5 text-sm dark:border-surface-700">
      <dt class="text-surface-500 dark:text-surface-400">{{ t("Store") }}</dt>
      <dd class="m-0 text-right font-medium">{{ order.store?.name }}</dd>
      <dt class="text-surface-500 dark:text-surface-400">{{ t("Register") }}</dt>
      <dd class="m-0 text-right font-medium">{{ order.cash_register_shift?.cash_register?.name }}</dd>
      <dt class="text-surface-500 dark:text-surface-400">{{ t("Cashier") }}</dt>
      <dd class="m-0 text-right font-medium">{{ order.user?.full_name }}</dd>
      <dt class="text-surface-500 dark:text-surface-400">{{ t("Customer") }}</dt>
      <dd class="m-0 text-right font-medium">{{ order.customer?.display_name ?? t("Walk-in customer") }}</dd>
    </dl>

    <section class="py-5" :aria-label="t('Items')">
      <div v-for="(item, index) in order.items" :key="item.id" class="flex justify-between gap-4 py-2">
        <div class="min-w-0">
          <div class="font-medium">{{ itemName(index) }}</div>
          <small class="text-surface-500 dark:text-surface-400">
            {{ item.quantity }} {{ item.sale_unit?.name ?? item.product_variant?.product?.measurement_unit?.name }} ×
            {{ formatCurrency(String(item.unit_price)) }}
          </small>
        </div>
        <span class="font-medium tabular-nums">{{ formatCurrency(String(item.line_total)) }}</span>
      </div>
    </section>

    <dl class="flex flex-col gap-2 border-y border-surface-200 py-5 dark:border-surface-700">
      <div class="flex justify-between gap-4">
        <dt>{{ t("Subtotal") }}</dt>
        <dd class="m-0 tabular-nums">{{ formatCurrency(String(order.sub_total)) }}</dd>
      </div>
      <div class="flex justify-between gap-4">
        <dt>{{ t("Discount") }}</dt>
        <dd class="m-0 tabular-nums">−{{ formatCurrency(String(order.discount)) }}</dd>
      </div>
      <div class="flex justify-between gap-4">
        <dt>{{ t("Tax") }}</dt>
        <dd class="m-0 tabular-nums">{{ formatCurrency(String(order.tax_amount)) }}</dd>
      </div>
      <div class="mt-2 flex justify-between gap-4 text-xl font-bold">
        <dt>{{ t("Total") }}</dt>
        <dd class="m-0 tabular-nums">{{ formatCurrency(String(order.total)) }}</dd>
      </div>
    </dl>

    <section class="pt-5" :aria-label="t('Payments')">
      <div v-for="payment in order.payments" :key="payment.id" class="flex justify-between gap-4 py-1">
        <span>
          {{ paymentLabel(payment) }}
          <small v-if="payment.reference" class="ml-2 text-surface-500 dark:text-surface-400">{{ payment.reference }}</small>
        </span>
        <span class="font-medium tabular-nums">{{ formatCurrency(String(payment.amount)) }}</span>
      </div>
      <div v-if="cashPayment?.tendered_amount !== null && cashPayment?.tendered_amount !== undefined" class="mt-3 flex justify-between gap-4">
        <span>{{ t("Cash received") }}</span>
        <span class="tabular-nums">{{ formatCurrency(String(cashPayment.tendered_amount)) }}</span>
      </div>
      <div v-if="cashPayment && cashPayment.change_amount > 0" class="mt-1 flex justify-between gap-4 text-lg font-bold">
        <span>{{ t("Change due") }}</span>
        <span class="tabular-nums">{{ formatCurrency(String(cashPayment.change_amount)) }}</span>
      </div>
    </section>
  </article>
</template>

<style scoped>
@media print {
  :global(body *) {
    visibility: hidden;
  }

  .receipt-sheet,
  .receipt-sheet * {
    visibility: visible;
  }

  .receipt-sheet {
    position: absolute;
    inset: 0;
    width: 100%;
    border: 0;
    color: #000;
    background: #fff;
  }
}
</style>
