<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Toast,
  Button,
  Select,
  DatePicker,
  Popover,
  Badge,
  Tag,
  useToast,
  type DataTablePageEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import OpenShiftDialog from "@pages/CashRegisterShifts/Components/OpenShiftDialog.vue";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type {
  CashRegisterShiftResponse,
  ShiftListResponse,
  ShiftFilters,
} from "@/Types/cash-register-types";
import { useI18n } from "vue-i18n";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  shifts: ShiftListResponse;
  filters: ShiftFilters;
  registers: Array<{ id: number; name: string; code: string }>;
  cashiers: Array<{ id: number; full_name: string }>;
}>();

const toast = useToast();
const { t } = useI18n();
const { formatCurrencySymbol } = useCurrencyFormatter();

const { formatDatetime } = useDatetimeFormatter();

const ALL = "__all__";

// Local filter state — convert backend "all" default to our sentinel
const status = ref(props.filters.status === "all" || !props.filters.status ? ALL : props.filters.status);
const registerId = ref<string | number>(props.filters.cash_register_id ?? ALL);
const cashierId = ref<string | number>(props.filters.user_id ?? ALL);
const dateFrom = ref<Date | null>(props.filters.date_from ? new Date(props.filters.date_from) : null);
const dateTo = ref<Date | null>(props.filters.date_to ? new Date(props.filters.date_to) : null);
const filterPopover = ref();

const statusOptions = computed(() => [
  { label: t("All"), value: ALL },
  { label: t("Open"), value: "open" },
  { label: t("Closed"), value: "closed" },
  { label: t("Forced Close"), value: "forced_close" },
]);

const registerOptions = computed(() => [
  { label: t("All Registers"), value: ALL },
  ...props.registers.map((r) => ({ label: r.name, value: r.id })),
]);

const cashierOptions = computed(() => [
  { label: t("All Cashiers"), value: ALL },
  ...props.cashiers.map((c) => ({ label: c.full_name, value: c.id })),
]);

const hasActiveFilters = computed(
  () =>
    status.value !== ALL ||
    registerId.value !== ALL ||
    cashierId.value !== ALL ||
    dateFrom.value !== null ||
    dateTo.value !== null,
);

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== ALL) count++;
  if (registerId.value !== ALL) count++;
  if (cashierId.value !== ALL) count++;
  if (dateFrom.value !== null) count++;
  if (dateTo.value !== null) count++;
  return count;
});

function formatDateParam(date: Date | null): string | null {
  if (!date) return null;
  return date.toISOString().split("T")[0];
}

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("shifts"), {
    data: {
      status: status.value === ALL ? null : status.value,
      cash_register_id: registerId.value === ALL ? null : registerId.value,
      user_id: cashierId.value === ALL ? null : cashierId.value,
      date_from: formatDateParam(dateFrom.value),
      date_to: formatDateParam(dateTo.value),
      ...overrides,
    },
    preserveState: true,
    replace: true,
  });
}

function resetFilters() {
  status.value = ALL;
  registerId.value = ALL;
  cashierId.value = ALL;
  dateFrom.value = null;
  dateTo.value = null;
  applyFilters();
}

watch(status, () => applyFilters());
watch(registerId, () => applyFilters());
watch(cashierId, () => applyFilters());

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

function shiftStatusSeverity(shiftStatus: string) {
  switch (shiftStatus) {
    case "open":
      return "success";
    case "closed":
      return "info";
    case "forced_close":
      return "danger";
    default:
      return "secondary";
  }
}

function shiftStatusLabel(shiftStatus: string) {
  switch (shiftStatus) {
    case "open":
      return t("Open");
    case "closed":
      return t("Closed");
    case "forced_close":
      return t("Forced Close");
    default:
      return shiftStatus;
  }
}

function differenceSeverity(diff: number | null) {
  if (diff === null) return "secondary";
  if (diff === 0) return "success";
  return diff > 0 ? "success" : "danger";
}

function viewShift(shiftId: number) {
  router.visit(route("shifts.show", shiftId));
}

