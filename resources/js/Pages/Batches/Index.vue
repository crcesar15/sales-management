<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Button,
  InputText,
  IconField,
  InputIcon,
  Select,
  Badge,
  Checkbox,
  Popover,
  DatePicker,
  type DataTablePageEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { BatchListResponse, BatchFilters, BatchResponse } from "@/Types/batch-types";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import BatchStatusTag from "./Show/Components/BatchStatusTag.vue";
import CloseBatchModal from "./Show/Components/CloseBatchModal.vue";
import EditBatchModal from "./Show/Components/EditBatchModal.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  batches: BatchListResponse;
  filters: BatchFilters;
  stores: Array<{ id: number; name: string; code: string }>;
}>();

const { t } = useI18n();
const { formatDate, formatDatetime } = useDatetimeFormatter();

const ALL = "__all__";

const status = ref(props.filters.status || ALL);
const storeId = ref<string | number>(props.filters.store_id ?? ALL);
const expiringSoon = ref(props.filters.expiring_soon ?? false);
const expiryFrom = ref<Date | null>(props.filters.expiry_from ? new Date(props.filters.expiry_from) : null);
const expiryTo = ref<Date | null>(props.filters.expiry_to ? new Date(props.filters.expiry_to) : null);
const filterPopover = ref();

const productName = ref(props.filters.product_name ?? "");

const closeModalVisible = ref(false);
const selectedBatchId = ref<number | null>(null);

const editModalVisible = ref(false);
const editBatchId = ref<number | null>(null);
const editBatchIdentifier = ref<string | null>(null);
const editExpiryDate = ref<string | null>(null);

const statusOptions = computed(() => [
  { label: t("All"), value: ALL },
  { label: t("Active"), value: "active" },
  { label: t("Closed"), value: "closed" },
]);

const storeOptions = computed(() => [{ label: t("All Stores"), value: ALL }, ...props.stores.map((s) => ({ label: s.name, value: s.id }))]);

const hasActiveFilters = computed(
  () =>
    status.value !== ALL || storeId.value !== ALL || expiringSoon.value || expiryFrom.value || expiryTo.value || productName.value !== "",
);

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== ALL) count++;
  if (storeId.value !== ALL) count++;
  if (expiringSoon.value) count++;
  if (expiryFrom.value) count++;
  if (expiryTo.value) count++;
  if (productName.value !== "") count++;
  return count;
});

function resetFilters() {
  status.value = ALL;
  storeId.value = ALL;
  expiringSoon.value = false;
  expiryFrom.value = null;
  expiryTo.value = null;
  productName.value = "";
  applyFilters();
}

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("batches"), {
    data: {
      status: status.value === ALL ? "" : status.value,
      store_id: storeId.value === ALL ? "" : storeId.value,
      expiring_soon: expiringSoon.value,
      expiry_from: expiryFrom.value ? expiryFrom.value.toISOString().split("T")[0] : "",
      expiry_to: expiryTo.value ? expiryTo.value.toISOString().split("T")[0] : "",
      product_name: productName.value,
      ...overrides,
    },
    preserveState: true,
    replace: true,
  });
}

watch(status, () => applyFilters());
watch(storeId, () => applyFilters());
watch(expiringSoon, () => applyFilters());
watch(expiryFrom, () => applyFilters());
watch(expiryTo, () => applyFilters());

let filterTimer: ReturnType<typeof setTimeout>;
watch(productName, () => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => applyFilters(), 300);
});

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

function openCloseModal(batchId: number) {
  selectedBatchId.value = batchId;
  closeModalVisible.value = true;
}

