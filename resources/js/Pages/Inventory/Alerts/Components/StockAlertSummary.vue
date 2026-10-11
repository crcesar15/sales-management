<script setup lang="ts">
import type { AlertsSummary } from "@/Types/stock-alert-types";
import { useI18n } from "vue-i18n";
import { computed } from "vue";

const props = defineProps<{
  summary: AlertsSummary;
}>();

const { t } = useI18n();
const hasAlerts = computed(() => props.summary.total > 0);
</script>

<template>
  <div
    class="flex flex-col gap-3 rounded-lg border border-surface-200 bg-surface-0 px-4 py-3 sm:flex-row sm:items-center sm:gap-5 dark:border-surface-700 dark:bg-surface-900"
    aria-live="polite"
  >
    <div class="flex min-w-0 items-center gap-3">
      <i
        class="fa text-lg"
        :class="hasAlerts ? 'fa-triangle-exclamation text-red-500' : 'fa-circle-check text-green-600 dark:text-green-400'"
        aria-hidden="true"
      />
      <p class="m-0 font-semibold text-surface-900 dark:text-surface-0">
        {{ hasAlerts ? t("Active stock alerts: {count}", { count: summary.total }) : t("No active stock alerts") }}
      </p>
    </div>
    <div class="hidden h-6 w-px bg-surface-200 sm:block dark:bg-surface-700" aria-hidden="true" />
    <dl class="m-0 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
      <div class="flex items-center gap-2">
        <span
          class="h-2 w-2 rounded-full"
          :class="summary.low_stock_count > 0 ? 'bg-orange-500' : 'bg-surface-300 dark:bg-surface-600'"
          aria-hidden="true"
        />
        <dt class="text-surface-600 dark:text-surface-300">{{ t("Low Stock") }}</dt>
        <dd class="m-0 font-semibold text-surface-900 dark:text-surface-0">{{ summary.low_stock_count }}</dd>
      </div>
      <div class="flex items-center gap-2">
        <span
          class="h-2 w-2 rounded-full"
          :class="summary.expiry_count > 0 ? 'bg-red-500' : 'bg-surface-300 dark:bg-surface-600'"
          aria-hidden="true"
        />
        <dt class="text-surface-600 dark:text-surface-300">{{ t("Expiring Soon") }}</dt>
        <dd class="m-0 font-semibold text-surface-900 dark:text-surface-0">{{ summary.expiry_count }}</dd>
      </div>
    </dl>
  </div>
</template>
