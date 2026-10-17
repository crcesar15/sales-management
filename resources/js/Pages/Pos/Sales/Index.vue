<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { Badge, Button, Card, Column, DataTable, DatePicker, IconField, InputIcon, InputText, Popover, Select } from "primevue";
import { computed, nextTick, onUnmounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import PosLayout from "@layouts/pos.vue";
import OrderStatusBadge from "@pages/SalesOrders/Components/OrderStatusBadge.vue";
import PaymentStatusBadge from "@pages/SalesOrders/Components/PaymentStatusBadge.vue";
import type { PosSalesOrderResponse, SalesOrderFilters } from "@/Types/sales-order-types";

defineOptions({ layout: PosLayout });

const props = defineProps<{
  orders: {
    data: PosSalesOrderResponse[];
  };
  filters: SalesOrderFilters;
  currentShiftId: number | null;
}>();

const page = usePage();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { formatDatetime, parseDateOnly, toDateOnly, datePickerFormat } = useDatetimeFormatter();

const search = ref(props.filters.search ?? "");
const status = ref(props.filters.status || "all");
const dateFrom = ref<Date | null>(parseDateOnly(props.filters.from ?? null));
const dateTo = ref<Date | null>(parseDateOnly(props.filters.to ?? null));
const filterPopover = ref();

const statusOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Draft"), value: "draft" },
  { label: t("Validated"), value: "validated" },
  { label: t("Fulfilled"), value: "fulfilled" },
  { label: t("Completed"), value: "completed" },
  { label: t("Cancelled"), value: "cancelled" },
]);

const hasActiveFilters = computed(() => status.value !== "all" || dateFrom.value !== null || dateTo.value !== null || search.value !== "");
const activeFilterCount = computed(
  () => [status.value !== "all", dateFrom.value !== null, dateTo.value !== null, search.value !== ""].filter(Boolean).length,
);
const currentUserId = computed(() => page.props.auth.user?.id ?? null);

function applyFilters(): void {
  router.visit(route("pos.sales.index"), {
    data: {
      search: search.value || "",
      status: status.value === "all" ? "" : status.value,
      from: toDateOnly(dateFrom.value) ?? "",
      to: toDateOnly(dateTo.value) ?? "",
    },
    preserveState: true,
    replace: true,
  });
}

function resetFilters(): void {
  resettingFilters = true;
  clearTimeout(searchTimer);
  search.value = "";
  status.value = "all";
  dateFrom.value = null;
  dateTo.value = null;
  void nextTick(() => {
    resettingFilters = false;
    applyFilters();
  });
}

function customerName(order: PosSalesOrderResponse): string {
  return order.customer?.display_name || t("Walk-in");
}

function canResume(order: PosSalesOrderResponse): boolean {
  return (
    order.status === "draft" &&
    currentUserId.value !== null &&
    order.user_id === currentUserId.value &&
    props.currentShiftId !== null &&
    order.cash_register_shift_id === props.currentShiftId
  );
}

function openOrder(order: PosSalesOrderResponse): void {
  router.visit(route(canResume(order) ? "pos.sales.edit" : "pos.sales.show", order.id));
}

let searchTimer: ReturnType<typeof setTimeout>;
let resettingFilters = false;
watch(search, () => {
  if (resettingFilters) return;
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => applyFilters(), 300);
});
watch([status, dateFrom, dateTo], () => {
  if (!resettingFilters) applyFilters();
});
onUnmounted(() => clearTimeout(searchTimer));
</script>

<template>
  <div class="p-4 lg:p-6">
    <div class="mb-4">
      <h1 class="m-0 text-2xl font-bold">{{ t("Sales") }}</h1>
    </div>

    <Card>
      <template #content>
        <DataTable :value="orders.data" data-key="id" resizable-columns>
          <template #empty>
            <div class="flex flex-col items-center py-10 text-surface-400">
              <i class="fa fa-receipt mb-3 text-4xl" aria-hidden="true"></i>
              <span>{{ t("No sales orders found") }}</span>
            </div>
          </template>

          <template #header>
            <div class="flex items-center gap-2">
              <Button
                type="button"
                icon="fa fa-filter"
                :label="t('Filters')"
                :severity="hasActiveFilters ? 'primary' : 'secondary'"
                outlined
                :pt="{ label: { class: 'hidden lg:inline' } }"
                @click="filterPopover.toggle($event)"
              />
              <Badge v-if="activeFilterCount > 0" :value="activeFilterCount" severity="primary" />
              <IconField icon-position="left" class="ml-auto w-full max-w-80">
                <InputIcon class="fa fa-search" />
                <InputText v-model="search" :placeholder="t('Search') + '...'" class="w-full" />
              </IconField>
            </div>

            <Popover ref="filterPopover">
              <div class="flex min-w-72 flex-col gap-4 p-4">
                <div>
                  <label class="mb-1 block text-sm font-medium">{{ t("Status") }}</label>
                  <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium">{{ t("Date From") }}</label>
                  <DatePicker v-model="dateFrom" show-icon :placeholder="t('From')" :date-format="datePickerFormat" class="w-full" />
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium">{{ t("Date To") }}</label>
                  <DatePicker v-model="dateTo" show-icon :placeholder="t('To')" :date-format="datePickerFormat" class="w-full" />
                </div>
                <div class="flex justify-end border-t border-surface-200 pt-2 dark:border-surface-700">
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

          <Column field="id" :header="t('Order #')" style="min-width: 90px">
            <template #body="{ data }">#{{ data.id }}</template>
          </Column>
          <Column :header="t('Customer')" style="min-width: 170px">
            <template #body="{ data }">{{ customerName(data) }}</template>
          </Column>
          <Column :header="t('Cashier')" style="min-width: 160px">
            <template #body="{ data }">{{ data.user?.full_name ?? "---" }}</template>
          </Column>
          <Column field="created_at" :header="t('Date')" style="min-width: 150px">
            <template #body="{ data }">{{ formatDatetime(data.created_at) }}</template>
          </Column>
          <Column field="status" :header="t('Status')" style="min-width: 130px">
            <template #body="{ data }"><OrderStatusBadge :status="data.status" /></template>
          </Column>
          <Column field="payment_status" :header="t('Payment')" style="min-width: 140px">
            <template #body="{ data }"><PaymentStatusBadge :status="data.payment_status" /></template>
          </Column>
          <Column field="items_count" :header="t('Items')" style="width: 90px" />
          <Column field="total" :header="t('Total')" style="min-width: 130px">
            <template #body="{ data }">
              <span class="font-semibold tabular-nums">{{ formatCurrency(String(data.total ?? 0)) }}</span>
            </template>
          </Column>
          <Column :header="t('Actions')" style="width: 140px">
            <template #body="{ data }">
              <Button
                v-tooltip.top="canResume(data) ? t('Continue') : t('View')"
                :aria-label="canResume(data) ? t('Continue') : t('View')"
                :icon="canResume(data) ? 'fa fa-arrow-right' : 'fa fa-eye'"
                text
                rounded
                @click="openOrder(data)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
