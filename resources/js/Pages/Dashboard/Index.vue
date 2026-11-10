<script setup lang="ts">
import { computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Button, Message } from "primevue";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import AppLayout from "@/Layouts/admin.vue";
import { useDatetimeFormatter } from "@/Composables/useDatetimeFormatter";
import { useDashboard } from "@/Composables/useDashboard";
import type { DashboardData, DashboardStore } from "@/Types/dashboard-types";
import DashboardFilters from "./Components/DashboardFilters.vue";
import DashboardSummary from "./Components/DashboardSummary.vue";
import SalesTrendChart from "./Components/SalesTrendChart.vue";
import InventoryAttentionPanel from "./Components/InventoryAttentionPanel.vue";
import ProductPerformanceTable from "./Components/ProductPerformanceTable.vue";
import CashShiftPanel from "./Components/CashShiftPanel.vue";
import StorePerformanceTable from "./Components/StorePerformanceTable.vue";

defineOptions({ layout: AppLayout });
const props = defineProps<{ dashboard: DashboardData; stores: DashboardStore[] }>();
const { t } = useI18n();
const { formatDatetime } = useDatetimeFormatter();
const { loading, stale, accessLost, errors, refresh, apply, hasWidgets } = useDashboard(() => props.dashboard);
const periodLabel = computed(() => `${formatDatetime(props.dashboard.period.start)} — ${formatDatetime(props.dashboard.period.end)}`);
const scopeName = computed(
  () => props.stores.find((store) => store.id === props.dashboard.filters.store_id)?.name ?? t("All permitted stores"),
);

function salesDetails() {
  router.visit(
    route("sales-orders", {
      store_id: props.dashboard.filters.store_id,
      date_basis: "fulfilled",
      from: props.dashboard.filters.from,
      to: props.dashboard.filters.to,
      report_to: props.dashboard.period.end,
    }),
  );
}
function alerts(storeId: number | null, tab: "low-stock" | "expiry") {
  router.visit(route("inventory.alerts", { store_id: storeId ?? props.dashboard.filters.store_id, per_store: 1, tab }));
}
function product(id: number) {
  router.visit(route("inventory.variants.show", { variant: id, store_id: props.dashboard.filters.store_id }));
}
function closedShifts() {
  router.visit(
    route("shifts", {
      store_id: props.dashboard.filters.store_id,
      date_basis: "closed",
      date_from: props.dashboard.filters.from,
      date_to: props.dashboard.filters.to,
      report_to: props.dashboard.period.end,
    }),
  );
}
</script>

<template>
  <Head :title="t('Dashboard')" />
  <div class="flex min-w-0 flex-col gap-5 text-surface-900 dark:text-surface-0" :aria-busy="loading">
    <header class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="m-0 text-3xl font-bold">{{ t("Dashboard") }}</h1>
        <p class="mb-0 mt-1 text-surface-600 dark:text-surface-300">{{ t("Trading performance and store operations") }}</p>
      </div>
      <p class="m-0 text-sm text-surface-600 dark:text-surface-300" aria-live="polite">
        {{ t("Updated {time}", { time: formatDatetime(dashboard.generated_at) }) }}
        <br />
        {{ t("Refreshes every five minutes") }}
      </p>
    </header>
    <DashboardFilters :filters="dashboard.filters" :stores="stores" :loading="loading" :errors="errors" @apply="apply" @refresh="refresh" />
    <Message v-if="stale" severity="warn" :closable="false" role="alert">
      <div class="flex flex-wrap items-center gap-3">
        <span>
          {{
            accessLost
              ? t("Dashboard access changed. Refresh to reload your permissions.")
              : t("Refresh failed. Showing the last successful results for the applied filters.")
          }}
        </span>
        <Button :label="t('Retry')" size="small" severity="secondary" :disabled="loading" @click="refresh" />
      </div>
    </Message>
    <template v-if="!accessLost">
      <div
        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-b border-surface-200 pb-3 text-sm dark:border-surface-700"
      >
        <p class="m-0 font-semibold">
          {{ scopeName }}
          <span
            v-if="!dashboard.capabilities.all_sales && dashboard.capabilities.sales"
            class="font-normal text-surface-600 dark:text-surface-300"
          >
            · {{ t("Your sales only") }}
          </span>
        </p>
        <p class="m-0 text-surface-600 dark:text-surface-300">{{ periodLabel }} · {{ dashboard.period.timezone }}</p>
        <p
          v-if="dashboard.period.comparison_start && dashboard.period.comparison_end"
          class="m-0 w-full text-surface-600 dark:text-surface-300"
        >
          {{
            t("Compared with {from} to {to}", {
              from: formatDatetime(dashboard.period.comparison_start),
              to: formatDatetime(dashboard.period.comparison_end),
            })
          }}
        </p>
      </div>
      <div
        v-if="!dashboard.store_count || !hasWidgets"
        class="rounded-lg border border-surface-200 bg-surface-0 px-5 py-12 text-center dark:border-surface-700 dark:bg-surface-800"
      >
        <h2 class="m-0 text-xl font-bold">
          {{ !dashboard.store_count ? t("No permitted stores") : t("No dashboard sections available") }}
        </h2>
        <p class="mb-0 mt-2 text-surface-600 dark:text-surface-300">
          {{ t("Ask an administrator to review your store assignments and permissions.") }}
        </p>
      </div>
      <template v-else>
        <DashboardSummary v-if="dashboard.sales" :sales="dashboard.sales" :comparison="dashboard.comparison" @details="salesDetails" />
        <div
          v-if="dashboard.trend || dashboard.inventory"
          class="grid grid-cols-1 gap-5"
          :class="{ 'xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]': dashboard.trend && dashboard.inventory }"
        >
          <SalesTrendChart v-if="dashboard.trend" :trend="dashboard.trend" :timezone="dashboard.period.timezone" />
          <InventoryAttentionPanel v-if="dashboard.inventory" :inventory="dashboard.inventory" @alerts="alerts" />
        </div>
        <div
          v-if="dashboard.products || dashboard.collections || dashboard.shifts"
          class="grid grid-cols-1 items-start gap-5"
          :class="{ 'xl:grid-cols-2': dashboard.products && (dashboard.collections || dashboard.shifts) }"
        >
          <ProductPerformanceTable
            v-if="dashboard.products"
            :products="dashboard.products"
            :show-profit="dashboard.capabilities.profit"
            :can-view="dashboard.capabilities.inventory_details"
            @product="product"
          />
          <CashShiftPanel
            v-if="dashboard.collections || dashboard.shifts"
            :collections="dashboard.collections"
            :shifts="dashboard.shifts"
            @shift="router.visit(route('shifts.show', $event))"
            @discrepancies="closedShifts"
          />
        </div>
        <StorePerformanceTable
          v-if="dashboard.stores && dashboard.store_count > 1"
          :stores="dashboard.stores"
          :show-profit="dashboard.capabilities.profit"
          @store="apply({ ...dashboard.filters, store_id: $event })"
          @page="apply(dashboard.filters, $event)"
        />
      </template>
    </template>
  </div>
</template>
