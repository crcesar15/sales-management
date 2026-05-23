<script setup lang="ts">
import { computed } from "vue";
import Chart from "primevue/chart";
import { useI18n } from "vue-i18n";

const props = defineProps<{
  sold: number;
  remaining: number;
  missing: number;
  initial: number;
}>();

const { t } = useI18n();

const chartData = computed(() => ({
  labels: [t("Sold Quantity"), t("Remaining Quantity"), t("Missing Quantity")],
  datasets: [
    {
      data: [props.sold, props.remaining, props.missing],
      backgroundColor: ["#3b82f6", "#22c55e", "#ef4444"],
      hoverBackgroundColor: ["#2563eb", "#16a34a", "#dc2626"],
      borderWidth: 0,
    },
  ],
}));

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  cutout: "65%",
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      callbacks: {
        label: (context: { raw: number }) => {
          const value = context.raw;
          const percent = props.initial
            ? Math.round((value / props.initial) * 100)
            : 0;
          return `${value} (${percent}%)`;
        },
      },
    },
  },
}));
</script>

<template>
  <div class="relative flex items-center justify-center" style="height: 160px">
    <Chart type="doughnut" :data="chartData" :options="chartOptions" class="w-full h-full" />
    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
      <span class="text-2xl font-bold">
        {{ initial ? Math.round(((sold + remaining + missing) / initial) * 100) : 0 }}%
      </span>
      <span class="text-xs text-surface-500">{{ t("Tracked") }}</span>
    </div>
  </div>
</template>
