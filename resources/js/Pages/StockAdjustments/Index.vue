<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Button,
  Select,
  Calendar,
  Badge,
  Popover,
  type DataTablePageEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type {
  StockAdjustmentListResponse,
  StockAdjustmentFilters,
  StockAdjustmentResponse,
} from "@/Types/stock-adjustment-types";
import AdjustmentReasonTag from "./Show/Components/AdjustmentReasonTag.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  adjustments: StockAdjustmentListResponse;
  filters: StockAdjustmentFilters;
  stores: Array<{ id: number; name: string; code: string }>;
}>();

const { t } = useI18n();

const ALL = "__all__";

const storeId = ref<string | number>(props.filters.store_id ?? ALL);
const reason = ref(props.filters.reason || ALL);
const dateFrom = ref<Date | null>(props.filters.date_from ? new Date(props.filters.date_from) : null);
const dateTo = ref<Date | null>(props.filters.date_to ? new Date(props.filters.date_to) : null);
const filterPopover = ref();

const reasonOptions = computed(() => [
  { label: t("All"), value: ALL },
  { label: t("Physical Audit"), value: "physical_audit" },
  { label: t("Robbery"), value: "robbery" },
  { label: t("Expiry"), value: "expiry" },
  { label: t("Damage"), value: "damage" },
  { label: t("Correction"), value: "correction" },
  { label: t("Other"), value: "other" },
]);

const storeOptions = computed(() => [
  { label: t("All Stores"), value: ALL },
  ...props.stores.map((s) => ({ label: s.name, value: s.id })),
]);

const hasActiveFilters = computed(
  () => storeId.value !== ALL || reason.value !== ALL || dateFrom.value !== null || dateTo.value !== null,
);

const activeFilterCount = computed(() => {
  let count = 0;
  if (storeId.value !== ALL) count++;
  if (reason.value !== ALL) count++;
  if (dateFrom.value !== null) count++;
  if (dateTo.value !== null) count++;
  return count;
});

function resetFilters() {
  storeId.value = ALL;
  reason.value = ALL;
  dateFrom.value = null;
  dateTo.value = null;
  applyFilters();
}

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("stock-adjustments"), {
    data: {
      store_id: storeId.value === ALL ? "" : storeId.value,
      reason: reason.value === ALL ? "" : reason.value,
      date_from: dateFrom.value ? formatDateParam(dateFrom.value) : "",
      date_to: dateTo.value ? formatDateParam(dateTo.value) : "",
      ...overrides,
    },
    preserveState: true,
    replace: true,
  });
}

function formatDateParam(val: Date): string {
  return val.toISOString().split("T")[0];
}

watch(storeId, () => applyFilters());
watch(reason, () => applyFilters());

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

function formatDate(date: string | null): string {
  if (!date) return "---";
  return new Date(date).toLocaleDateString();
}

function quantityClass(val: number): string {
  return val < 0 ? "text-red-500 font-semibold" : "text-green-600 font-semibold";
}

function formatQuantity(val: number): string {
  return val > 0 ? `+${val}` : `${val}`;
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-center m-0">
        {{ t("Stock Adjustments") }}
      </h2>
      <Button
        v-can="'stock.adjust'"
        :label="t('New Adjustment')"
        icon="fa fa-plus"
        @click="router.visit(route('stock-adjustments.create'))"
      />
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="adjustments.data"
          resizable-columns
          lazy
          :total-records="adjustments.meta.total"
          :rows="adjustments.meta.per_page"
          :first="(adjustments.meta.current_page - 1) * adjustments.meta.per_page"
          paginator
          :page-link-size="3"
          @page="onPage($event)"
        >
          <template #empty>
            {{ t("No stock adjustments found") }}
          </template>

          <template #header>
            <div class="grid grid-cols-12 gap-2">
              <div class="lg:col-span-4 lg:col-start-1 md:col-span-6 col-span-12 flex gap-2 items-center">
                <Button
                  type="button"
                  icon="fa fa-filter"
                  :label="t('Filters')"
                  :severity="hasActiveFilters ? 'primary' : 'secondary'"
                  outlined
                  @click="filterPopover.toggle($event)"
                />
                <Badge v-if="activeFilterCount > 0" :value="activeFilterCount" severity="primary" />
              </div>
            </div>

            <Popover ref="filterPopover">
              <div class="flex flex-col gap-4 p-4" style="width: 320px">
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Store") }}</label>
                  <Select v-model="storeId" :options="storeOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Reason") }}</label>
                  <Select v-model="reason" :options="reasonOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Date From") }}</label>
                  <Calendar v-model="dateFrom" :show-icon="true" date-format="yy-mm-dd" class="w-full" @date-select="applyFilters()" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Date To") }}</label>
                  <Calendar v-model="dateTo" :show-icon="true" date-format="yy-mm-dd" class="w-full" @date-select="applyFilters()" />
                </div>
                <div class="flex justify-end pt-2 border-t border-surface-200 dark:border-surface-700">
                  <Button
                    type="button"
                    :label="t('Clear')"
                    icon="fa fa-times"
                    severity="secondary"
                    text
                    size="small"
                    :disabled="!hasActiveFilters"
                    @click="resetFilters"
                  />
                </div>
              </div>
            </Popover>
          </template>

          <Column :header="t('Reason')" style="width: 140px">
            <template #body="{ data }: { data: StockAdjustmentResponse }">
              <AdjustmentReasonTag :reason="data.reason" />
            </template>
          </Column>

          <Column :header="t('Product')" style="width: 200px">
            <template #body="{ data }: { data: StockAdjustmentResponse }">
              <span class="font-medium">{{ data.product_variant?.product?.name ?? "---" }}</span>
              <div class="text-sm text-surface-500">{{ data.product_variant?.name }}</div>
            </template>
          </Column>

          <Column :header="t('Store')" style="width: 150px">
            <template #body="{ data }: { data: StockAdjustmentResponse }">
              {{ data.store?.name ?? "---" }}
            </template>
          </Column>

          <Column :header="t('Quantity Change')" style="width: 130px">
            <template #body="{ data }: { data: StockAdjustmentResponse }">
              <span :class="quantityClass(data.quantity_change)">{{ formatQuantity(data.quantity_change) }}</span>
            </template>
          </Column>

          <Column :header="t('User')" style="width: 150px">
            <template #body="{ data }: { data: StockAdjustmentResponse }">
              {{ data.user?.full_name ?? "---" }}
            </template>
          </Column>

          <Column :header="t('Created At')" style="width: 120px">
            <template #body="{ data }: { data: StockAdjustmentResponse }">
              {{ formatDate(data.created_at) }}
            </template>
          </Column>

          <Column :header="t('Actions')" style="width: 80px">
            <template #body="{ data }: { data: StockAdjustmentResponse }">
              <Button
                v-tooltip.top="t('View')"
                icon="fa-solid fa-eye"
                text
                rounded
                size="small"
                :aria-label="t('View')"
                @click="router.visit(route('stock-adjustments.show', { stockAdjustment: data.id }))"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