function openEditModal(batch: BatchResponse) {
  editBatchId.value = batch.id;
  editBatchIdentifier.value = batch.batch_identifier;
  editExpiryDate.value = batch.expiry_date;
  editModalVisible.value = true;
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-center m-0">
        {{ t("Batches") }}
      </h2>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="batches.data"
          resizable-columns
          lazy
          :total-records="batches.meta.total"
          :rows="batches.meta.per_page"
          :first="(batches.meta.current_page - 1) * batches.meta.per_page"
          paginator
          :page-link-size="3"
          @page="onPage($event)"
        >
          <template #empty>
            {{ t("No batches found") }}
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
              <IconField icon-position="left" class="flex-1 sm:flex-none sm:w-80 sm:ml-auto">
                <InputIcon class="fa fa-search" />
                <InputText v-model="productName" :placeholder="t('Search')" class="w-full" />
              </IconField>
            </div>

            <Popover ref="filterPopover">
              <div class="flex flex-col gap-4 p-4 min-w-72">
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Status") }}</label>
                  <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Store") }}</label>
                  <Select v-model="storeId" :options="storeOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Expiry Date") }}</label>
                  <div class="flex gap-2">
                    <DatePicker v-model="expiryFrom" :placeholder="t('From')" show-icon class="w-full" />
                    <DatePicker v-model="expiryTo" :placeholder="t('To')" show-icon class="w-full" />
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Checkbox v-model="expiringSoon" :binary="true" input-id="expiring-soon-filter" />
                  <label for="expiring-soon-filter" class="text-sm">{{ t("Expiring Soon") }}</label>
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

          <Column :header="t('Status')" style="width: 100px">
            <template #body="{ data }: { data: BatchResponse }">
              <BatchStatusTag :status="data.status" />
            </template>
          </Column>

          <Column :header="t('Product Variant')">
            <template #body="{ data }: { data: BatchResponse }">
              <span class="text-900 font-medium">{{ data.product?.name ?? "---" }}</span>
              <div class="text-sm text-surface-500">{{ data.product_variant?.label }}</div>
            </template>
          </Column>

          <Column :header="t('Store')">
            <template #body="{ data }: { data: BatchResponse }">
              {{ data.store?.name ?? "---" }}
            </template>
          </Column>

          <Column :header="t('Batch Identifier')" style="width: 120px">
            <template #body="{ data }: { data: BatchResponse }">
              {{ data.batch_identifier ?? "---" }}
            </template>
          </Column>

          <Column :header="t('Expiry Date')" style="width: 120px">
            <template #body="{ data }: { data: BatchResponse }">
              <div class="flex items-center gap-2">
                <span
                  class="w-2.5 h-2.5 rounded-full"
                  :class="{
                    'bg-green-500': data.expiry_status === 'ok',
                    'bg-yellow-500': data.expiry_status === 'expiring_soon',
                    'bg-red-500': data.expiry_status === 'expired',
                  }"
                />
                <span>{{ formatDate(data.expiry_date) }}</span>
              </div>
            </template>
          </Column>

          <Column :header="t('Initial Quantity')" style="width: 110px" class="hidden md:table-cell">
            <template #body="{ data }: { data: BatchResponse }">
              {{ data.initial_quantity }}
            </template>
          </Column>

          <Column :header="t('Remaining Quantity')" style="width: 130px">
            <template #body="{ data }: { data: BatchResponse }">
              <span :class="{ 'text-red-500 font-bold': data.remaining_quantity === 0 }">
                {{ data.remaining_quantity }}
              </span>
            </template>
          </Column>

          <Column :header="t('Sold Quantity')" style="width: 110px" class="hidden md:table-cell">
            <template #body="{ data }: { data: BatchResponse }">
              {{ data.sold_quantity }}
            </template>
          </Column>

          <Column :header="t('Created At')" style="width: 120px" class="hidden md:table-cell">
            <template #body="{ data }: { data: BatchResponse }">
              {{ formatDatetime(data.created_at) }}
            </template>
          </Column>

          <Column :header="t('Actions')" style="width: 120px">
            <template #body="{ data }: { data: BatchResponse }">
              <div class="flex gap-1">
                <Button
                  v-tooltip.top="t('View')"
                  icon="fa-solid fa-eye"
                  text
                  rounded
                  :aria-label="t('View')"
                  @click="router.visit(route('batches.show', { batch: data.id }))"
                />
                <Button
                  v-if="data.status !== 'closed'"
                  v-can="'batch.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa-solid fa-pen"
                  text
                  rounded
                  :aria-label="t('Edit')"
                  @click="openEditModal(data)"
                />
                <Button
                  v-if="data.status !== 'closed'"
                  v-can="'batch.edit'"
                  v-tooltip.top="t('Close Batch')"
                  icon="fa-solid fa-ban"
                  text
                  rounded
                  :aria-label="t('Close Batch')"
                  @click="openCloseModal(data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <CloseBatchModal v-if="selectedBatchId" v-model:visible="closeModalVisible" :batch-id="selectedBatchId" />

    <EditBatchModal
      v-if="editBatchId"
      v-model:visible="editModalVisible"
      :batch-id="editBatchId"
      :batch-identifier="editBatchIdentifier"
      :expiry-date="editExpiryDate"
    />
  </div>
</template>
