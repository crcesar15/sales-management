<script setup lang="ts">
import { Select, Tab, TabList, TabPanel, TabPanels, Tabs } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { computed, shallowRef } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type {
  AlertsFilters,
  AlertsSummary,
  ExpiryAlertCollection,
  LowStockAlertCollection,
  PriceReviewCollection,
} from "@/Types/stock-alert-types";
import PriceReviewList from "./Components/PriceReviewList.vue";
import LowStockAlertList from "./Components/LowStockAlertList.vue";
import ExpiryAlertList from "./Components/ExpiryAlertList.vue";
import StockAlertSummary from "./Components/StockAlertSummary.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  lowStockAlerts: LowStockAlertCollection;
  priceReviewAlerts: PriceReviewCollection | null;
  expiryAlerts: ExpiryAlertCollection;
  summary: AlertsSummary;
  stores: Array<{ id: number; name: string; code: string }>;
  filters: AlertsFilters;
}>();

const { t } = useI18n();

const ALL = "__all__";

const storeId = shallowRef<string | number>(props.filters.store_id ?? ALL);
const activeTab = shallowRef<"low-stock" | "expiry" | "price-review">(props.filters.tab);

const storeOptions = computed(() => [{ label: t("All Stores"), value: ALL }, ...props.stores.map((s) => ({ label: s.name, value: s.id }))]);

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("inventory.alerts"), {
    data: {
      store_id: storeId.value === ALL ? "" : storeId.value,
      per_store: props.filters.per_store ? 1 : 0,
      tab: activeTab.value,
      low_stock_page: props.lowStockAlerts.meta.current_page,
      low_stock_per_page: props.lowStockAlerts.meta.per_page,
      expiry_page: props.expiryAlerts.meta.current_page,
      expiry_per_page: props.expiryAlerts.meta.per_page,
      price_review_page: props.priceReviewAlerts?.meta.current_page ?? 1,
      price_review_per_page: props.priceReviewAlerts?.meta.per_page ?? 20,
      price_review_reason: props.filters.price_review_reason ?? "",
      price_review_severity: props.filters.price_review_severity ?? "",
      price_review_state: props.filters.price_review_state ?? "active",
      ...overrides,
    },
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function onStoreChange() {
  applyFilters({ low_stock_page: 1, expiry_page: 1, price_review_page: 1 });
}

function onTabChange(value: string | number) {
  if (value !== "low-stock" && value !== "expiry" && value !== "price-review") return;

  activeTab.value = value;
  applyFilters({ tab: value });
}

function onLowStockPage(page: number, perPage: number) {
  applyFilters({ low_stock_page: page, low_stock_per_page: perPage });
}

function onExpiryPage(page: number, perPage: number) {
  applyFilters({ expiry_page: page, expiry_per_page: perPage });
}
</script>

<template>
  <div>
    <header class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <h2 class="m-0 flex items-center gap-2 text-2xl font-bold">
        {{ t("Stock Alerts") }}
      </h2>
      <div class="flex w-full flex-col gap-1 sm:w-64">
        <label for="stock-alert-store" class="text-sm font-medium text-surface-700 dark:text-surface-200">{{ t("Store") }}</label>
        <Select
          v-model="storeId"
          input-id="stock-alert-store"
          :options="storeOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('All Stores')"
          class="w-full"
          @change="onStoreChange"
        />
      </div>
    </header>

    <StockAlertSummary :summary="summary" class="mb-4" />

    <section
      class="overflow-hidden rounded-lg border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
      :aria-label="t('Stock Alerts')"
    >
      <Tabs :value="activeTab" scrollable @update:value="onTabChange">
        <TabList>
          <Tab v-if="priceReviewAlerts" value="price-review">
            {{ t("Price Review") }}
            <span class="ml-2">{{ summary.price_review_count }}</span>
          </Tab>
          <Tab value="low-stock">
            <span class="flex items-center gap-2">
              {{ t("Low Stock") }}
              <span
                class="rounded-full bg-surface-100 px-2 py-0.5 text-xs font-semibold text-surface-700 dark:bg-surface-800 dark:text-surface-200"
              >
                {{ summary.low_stock_count }}
              </span>
            </span>
          </Tab>
          <Tab value="expiry">
            <span class="flex items-center gap-2">
              {{ t("Expiring Soon") }}
              <span
                class="rounded-full bg-surface-100 px-2 py-0.5 text-xs font-semibold text-surface-700 dark:bg-surface-800 dark:text-surface-200"
              >
                {{ summary.expiry_count }}
              </span>
            </span>
          </Tab>
        </TabList>
        <TabPanels :pt="{ root: { class: '!p-0' } }">
          <TabPanel v-if="priceReviewAlerts" value="price-review">
            <PriceReviewList
              :alerts="priceReviewAlerts"
              :reason="filters.price_review_reason ?? null"
              :severity="filters.price_review_severity ?? null"
              :review-state="filters.price_review_state ?? 'active'"
              @page="(page, perPage) => applyFilters({ price_review_page: page, price_review_per_page: perPage })"
              @filter="(reason) => applyFilters({ price_review_reason: reason ?? '', price_review_page: 1 })"
              @severity="(severity) => applyFilters({ price_review_severity: severity ?? '', price_review_page: 1 })"
              @state="(state) => applyFilters({ price_review_state: state, price_review_page: 1 })"
            />
          </TabPanel>
          <TabPanel value="low-stock">
            <LowStockAlertList :alerts="lowStockAlerts" @page="onLowStockPage" />
          </TabPanel>
          <TabPanel value="expiry">
            <ExpiryAlertList :alerts="expiryAlerts" @page="onExpiryPage" />
          </TabPanel>
        </TabPanels>
      </Tabs>
    </section>
  </div>
</template>
