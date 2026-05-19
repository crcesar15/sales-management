<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Button,
  Select,
  Calendar,
  Popover,
  Badge,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { useAuth } from "@/Composables/useAuth";

import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { ReceptionOrderResponse, ReceptionOrderFilters } from "@/Types/reception-order-types";
import ReceptionStatusBadge from "./Components/ReceptionStatusBadge.vue";
import { useI18n } from "vue-i18n";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  receptionOrders: {
    data: ReceptionOrderResponse[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
  filters: ReceptionOrderFilters;
  vendors: Array<{ id: number; fullname: string }>;
  stores: Array<{ id: number; name: string }>;
}>();

const { getSetting } = useAuth();
const { t } = useI18n();

const status = ref(props.filters.status || "all");
const vendorId = ref<number | null>(props.filters.vendor_id ?? null);
const storeId = ref<number | null>(props.filters.store_id ?? null);
const dateFrom = ref<Date | null>(props.filters.from ? new Date(props.filters.from) : null);
const dateTo = ref<Date | null>(props.filters.to ? new Date(props.filters.to) : null);
const sortField = ref(props.filters.order_by ?? "created_at");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);
const filterPopover = ref();

const statusOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Pending"), value: "pending" },
  { label: t("Completed"), value: "completed" },
  { label: t("Cancelled"), value: "cancelled" },
]);

const vendorOptions = computed(() => [
  { label: t("All Vendors"), value: null },
  ...props.vendors.map((v) => ({ label: v.fullname, value: v.id })),
]);

const storeOptions = computed(() => [
  { label: t("All Stores"), value: null },
  ...props.stores.map((s) => ({ label: s.name, value: s.id })),
]);

const hasActiveFilters = computed(
  () => status.value !== "all" || vendorId.value !== null || storeId.value !== null || dateFrom.value !== null || dateTo.value !== null,
);

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== "all") count++;
  if (vendorId.value !== null) count++;
  if (storeId.value !== null) count++;
  if (dateFrom.value !== null) count++;
  if (dateTo.value !== null) count++;
  return count;
});

const orders = computed(() =>
  props.receptionOrders.data.map((item) => ({
    ...item,
    reception_date: useDatetimeFormatter(item.reception_date, getSetting("general", "date_format") || "DD-MM-YYYY"),
    created_at: useDatetimeFormatter(item.created_at),
  })),
);

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("reception-orders"), {
    data: {
      filter: "",
      status: status.value === "all" ? null : status.value,
      vendor_id: vendorId.value ?? "",
      store_id: storeId.value ?? "",
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
  status.value = "all";
  vendorId.value = null;
  storeId.value = null;
  dateFrom.value = null;
  dateTo.value = null;
  applyFilters();
}

watch(status, () => applyFilters());
watch(vendorId, () => applyFilters());
watch(storeId, () => applyFilters());
watch(dateFrom, () => applyFilters());
watch(dateTo, () => applyFilters());

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "created_at";
  sortOrder.value = event.sortOrder ?? 1;
  applyFilters();
};

function viewOrder(order: ReceptionOrderResponse) {
  router.visit(route("reception-orders.show", order.id));
}

function editOrder(order: ReceptionOrderResponse) {
  router.visit(route("reception-orders.edit", order.id));
}
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Reception Orders") }}
      </h2>
      <Button
        v-can="'reception_order.create'"
        :label="t('Create Reception Order')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="$inertia.visit(route('reception-orders.create'))"
      />
    </div>
    <Card>
      <template #content>
        <DataTable
          :value="orders"
          resizable-columns
          lazy
          :total-records="props.receptionOrders.meta.total"
          :rows="props.receptionOrders.meta.per_page"
          :first="(props.receptionOrders.meta.current_page - 1) * props.receptionOrders.meta.per_page"
          paginator
          sort-field="created_at"
          :sort-order="-1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No reception orders found") }}</span>
            </div>
          </template>
          <template #header>
            <div class="grid grid-cols-12 gap-2">
              <div class="lg:col-span-4 lg:col-start-1 col-span-12 flex gap-2 items-center">
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
                  <label class="text-sm font-medium mb-1 block">{{ t("Vendor") }}</label>
                  <Select
                    v-model="vendorId"
                    :options="vendorOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="t('All Vendors')"
                    class="w-full"
                  />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Store") }}</label>
                  <Select
                    v-model="storeId"
                    :options="storeOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="t('All Stores')"
                    class="w-full"
                  />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Date From") }}</label>
                  <Calendar v-model="dateFrom" :show-icon="true" :placeholder="t('From')" date-format="yy-mm-dd" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Date To") }}</label>
                  <Calendar v-model="dateTo" :show-icon="true" :placeholder="t('To')" date-format="yy-mm-dd" class="w-full" />
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
          <Column field="vendor.fullname" :header="t('Vendor')" sortable>
            <template #body="{ data }">
              {{ data.vendor?.fullname ?? "—" }}
            </template>
          </Column>
          <Column field="purchaseOrder.id" :header="t('Purchase Order')" sortable>
            <template #body="{ data }">
              <a
                v-if="data.purchaseOrder?.id"
                class="text-primary-500 hover:underline cursor-pointer"
                @click="router.visit(route('purchase-orders.show', data.purchaseOrder.id))"
              >
                #{{ data.purchaseOrder.id }}
              </a>
              <span v-else>—</span>
            </template>
          </Column>
          <Column field="store.name" :header="t('Store')" sortable>
            <template #body="{ data }">
              {{ data.store?.name ?? "—" }}
            </template>
          </Column>
          <Column field="reception_date" :header="t('Reception Date')" sortable>
            <template #body="{ data }">
              {{ data.reception_date ?? "—" }}
            </template>
          </Column>
          <Column field="status" :header="t('Status')" sortable style="width: 140px">
            <template #body="{ data }">
              <ReceptionStatusBadge :status="data.status" />
            </template>
          </Column>
          <Column field="user.full_name" :header="t('Created By')" sortable>
            <template #body="{ data }">
              {{ data.user?.full_name ?? "—" }}
            </template>
          </Column>
          <Column :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center gap-2">
                <Button v-tooltip.top="t('View')" icon="fa fa-eye" text size="large" rounded @click="viewOrder(data)" />
                <Button
                  v-if="data.status === 'pending'"
                  v-can="'reception_order.edit'"
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