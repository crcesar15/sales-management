<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Button,
  InputText,
  IconField,
  InputIcon,
  SelectButton,
  Select,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";

import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { PurchaseOrderResponse, PurchaseOrderFilters } from "@/Types/purchase-order-types";
import POStatusBadge from "./Components/POStatusBadge.vue";
import { useI18n } from "vue-i18n";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  purchaseOrders: {
    data: PurchaseOrderResponse[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
  filters: PurchaseOrderFilters;
  vendors: Array<{ id: number; fullname: string }>;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status || "all");
const vendorId = ref<number | null>(props.filters.vendor_id ?? null);
const sortField = ref(props.filters.order_by ?? "created_at");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);

const statusOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Draft"), value: "draft" },
  { label: t("Awaiting Approval"), value: "awaiting_approval" },
  { label: t("Approved"), value: "approved" },
  { label: t("Sent"), value: "sent" },
  { label: t("Paid"), value: "paid" },
  { label: t("Cancelled"), value: "cancelled" },
]);

const vendorOptions = computed(() => [{ id: 0, fullname: t("All Vendors") }, ...props.vendors]);

const orders = computed(() =>
  props.purchaseOrders.data.map((item) => ({
    ...item,
    created_at: useDatetimeFormatter(item.created_at),
  })),
);

function applyFilters(data?: Record<string, unknown>) {
  router.visit(route("purchase-orders"), {
    data: {
      filter: filter.value,
      status: status.value,
      vendor_id: vendorId.value ?? "",
      from: props.filters.from ?? "",
      to: props.filters.to ?? "",
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      ...data,
    },
    preserveState: true,
    replace: true,
  });
}

let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, () => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => applyFilters(), 300);
});

watch(status, () => applyFilters({ page: 1 }));
watch(vendorId, () => applyFilters({ page: 1 }));

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "created_at";
  sortOrder.value = event.sortOrder ?? 1;
  applyFilters();
};

const viewOrder = (po: PurchaseOrderResponse) => {
  router.visit(route("purchase-orders.show", po.id));
};

const editOrder = (po: PurchaseOrderResponse) => {
  router.visit(route("purchase-orders.edit", po.id));
};
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Purchase Orders") }}
      </h2>
      <Button
        v-can="'purchase_order.create'"
        :label="t('Create Purchase Order')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="$inertia.visit(route('purchase-orders.create'))"
      />
    </div>
    <Card>
      <template #content>
        <DataTable
          :value="orders"
          resizable-columns
          lazy
          :total-records="props.purchaseOrders.meta.total"
          :rows="props.purchaseOrders.meta.per_page"
          :first="(props.purchaseOrders.meta.current_page - 1) * props.purchaseOrders.meta.per_page"
          paginator
          sort-field="created_at"
          :sort-order="-1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No purchase orders found") }}</span>
            </div>
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div class="md:col-span-6 col-span-12 flex md:justify-start justify-center">
                <SelectButton v-model="status" :allow-empty="false" :options="statusOptions" option-label="label" option-value="value" />
              </div>
              <div
                class="flex xl:col-span-3 xl:col-start-7 lg:col-span-4 lg:col-start-7 md:col-span-6 md:col-start-7 col-span-12 md:justify-end justify-center items-center gap-2"
              >
                <Select
                  v-model="vendorId"
                  :options="vendorOptions"
                  option-label="fullname"
                  option-value="id"
                  :placeholder="t('All Vendors')"
                  class="w-full"
                />
              </div>
              <div
                class="flex xl:col-span-3 xl:col-start-10 lg:col-span-4 lg:col-start-10 md:col-span-6 md:col-start-7 col-span-12 md:justify-end justify-center"
              >
                <IconField icon-position="left" class="w-full">
                  <InputIcon class="fa fa-search" />
                  <InputText v-model="filter" :placeholder="t('Search')" fluid />
                </IconField>
              </div>
            </div>
          </template>
          <Column field="status" :header="t('Status')" sortable style="width: 140px">
            <template #body="{ data }">
              <POStatusBadge :status="data.status" />
            </template>
          </Column>
          <Column field="vendor.fullname" :header="t('Vendor')" sortable>
            <template #body="{ data }">
              {{ data.vendor?.fullname ?? "—" }}
            </template>
          </Column>
          <Column field="user.full_name" :header="t('Created By')" sortable>
            <template #body="{ data }">
              {{ data.user?.full_name ?? "—" }}
            </template>
          </Column>
          <Column field="total" :header="t('Total')" sortable>
            <template #body="{ data }">
              {{ formatCurrency(String(data.total ?? 0)) }}
            </template>
          </Column>
          <Column field="order_date" :header="t('Order Date')" sortable>
            <template #body="{ data }">
              {{ data.order_date ?? "—" }}
            </template>
          </Column>
          <Column field="created_at" :header="t('Created At')" sortable>
            <template #body="{ data }">
              {{ data.created_at }}
            </template>
          </Column>
          <Column :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center gap-2">
                <Button v-tooltip.top="t('View')" icon="fa fa-eye" text size="large" rounded @click="viewOrder(data)" />
                <Button
                  v-if="data.status === 'draft'"
                  v-can="'purchase_order.edit'"
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
