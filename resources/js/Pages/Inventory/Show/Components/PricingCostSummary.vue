<script setup lang="ts">
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import type { ActualStockCosts } from "@app-types/pricing-types";

defineProps<{ costs: ActualStockCosts; baseUnit: string }>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { formatDatetime } = useDatetimeFormatter();
const money = (value: number | null) => (value === null ? "—" : formatCurrency(String(value)));
</script>

<template>
  <section class="flex flex-col gap-3 border-b border-surface-200 pb-4 dark:border-surface-700" :aria-label="t('Actual inventory costs')">
    <dl class="m-0 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <dt class="text-sm text-surface-500 dark:text-surface-400">{{ t("Actual stock cost") }} / {{ baseUnit }}</dt>
        <dd class="m-0 mt-1 font-semibold tabular-nums">{{ money(costs.average) }}</dd>
      </div>
      <div>
        <dt class="text-sm text-surface-500 dark:text-surface-400">{{ t("Latest received cost") }} / {{ baseUnit }}</dt>
        <dd class="m-0 mt-1 font-semibold tabular-nums">{{ money(costs.latest_received_cost) }}</dd>
        <small v-if="costs.latest_received_at">{{ formatDatetime(costs.latest_received_at) }}</small>
      </div>
    </dl>
    <p class="m-0 text-sm text-surface-500 dark:text-surface-400">
      {{ t("Actual costs include purchase-order discounts. Replacement cost uses the normal supplier price.") }}
    </p>
    <details v-if="costs.receipts.length">
      <summary class="cursor-pointer text-sm font-medium">{{ t("Actual received cost history") }}</summary>
      <ul class="mt-3 flex list-none flex-col gap-2 p-0 text-sm">
        <li v-for="receipt in costs.receipts" :key="receipt.reception_order_id" class="flex flex-wrap justify-between gap-2">
          <span>#{{ receipt.reception_order_id }} · {{ formatDatetime(receipt.date) }}</span>
          <span class="tabular-nums">{{ money(receipt.cost) }} / {{ baseUnit }}</span>
        </li>
      </ul>
    </details>
    <p v-if="!costs.complete" class="m-0 text-sm font-medium text-orange-700 dark:text-orange-300">
      {{
        t("Partial cost coverage: {known} of {total} units have a recorded cost.", {
          known: costs.known_quantity,
          total: costs.total_quantity,
        })
      }}
    </p>
  </section>
</template>
