<script setup lang="ts">
import { DataTable, Column } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import type { PaymentMethod } from "@/Types/purchase-order-types";
import type { SalesOrderPayment } from "@/Types/sales-order-types";

defineProps<{ payments: SalesOrderPayment[]; total: number }>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

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
      <div class="flex flex-col items-center py-6 text-surface-400">
        <i class="fa fa-credit-card text-3xl mb-2"></i>
        <span>{{ t("No payments") }}</span>
      </div>
    </template>
    <Column :header="t('Payment Method')" style="min-width: 150px">
      <template #body="{ data }">
        {{ paymentMethodLabel(data.payment_method) }}
      </template>
    </Column>
    <Column :header="t('Amount')" style="min-width: 120px">
      <template #body="{ data }">
        {{ formatCurrency(String(data.amount)) }}
      </template>
    </Column>
    <Column :header="t('Reference')" style="min-width: 150px">
      <template #body="{ data }">
        {{ data.reference ?? "---" }}
      </template>
    </Column>
    <Column :header="t('Total')" style="min-width: 120px" footer-style="font-weight: bold">
      <template #footer>
        {{ formatCurrency(String(total)) }}
      </template>
    </Column>
  </DataTable>
</template>