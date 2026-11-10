<script setup lang="ts">
import { computed } from "vue";
import { Button } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import type { SalesSummary } from "@/Types/dashboard-types";

const props = defineProps<{ sales: SalesSummary; comparison: SalesSummary | null }>();
const emit = defineEmits<{ details: [] }>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const change = computed(() => {
  if (!props.comparison) return null;
  const previous = props.comparison.net_sales;
  if (previous === 0) return props.sales.net_sales === 0 ? t("No change") : t("No prior sales");
  const value = ((props.sales.net_sales - previous) / previous) * 100;
  return `${value > 0 ? "+" : ""}${value.toFixed(1)}% ${t("vs comparison")}`;
});
</script>

<template>
  <section
    :aria-label="t('Sales overview')"
    class="rounded-lg border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-800"
  >
    <dl
      class="m-0 grid grid-cols-1 divide-y divide-surface-200 sm:grid-cols-2 sm:divide-y-0 dark:divide-surface-700"
      :class="sales.profitability ? 'xl:grid-cols-4' : 'xl:grid-cols-3'"
    >
      <div class="min-w-0 p-5">
        <dt class="text-sm font-medium text-surface-600 dark:text-surface-300">{{ t("Net sales") }}</dt>
        <dd class="m-0 mt-2 break-words text-2xl font-bold tabular-nums">{{ formatCurrency(String(sales.net_sales)) }}</dd>
        <p class="m-0 mt-2 text-sm text-surface-600 dark:text-surface-300">{{ change ?? t("After discounts, excluding tax") }}</p>
      </div>
      <div class="min-w-0 p-5">
        <dt class="text-sm font-medium text-surface-600 dark:text-surface-300">{{ t("Fulfilled orders") }}</dt>
        <dd class="m-0 mt-2 text-2xl font-bold tabular-nums">{{ sales.order_count }}</dd>
        <Button :label="t('View sales')" link size="small" class="!mt-1 !p-0 dark:!text-primary-200" @click="emit('details')" />
      </div>
      <div class="min-w-0 p-5">
        <dt class="text-sm font-medium text-surface-600 dark:text-surface-300">{{ t("Average sale") }}</dt>
        <dd class="m-0 mt-2 break-words text-2xl font-bold tabular-nums">
          {{ sales.average_sale === null ? "—" : formatCurrency(String(sales.average_sale)) }}
        </dd>
        <p class="m-0 mt-2 text-sm text-surface-600 dark:text-surface-300">{{ t("Per fulfilled order") }}</p>
      </div>
      <div v-if="sales.profitability" class="min-w-0 p-5">
        <dt class="text-sm font-medium text-surface-600 dark:text-surface-300">{{ t("Gross profit") }}</dt>
        <dd
          class="m-0 mt-2 break-words text-2xl font-bold tabular-nums"
          :class="{ 'text-red-700 dark:text-red-300': (sales.profitability.gross_profit ?? 0) < 0 }"
        >
          {{ sales.profitability.complete ? formatCurrency(String(sales.profitability.gross_profit)) : t("Incomplete costs") }}
        </dd>
        <p class="m-0 mt-2 text-sm text-surface-600 dark:text-surface-300">
          {{
            sales.profitability.complete
              ? t("Gross margin: {margin}", {
                  margin: sales.profitability.margin === null ? "—" : `${sales.profitability.margin.toFixed(1)}%`,
                })
              : t("{count} lines need historical costs", { count: sales.profitability.missing_lines })
          }}
        </p>
      </div>
    </dl>
    <p
      v-if="sales.profitability && !sales.profitability.complete"
      class="m-0 border-t border-surface-200 px-5 py-3 text-sm dark:border-surface-700"
    >
      {{
        t("Known-cost profit: {amount}. Cost coverage: {coverage}%. Total profit is unavailable until costs are complete.", {
          amount: formatCurrency(String(sales.profitability.known_profit)),
          coverage: sales.profitability.cost_coverage,
        })
      }}
    </p>
  </section>
</template>