// Open Shift dialog
const showOpenDialog = ref(false);
const onShiftOpened = () => {
  showOpenDialog.value = false;
  router.visit(route("shifts"), { preserveState: false });
};
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Shifts") }}
      </h2>
      <Button v-can="'shift.open'" :label="t('Open Shift')" icon="fa fa-clock" raised class="ml-2 uppercase" @click="showOpenDialog = true" />
    </div>
    <Toast />
    <Card>
      <template #content>
        <DataTable
          :value="shifts.data"
          resizable-columns
          lazy
          :total-records="shifts.meta.total"
          :rows="shifts.meta.per_page"
          :first="(shifts.meta.current_page - 1) * shifts.meta.per_page"
          paginator
          @page="onPage($event)"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No shifts found") }}</span>
              <span class="text-sm mt-1">{{ t("Open a shift to get started") }}</span>
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
                :pt="{ label: { class: 'hidden sm:inline' } }"
                @click="filterPopover.toggle($event)"
              />
              <Badge v-if="activeFilterCount > 0" :value="activeFilterCount" severity="primary" />
            </div>

            <Popover ref="filterPopover">
              <div class="flex flex-col gap-4 p-4 min-w-72">
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Status") }}</label>
                  <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" :empty-message="t('No available options')" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Register") }}</label>
                  <Select v-model="registerId" :options="registerOptions" option-label="label" option-value="value" :empty-message="t('No available options')" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Cashier") }}</label>
                  <Select v-model="cashierId" :options="cashierOptions" option-label="label" option-value="value" :empty-message="t('No available options')" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Date From") }}</label>
                  <DatePicker v-model="dateFrom" show-icon fluid @date-select="applyFilters()" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Date To") }}</label>
                  <DatePicker v-model="dateTo" show-icon fluid @date-select="applyFilters()" />
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
          <Column field="cash_register" :header="t('Cash Register')">
            <template #body="{ data }: { data: CashRegisterShiftResponse }">
              {{ data.cash_register?.name ?? "---" }}
            </template>
          </Column>
          <Column field="user" :header="t('Cashier')">
            <template #body="{ data }: { data: CashRegisterShiftResponse }">
              {{ data.user?.full_name ?? "---" }}
            </template>
          </Column>
          <Column field="status" :header="t('Status')" sortable>
            <template #body="{ data }: { data: CashRegisterShiftResponse }">
              <Tag :severity="shiftStatusSeverity(data.status)" :value="shiftStatusLabel(data.status)" />
            </template>
          </Column>
          <Column field="opened_at" :header="t('Opening Time')" sortable>
            <template #body="{ data }: { data: CashRegisterShiftResponse }">
              {{ formatDatetime(data.opened_at) }}
            </template>
          </Column>
          <Column field="closed_at" :header="t('Closing Time')">
            <template #body="{ data }: { data: CashRegisterShiftResponse }">
              {{ data.closed_at ? formatDatetime(data.closed_at) : "---" }}
            </template>
          </Column>
          <Column field="opening_balance" :header="t('Opening Balance')">
            <template #body="{ data }: { data: CashRegisterShiftResponse }">
              {{ formatCurrencySymbol(String(data.opening_balance)) }}
            </template>
          </Column>
          <Column field="closing_balance" :header="t('Closing Balance')">
            <template #body="{ data }: { data: CashRegisterShiftResponse }">
              {{ data.closing_balance !== null ? formatCurrencySymbol(String(data.closing_balance)) : "---" }}
            </template>
          </Column>
          <Column field="difference" :header="t('Difference')">
            <template #body="{ data }: { data: CashRegisterShiftResponse }">
              <Tag
                v-if="data.difference !== null"
                :severity="differenceSeverity(data.difference)"
                :value="formatCurrencySymbol(String(data.difference))"
              />
              <span v-else class="text-surface-400">---</span>
            </template>
          </Column>
          <Column :header="t('Actions')">
            <template #body="{ data }: { data: CashRegisterShiftResponse }">
              <div class="flex justify-start gap-2">
                <Button
                  v-tooltip.top="t('View')"
                  icon="fa fa-eye"
                  text
                  size="large"
                  rounded
                  @click="viewShift(data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <OpenShiftDialog v-model:visible="showOpenDialog" :registers="registers" @shift-opened="onShiftOpened" />
  </div>
</template>