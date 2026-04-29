<script setup lang="ts">
import { Card, TabView, TabPanel, Select, Badge } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { LowStockAlertItem, ExpiryAlertItem, AlertsSummary, AlertsFilters } from "@/Types/stock-alert-types";
import LowStockAlertList from "@components/Inventory/LowStockAlertList.vue";
import ExpiryAlertList from "@components/Inventory/ExpiryAlertList.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  lowStockAlerts: LowStockAlertItem[];
  expiryAlerts: ExpiryAlertItem[];
  summary: AlertsSummary;
  stores: Array<{ id: number; name: string; code: string }>;
  filters: AlertsFilters;
}>();

const { t } = useI18n();

const ALL = "__all__";

const storeId = ref<string | number>(props.filters.store_id ?? ALL);

const storeOptions = computed(() => [
  { label: t("All Stores"), value: ALL },
  ...props.stores.map((s) => ({ label: s.name, value: s.id })),
]);

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("inventory.alerts"), {
    data: {
      store_id: storeId.value === ALL ? "" : storeId.value,
      ...overrides,
    },
    preserveState: true,
    replace: true,
  });
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-3">
      <h2 class="text-2xl font-bold flex items-center gap-2 m-0">
        {{ t("Stock Alerts") }}
        <Badge v-if="summary.total > 0" :value="summary.total" severity="danger" />
      </h2>
      <Select
        v-model="storeId"
        :options="storeOptions"
        option-label="label"
        option-value="value"
        :placeholder="t('All Stores')"
        class="w-48"
        @change="applyFilters()"
      />
    </div>

    <Card>
      <template #content>
        <TabView>
          <TabPanel value="low-stock" :header="`${t('Low Stock')} (${summary.low_stock_count})`">
            <LowStockAlertList :alerts="lowStockAlerts" />
          </TabPanel>
          <TabPanel value="expiry" :header="`${t('Expiring Soon')} (${summary.expiry_count})`">
            <ExpiryAlertList :alerts="expiryAlerts" />
          </TabPanel>
        </TabView>
      </template>
    </Card>
  </div>
</template>
