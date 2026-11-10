<script setup lang="ts">
import { computed } from "vue";
import Chart from "primevue/chart";
import type { ChartOptions } from "chart.js";
import { useI18n } from "vue-i18n";
import moment from "moment-timezone";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { useLayout } from "@/Layouts/Components/Composables/useLayout";
import type { SalesTrend } from "@/Types/dashboard-types";

const props = defineProps<{ trend: SalesTrend; timezone: string }>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { isDarkMode } = useLayout();
const label = (at: string) =>
  moment(at)
    .tz(props.timezone)
    .format(props.trend.granularity === "hour" ? "HH:mm" : "DD/MM");
const hasSales = computed(() => props.trend.points.some((point) => point.sales !== 0 || (point.comparison ?? 0) !== 0));
const chartData = computed(() => ({
  labels: props.trend.points.map((point) => label(point.at)),
  datasets: [
    {
      label: t("Selected period"),
      data: props.trend.points.map((point) => point.sales),
      borderColor: isDarkMode.value ? "#92b8da" : "#00539b",
      backgroundColor: "transparent",
      borderWidth: 2.5,
      pointRadius: 2,
      tension: 0.15,
    },
    ...(props.trend.points.some((point) => point.comparison !== null)
      ? [
          {
            label: t("Comparison period"),
            data: props.trend.points.map((point) => point.comparison),
            borderColor: isDarkMode.value ? "#94a3b8" : "#64748b",
            borderDash: [6, 5],
            borderWidth: 2,
            pointRadius: 0,
          },
        ]
      : []),
  ],
}));
const options = computed<ChartOptions<"line">>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  animation: false,
  interaction: { intersect: false, mode: "index" },
  plugins: {
    legend: { display: true, position: "bottom", labels: { color: isDarkMode.value ? "#f8fafc" : "#1e293b", font: { family: "Lato" } } },
    tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${formatCurrency(String(context.parsed.y ?? 0))}` } },
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: isDarkMode.value ? "#94a3b8" : "#64748b", maxTicksLimit: 7 } },
    y: {
      beginAtZero: true,
      grid: { color: isDarkMode.value ? "#334155" : "#e2e8f0" },
      ticks: { color: isDarkMode.value ? "#94a3b8" : "#64748b", maxTicksLimit: 5 },
    },
  },
}));
</script>

<template>
  <section
    class="min-w-0 rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-800"
    aria-labelledby="sales-trend-title"
  >
    <h2 id="sales-trend-title" class="m-0 text-xl font-bold">{{ t("Sales trend") }}</h2>
    <p class="mb-4 mt-1 text-sm text-surface-600 dark:text-surface-300">{{ t("Fulfilled sales, excluding tax") }}</p>
    <Chart
      v-if="hasSales"
      type="line"
      :data="chartData"
      :options="options"
      class="h-64"
      :canvas-props="{ role: 'img', 'aria-label': t('Net sales by reporting interval') }"
    />
    <div v-else class="flex h-64 flex-col items-center justify-center gap-2 text-center text-surface-600 dark:text-surface-300">
      <i class="fa fa-chart-line text-2xl" aria-hidden="true" />
      <p class="m-0 font-medium">{{ t("No sales in this period") }}</p>
      <p class="m-0 text-sm">{{ t("Fulfilled sales will appear here. Try another date range.") }}</p>
    </div>
    <details v-if="hasSales" class="mt-3 text-sm">
      <summary class="cursor-pointer text-primary-700 dark:text-primary-200">{{ t("View chart data") }}</summary>
      <div class="mt-2 max-h-52 overflow-auto">
        <table class="w-full text-left tabular-nums">
          <caption class="sr-only">{{ t("Net sales by reporting interval") }}</caption>
          <thead>
            <tr>
              <th scope="col" class="py-2">{{ t("Time") }}</th>
              <th scope="col">{{ t("Net sales") }}</th>
              <th scope="col">{{ t("Comparison period") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="point in trend.points" :key="point.at">
              <th scope="row" class="py-1 font-normal">{{ label(point.at) }}</th>
              <td>{{ formatCurrency(String(point.sales)) }}</td>
              <td>{{ point.comparison === null ? "—" : formatCurrency(String(point.comparison)) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </details>
  </section>
</template>
