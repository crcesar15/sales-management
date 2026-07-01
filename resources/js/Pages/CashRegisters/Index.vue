<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Toast,
  Button,
  InputText,
  IconField,
  InputIcon,
  Select,
  Popover,
  Badge,
  ConfirmDialog,
  Tag,
  useToast,
  useConfirm,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import RegisterEditor from "@pages/CashRegisters/List/RegisterEditor.vue";
import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { CashRegisterResponse, CashRegisterListResponse, CashRegisterFilters } from "@/Types/cash-register-types";
import { useI18n } from "vue-i18n";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  registers: CashRegisterListResponse;
  filters: CashRegisterFilters;
  stores: Array<{ id: number; name: string; code: string }>;
}>();

const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

const ALL = "__all__";

// Local filter/sort state — convert backend "all" default to our sentinel
const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status === "all" || !props.filters.status ? ALL : props.filters.status);
const storeId = ref<string | number>(props.filters.store_id ?? ALL);
const sortField = ref(props.filters.order_by ?? "name");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);
const filterPopover = ref();

const statusOptions = computed(() => [
  { label: t("All"), value: ALL },
  { label: t("Active"), value: "active" },
  { label: t("Inactive"), value: "inactive" },
]);

const storeOptions = computed(() => [{ label: t("All Stores"), value: ALL }, ...props.stores.map((s) => ({ label: s.name, value: s.id }))]);

const hasActiveFilters = computed(() => status.value !== ALL || filter.value !== "" || storeId.value !== ALL);

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== ALL) count++;
  if (filter.value !== "") count++;
  if (storeId.value !== ALL) count++;
  return count;
});

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("cash-registers"), {
    data: {
      filter: filter.value || null,
      status: status.value === ALL ? null : status.value,
      store_id: storeId.value === ALL ? null : storeId.value,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      ...overrides,
    },
    preserveState: true,
    replace: true,
  });
}

function resetFilters() {
  status.value = ALL;
  storeId.value = ALL;
  filter.value = "";
  applyFilters();
}

let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, () => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => applyFilters(), 300);
});

watch(status, () => applyFilters());
watch(storeId, () => applyFilters());

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "name";
  sortOrder.value = event.sortOrder ?? 1;
  applyFilters();
};

// Create/Edit Register dialog
let showModal = ref(false);
let selectedRegister = ref<CashRegisterResponse | null>(null);

const addRegister = () => {
  selectedRegister.value = null;
  showModal.value = true;
};

const editRegister = (reg: CashRegisterResponse) => {
  selectedRegister.value = reg;
  showModal.value = true;
};

// Delete Register
const deleteRegister = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to delete this register?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("cash-registers.destroy", id), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Register deleted successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not delete register"), life: 3000 });
        },
      });
    },
  });
};

function statusSeverity(statusValue: string) {
  switch (statusValue) {
    case "active":
      return "success";
    case "inactive":
      return "secondary";
    default:
      return "info";
  }
}

function shiftStatusSeverity(shiftStatus: string | null | undefined) {
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

function shiftStatusLabel(shiftStatus: string | null | undefined) {
  switch (shiftStatus) {
    case "open":
      return t("Open");
    case "closed":
      return t("Closed");
    case "forced_close":
      return t("Forced Close");
    default:
      return t("No shift");
  }
}
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Cash Registers") }}
      </h2>
      <Button
        v-can="'cash_register.create'"
        :label="t('Add Register')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="addRegister"
      />
    </div>
    <ConfirmDialog />
    <Toast />
    <Card>
      <template #content>
        <DataTable
          :value="registers.data"
          resizable-columns
          lazy
          :total-records="registers.meta.total"
          :rows="registers.meta.per_page"
          :first="(registers.meta.current_page - 1) * registers.meta.per_page"
          paginator
          sort-field="name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No registers found") }}</span>
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
              <IconField icon-position="left" class="flex-1 sm:flex-none sm:w-80 sm:ml-auto">
                <InputIcon class="fa fa-search" />
                <InputText v-model="filter" :placeholder="t('Search')" class="w-full" />
              </IconField>
            </div>

            <Popover ref="filterPopover">
              <div class="flex flex-col gap-4 p-4 min-w-72">
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Status") }}</label>
                  <Select
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    :empty-message="t('No available options')"
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
                    :empty-message="t('No available options')"
                    class="w-full"
                  />
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
          <Column field="name" :header="t('Register Name')" sortable>
            <template #body="{ data }: { data: CashRegisterResponse }">
              <span class="font-medium">{{ data.name }}</span>
            </template>
          </Column>
          <Column field="code" :header="t('Code')" sortable />
          <Column field="store_id" :header="t('Store')">
            <template #body="{ data }: { data: CashRegisterResponse }">
              {{ data.store?.name ?? "---" }}
            </template>
          </Column>
          <Column field="status" :header="t('Status')" sortable>
            <template #body="{ data }: { data: CashRegisterResponse }">
              <Tag :severity="statusSeverity(data.status)" :value="data.status === 'active' ? t('Active') : t('Inactive')" />
            </template>
          </Column>
          <Column field="is_default" :header="t('Default')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }: { data: CashRegisterResponse }">
              <div class="flex justify-center">
                <Tag v-if="data.is_default" rounded severity="primary" :value="t('Default')" />
              </div>
            </template>
          </Column>
          <Column field="current_shift" :header="t('Current Shift')">
            <template #body="{ data }: { data: CashRegisterResponse }">
              <Tag
                v-if="data.current_shift"
                :severity="shiftStatusSeverity(data.current_shift.status)"
                :value="shiftStatusLabel(data.current_shift.status)"
              />
              <span v-else class="text-surface-400">{{ t("No shift") }}</span>
            </template>
          </Column>
          <Column :header="t('Actions')">
            <template #body="{ data }: { data: CashRegisterResponse }">
              <div class="flex justify-start gap-2">
                <Button
                  v-can="'cash_register.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="large"
                  rounded
                  @click="editRegister(data)"
                />
                <Button
                  v-can="'cash_register.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  size="large"
                  rounded
                  @click="deleteRegister(data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <RegisterEditor v-model:show-modal="showModal" :register="selectedRegister" :stores="stores" />
  </div>
</template>
