<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Button,
  Select,
  DatePicker,
  Popover,
  Badge,
  InputText,
  IconField,
  InputIcon,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";

import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { SalesOrderResponse, SalesOrderFilters } from "@/Types/sales-order-types";
import OrderStatusBadge from "./Components/OrderStatusBadge.vue";
import { useI18n } from "vue-i18n";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  orders: {
    data: SalesOrderResponse[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
  filters: SalesOrderFilters;
  canViewAll: boolean;
}>();

const { formatDatetime } = useDatetimeFormatter();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const search = ref(props.filters.search ?? "");
const status = ref(props.filters.status || "all");
const dateFrom = ref<Date | null>(props.filters.from ? new Date(props.filters.from) : null);
const dateTo = ref<Date | null>(props.filters.to ? new Date(props.filters.to) : null);
const sortField = ref("created_at");
const sortOrder = ref(-1);
const filterPopover = ref();

const statusOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Draft"), value: "draft" },
  { label: t("Sent"), value: "sent" },
  { label: t("Paid"), value: "paid" },
  { label: t("Held"), value: "held" },
  { label: t("Cancelled"), value: "cancelled" },
]);

const hasActiveFilters = computed(() => status.value !== "all" || dateFrom.value !== null || dateTo.value !== null || search.value !== "");

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== "all") count++;
  if (dateFrom.value !== null) count++;
  if (dateTo.value !== null) count++;
  if (search.value) count++;
  return count;
});

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("sales-orders"), {
    data: {
      search: search.value || "",
      status: status.value === "all" ? "" : status.value,
      from: dateFrom.value ? dateFrom.value.toISOString().split("T")[0] : "",
      to: dateTo.value ? dateTo.value.toISOString().split("T")[0] : "",
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      ...overrides,
    },
    preserveState: true,
    replace: true,
  });
}

function resetFilters() {
  search.value = "";
  status.value = "all";
  dateFrom.value = null;
  dateTo.value = null;
  applyFilters();
}

let searchTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => applyFilters(), 300);
});

watch(status, () => applyFilters());
watch(dateFrom, () => applyFilters());
watch(dateTo, () => applyFilters());

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "created_at";
  sortOrder.value = event.sortOrder ?? -1;
  applyFilters();
};

function viewOrder(order: SalesOrderResponse) {
  router.visit(route("sales-orders.show", order.id));
}

function editOrder(order: SalesOrderResponse) {
  router.visit(route("sales-orders.edit", order.id));
}

function customerName(order: SalesOrderResponse): string {
  if (order.customer?.display_name) return order.customer.display_name;
  return t("Walk-in");
}
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Sales Orders") }}
      </h2>
      <Button
        v-can="'sales.manage'"
        :label="t('Create Sales Order')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="router.visit(route('sales-orders.create'))"
      />
    </div>
    <Card>
      <template #content>
        <DataTable
          :value="orders.data"
          resizable-columns
          lazy
          :total-records="props.orders.meta.total"
          :rows="props.orders.meta.per_page"
          :first="(props.orders.meta.current_page - 1) * props.orders.meta.per_page"
          paginator
          sort-field="created_at"
          :sort-order="-1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-file-invoice-dollar text-4xl mb-3"></i>
              <span>{{ t("No sales orders found") }}</span>
            </div>
          </template>
          <template #header>
            <div class="grid grid-cols-12 gap-2">
              <div class="lg:col-span-4 col-span-12 flex gap-2 items-center">
                <IconField class="w-full">
                  <InputIcon class="fa fa-search" />
                  <InputText v-model="search" :placeholder="t('Search') + '...'" class="w-full" />
                </IconField>
              </div>
              <div class="lg:col-span-8 col-span-12 flex justify-end gap-2 items-center">
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
              <div class="flex flex-col gap-4 p-4 min-w-72">
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Status") }}</label>
                  <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Date From") }}</label>
                  <DatePicker v-model="dateFrom" :show-icon="true" :placeholder="t('From')" date-format="yy-mm-dd" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Date To") }}</label>
                  <DatePicker v-model="dateTo" :show-icon="true" :placeholder="t('To')" date-format="yy-mm-dd" class="w-full" />
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
          <Column field="id" :header="t('Order #')" sortable style="min-width: 80px">
            <template #body="{ data }">#{{ data.id }}</template>
          </Column>
          <Column field="customer" :header="t('Customer')" sortable style="min-width: 180px">
            <template #body="{ data }">
              {{ customerName(data) }}
            </template>
          </Column>
          <Column field="user" :header="t('Cashier')" sortable style="min-width: 150px">
            <template #body="{ data }">
              {{ data.user?.full_name ?? "---" }}
            </template>
          </Column>
          <Column field="created_at" :header="t('Date')" sortable style="min-width: 140px">
            <template #body="{ data }">
              {{ formatDatetime(data.created_at) }}
            </template>
          </Column>
          <Column field="status" :header="t('Status')" sortable style="width: 140px">
            <template #body="{ data }">
              <OrderStatusBadge :status="data.status" />
            </template>
          </Column>
          <Column field="total" :header="t('Total')" sortable style="min-width: 120px">
            <template #body="{ data }">
              {{ formatCurrency(String(data.total ?? 0)) }}
            </template>
          </Column>
          <Column :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center gap-2">
                <Button v-tooltip.top="t('View')" icon="fa fa-eye" text size="large" rounded @click="viewOrder(data)" />
                <Button
                  v-if="data.status === 'draft'"
                  v-can="'sales.manage'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="large"
                  rounded
                  @click="editOrder(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
