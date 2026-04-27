<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Button,
  Select,
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
  StockTransferListResponse,
  StockTransferFilters,
  StockTransferResponse,
} from "@/Types/stock-transfer-types";
import TransferStatusTag from "./Show/Components/TransferStatusTag.vue";
import CancelTransferModal from "./Show/Components/CancelTransferModal.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  transfers: StockTransferListResponse;
  filters: StockTransferFilters;
  stores: Array<{ id: number; name: string; code: string }>;
}>();

const { t } = useI18n();

const ALL = "__all__";

const status = ref(props.filters.status || ALL);
const fromStoreId = ref<string | number>(props.filters.from_store_id ?? ALL);
const toStoreId = ref<string | number>(props.filters.to_store_id ?? ALL);
const filterPopover = ref();

const cancelModalVisible = ref(false);
const selectedTransferId = ref<number | null>(null);

const statusOptions = computed(() => [
  { label: t("All"), value: ALL },
  { label: t("Requested"), value: "requested" },
  { label: t("Picked"), value: "picked" },
  { label: t("In Transit"), value: "in_transit" },
  { label: t("Received"), value: "received" },
  { label: t("Completed"), value: "completed" },
  { label: t("Cancelled"), value: "cancelled" },
]);

const storeOptions = computed(() => [
  { label: t("All Stores"), value: ALL },
  ...props.stores.map((s) => ({ label: s.name, value: s.id })),
]);

const hasActiveFilters = computed(
  () => status.value !== ALL || fromStoreId.value !== ALL || toStoreId.value !== ALL,
);

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== ALL) count++;
  if (fromStoreId.value !== ALL) count++;
  if (toStoreId.value !== ALL) count++;
  return count;
});

function resetFilters() {
  status.value = ALL;
  fromStoreId.value = ALL;
  toStoreId.value = ALL;
  applyFilters();
}

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("stock-transfers"), {
    data: {
      status: status.value === ALL ? "" : status.value,
      from_store_id: fromStoreId.value === ALL ? "" : fromStoreId.value,
      to_store_id: toStoreId.value === ALL ? "" : toStoreId.value,
      ...overrides,
    },
    preserveState: true,
    replace: true,
  });
}

watch(status, () => applyFilters());
watch(fromStoreId, () => applyFilters());
watch(toStoreId, () => applyFilters());

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

function openCancelModal(transferId: number) {
  selectedTransferId.value = transferId;
  cancelModalVisible.value = true;
}

function formatDate(date: string | null): string {
  if (!date) return "---";
  return new Date(date).toLocaleDateString();
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-center m-0">
        {{ t("Stock Transfers") }}
      </h2>
      <Button
        v-can="'stock_transfer.create'"
        :label="t('New Transfer')"
        icon="fa fa-plus"
        @click="router.visit(route('stock-transfers.create'))"
      />
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="transfers.data"
          resizable-columns
          lazy
          :total-records="transfers.meta.total"
          :rows="transfers.meta.per_page"
          :first="(transfers.meta.current_page - 1) * transfers.meta.per_page"
          paginator
          :page-link-size="3"
          @page="onPage($event)"
        >
          <template #empty>
            {{ t("No stock transfers found") }}
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
                  <label class="text-sm font-medium mb-1 block">{{ t("Status") }}</label>
                  <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("From Store") }}</label>
                  <Select v-model="fromStoreId" :options="storeOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("To Store") }}</label>
                  <Select v-model="toStoreId" :options="storeOptions" option-label="label" option-value="value" class="w-full" />
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

          <Column :header="t('Status')" style="width: 120px">
            <template #body="{ data }: { data: StockTransferResponse }">
              <TransferStatusTag :status="data.status" />
            </template>
          </Column>

          <Column :header="t('From Store')" style="width: 150px">
            <template #body="{ data }: { data: StockTransferResponse }">
              {{ data.from_store?.name ?? "---" }}
            </template>
          </Column>

          <Column :header="t('To Store')" style="width: 150px">
            <template #body="{ data }: { data: StockTransferResponse }">
              {{ data.to_store?.name ?? "---" }}
            </template>
          </Column>

          <Column :header="t('Requested By')" style="width: 150px">
            <template #body="{ data }: { data: StockTransferResponse }">
              {{ data.requested_by_user?.full_name ?? "---" }}
            </template>
          </Column>

          <Column :header="t('Items')" style="width: 80px">
            <template #body="{ data }: { data: StockTransferResponse }">
              {{ data.items?.length ?? 0 }}
            </template>
          </Column>

          <Column :header="t('Created At')" style="width: 120px">
            <template #body="{ data }: { data: StockTransferResponse }">
              {{ formatDate(data.created_at) }}
            </template>
          </Column>

          <Column :header="t('Actions')" style="width: 120px">
            <template #body="{ data }: { data: StockTransferResponse }">
              <div class="flex gap-1">
                <Button
                  v-tooltip.top="t('View')"
                  icon="fa-solid fa-eye"
                  text
                  rounded
                  size="small"
                  :aria-label="t('View')"
                  @click="router.visit(route('stock-transfers.show', { stockTransfer: data.id }))"
                />
                <Button
                  v-if="data.status !== 'completed' && data.status !== 'cancelled'"
                  v-tooltip.top="t('Cancel Transfer')"
                  v-can="'stock_transfer.cancel'"
                  icon="fa-solid fa-ban"
                  text
                  rounded
                  size="small"
                  severity="danger"
                  :aria-label="t('Cancel Transfer')"
                  @click="openCancelModal(data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <CancelTransferModal
      v-if="selectedTransferId"
      v-model:visible="cancelModalVisible"
      :transfer-id="selectedTransferId"
    />
  </div>
</template>
