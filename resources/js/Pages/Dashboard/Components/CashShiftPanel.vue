<script setup lang="ts">
import { Button } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@/Composables/useDatetimeFormatter";
import type { DashboardCollections, DashboardShifts } from "@/Types/dashboard-types";
defineProps<{ collections: DashboardCollections | null; shifts: DashboardShifts | null }>();
const emit = defineEmits<{ shift: [id: number]; discrepancies: [] }>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { formatDatetime } = useDatetimeFormatter();
const methodLabels = { cash: "Cash", credit_card: "Credit Card", qr: "QR", transfer: "Transfer" };
</script>

<template>
  <section
    class="min-w-0 rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-800"
    aria-labelledby="cash-shift-title"
  >
    <h2 id="cash-shift-title" class="m-0 text-xl font-bold">{{ t("Collections and shifts") }}</h2>
    <template v-if="collections">
      <div class="mt-4 flex flex-wrap items-baseline justify-between gap-2">
        <h3 class="m-0 text-base font-medium">{{ t("Payments received") }}</h3>
        <strong class="text-xl tabular-nums">{{ formatCurrency(String(collections.total)) }}</strong>
      </div>
      <p class="mb-3 mt-1 text-sm text-surface-600 dark:text-surface-300">
        {{ t("Selected period · includes tax and credit collections") }}
      </p>
      <dl class="m-0 flex flex-wrap gap-x-6 gap-y-2 text-sm">
        <div v-for="method in collections.methods" :key="method.method">
          <dt class="text-surface-600 dark:text-surface-300">{{ t(methodLabels[method.method]) }}</dt>
          <dd class="m-0 mt-1 font-medium tabular-nums">{{ formatCurrency(String(method.amount)) }}</dd>
        </div>
      </dl>
    </template>
    <template v-if="shifts">
      <div class="mt-5 border-t border-surface-200 pt-4 dark:border-surface-700">
        <div class="flex items-baseline justify-between gap-3">
          <h3 class="m-0 text-base font-medium">
            {{ t("Open shifts") }}
            <span class="text-surface-600 dark:text-surface-300">({{ shifts.open_count }})</span>
          </h3>
          <span class="text-sm text-surface-600 dark:text-surface-300">{{ t("Current status") }}</span>
        </div>
        <ul class="m-0 list-none divide-y divide-surface-200 p-0 dark:divide-surface-700">
          <li v-for="shift in shifts.open" :key="shift.id" class="flex items-center justify-between gap-3 py-3">
            <div class="min-w-0">
              <p class="m-0 font-medium">{{ shift.register_name }} · {{ shift.store_name }}</p>
              <p class="m-0 mt-1 text-sm text-surface-600 dark:text-surface-300">
                {{ shift.cashier }} · {{ formatDatetime(shift.opened_at) }}
              </p>
              <p class="m-0 mt-1 text-sm">{{ t("Expected cash: {amount}", { amount: formatCurrency(String(shift.expected_cash)) }) }}</p>
            </div>
            <Button
              icon="fa fa-arrow-right"
              class="dark:!text-primary-200"
              text
              :aria-label="t('View shift for {register}', { register: shift.register_name })"
              @click="emit('shift', shift.id)"
            />
          </li>
        </ul>
        <p v-if="!shifts.open_count" class="mb-0 mt-3 text-sm text-surface-600 dark:text-surface-300">
          {{ t("No shifts are currently open.") }}
        </p>
      </div>
      <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-surface-200 pt-4 dark:border-surface-700">
        <dl class="m-0 flex flex-wrap gap-5 text-sm">
          <div>
            <dt>{{ t("Cash shortages") }}</dt>
            <dd class="m-0 mt-1 font-semibold tabular-nums">{{ formatCurrency(String(shifts.shortages)) }}</dd>
          </div>
          <div>
            <dt>{{ t("Cash overages") }}</dt>
            <dd class="m-0 mt-1 font-semibold tabular-nums">{{ formatCurrency(String(shifts.overages)) }}</dd>
          </div>
        </dl>
        <Button :label="t('Review closed shifts')" text size="small" class="dark:!text-primary-200" @click="emit('discrepancies')" />
      </div>
      <p class="mb-0 mt-2 text-xs text-surface-600 dark:text-surface-300">
        {{ t("{count} shifts closed in the selected period", { count: shifts.closed_count }) }}
      </p>
    </template>
  </section>
</template>
