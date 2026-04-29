<script setup lang="ts">
import { Card } from "primevue";
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import type { AlertsSummary } from "@/Types/stock-alert-types";

defineProps<{
  summary: AlertsSummary;
}>();

const { t } = useI18n();
</script>

<template>
  <Link :href="route('inventory.alerts')" class="block no-underline">
    <Card class="hover:shadow-md transition-shadow cursor-pointer">
      <template #content>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold m-0">{{ t("Stock Alerts") }}</h3>
          <span v-if="summary.total > 0" class="text-xs font-bold px-2 py-1 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
            {{ summary.total }}
          </span>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg" :class="summary.low_stock_count > 0 ? 'bg-orange-100 dark:bg-orange-900/30' : 'bg-surface-100 dark:bg-surface-800'">
              <i class="fa fa-arrow-down" :class="summary.low_stock_count > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-surface-400'" />
            </div>
            <div>
              <p class="text-2xl font-bold m-0" :class="summary.low_stock_count > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-surface-400'">
                {{ summary.low_stock_count }}
              </p>
              <p class="text-xs text-surface-500 m-0">{{ t("Low Stock") }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg" :class="summary.expiry_count > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-surface-100 dark:bg-surface-800'">
              <i class="fa fa-clock" :class="summary.expiry_count > 0 ? 'text-red-600 dark:text-red-400' : 'text-surface-400'" />
            </div>
            <div>
              <p class="text-2xl font-bold m-0" :class="summary.expiry_count > 0 ? 'text-red-600 dark:text-red-400' : 'text-surface-400'">
                {{ summary.expiry_count }}
              </p>
              <p class="text-xs text-surface-500 m-0">{{ t("Expiring Soon") }}</p>
            </div>
          </div>
        </div>
      </template>
    </Card>
  </Link>
</template>
