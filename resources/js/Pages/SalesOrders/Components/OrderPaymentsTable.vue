<script setup lang="ts">
import { DataTable, Column } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { computed } from "vue";
import type { PaymentMethod } from "@/Types/purchase-order-types";
import type { SalesOrderPayment } from "@/Types/sales-order-types";

const props = defineProps<{ payments: SalesOrderPayment[]; outstandingBalance: number }>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const paymentsTotal = computed(() => props.payments.reduce((total, payment) => total + payment.amount, 0));

function paymentMethodLabel(method: PaymentMethod): string {
  const labels: Record<string, string> = {
    cash: t("Cash"),
    credit_card: t("Credit Card"),
    qr: t("QR"),
    transfer: t("Transfer"),
  };
  return labels[method] ?? method;
}
</script>

<template>
  <DataTable :value="payments" data-key="id" striped-rows row-hover>
    <template #empty>
      <div class="flex flex-row gap-2 justify-center items-center py-1 text-surface-400">
        <i class="fa fa-credit-card text-3xl"></i>
        <span>{{ t("No payments") }}</span>
      </div>
    </template>
    <Column :header="t('Payment Method')" style="min-width: 150px" :pt="{headerCell: {class: '!bg-surface-200 dark:!bg-surface-800'}}">
      <template #body="{ data }">
        {{ paymentMethodLabel(data.payment_method) }}
      </template>
    </Column>
    <Column :header="t('Amount')" style="min-width: 120px" :pt="{headerCell: {class: '!bg-surface-200 dark:!bg-surface-800'}}">
      <template #body="{ data }">
        {{ formatCurrency(String(data.amount)) }}
      </template>
    </Column>
    <Column :header="t('Reference')" style="min-width: 150px" :pt="{headerCell: {class: '!bg-surface-200 dark:!bg-surface-800'}}">
      <template #body="{ data }">
        {{ data.reference ?? "---" }}
      </template>
    </Column>
  </DataTable>
  <div class="mt-3 flex flex-col items-end gap-1">
    <div class="flex w-full max-w-xs justify-between gap-4">
      <span class="text-surface-500 dark:text-surface-400">{{ t("Payments received") }}</span>
      <span class="font-medium">{{ formatCurrency(String(paymentsTotal)) }}</span>
    </div>
    <div class="flex w-full max-w-xs justify-between gap-4">
      <span class="text-surface-500 dark:text-surface-400">{{ t("Outstanding Balance") }}</span>
      <span class="font-semibold">{{ formatCurrency(String(outstandingBalance)) }}</span>
    </div>
  </div>
</template>
