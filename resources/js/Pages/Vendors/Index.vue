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
  ConfirmDialog,
  Select,
  Popover,
  Badge,
  Tag,
  useToast,
  useConfirm,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";

import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { VendorResponse } from "@/Types/vendor-types";
import { useI18n } from "vue-i18n";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  vendors: {
    data: VendorResponse[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
  filters: {
    filter?: string | null;
    status?: string;
    order_by?: string;
    order_direction?: string;
    per_page?: number;
  };
}>();

const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();
const { formatDatetime } = useDatetimeFormatter();

const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "all");
const sortField = ref(props.filters.order_by ?? "fullname");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);
const filterPopover = ref();

const statusOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Active"), value: "active" },
  { label: t("Inactive"), value: "inactive" },
  { label: t("Archived"), value: "archived" },
]);

const hasActiveFilters = computed(() => status.value !== "all" || filter.value !== "");

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== "all") count++;
  if (filter.value !== "") count++;
  return count;
});

const vendors = computed(() =>
  props.vendors.data.map((item) => ({
    ...item,
    created_at: formatDatetime(item.created_at),
    updated_at: formatDatetime(item.updated_at),
  })),
);

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("vendors"), {
    data: {
      filter: filter.value,
      status: status.value === "all" ? null : status.value,
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
  filter.value = "";
  applyFilters();
}

let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, () => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    applyFilters();
  }, 300);
});

watch(status, () => {
  applyFilters();
});

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "fullname";
  sortOrder.value = event.sortOrder ?? 1;
  applyFilters();
};

const editVendor = (vendor: VendorResponse) => {
  router.visit(route("vendors.edit", vendor.id));
};

const showProducts = (vendor: VendorResponse) => {
  router.visit(route("vendors.products", vendor.id));
};

const deleteVendor = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to delete this vendor?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("vendors.destroy", id), {
        onSuccess: () => {
          toast.add({
            severity: "success",
            summary: t("Success"),
            detail: t("Vendor deleted successfully"),
            life: 3000,
          });
        },
        onError: () => {
          toast.add({
            severity: "error",
            summary: t("Error"),
            detail: t("Could not delete vendor"),
            life: 3000,
          });
        },
      });
    },
  });
};
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Vendors") }}
      </h2>
      <Button
        v-can="'vendor.create'"
        :label="t('Add Vendor')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="$inertia.visit(route('vendors.create'))"
      />
    </div>
    <ConfirmDialog />
    <Toast />
    <Card>
      <template #content>
        <DataTable
          :value="vendors"
          resizable-columns
          lazy
          :total-records="props.vendors.meta.total"
          :rows="props.vendors.meta.per_page"
          :first="(props.vendors.meta.current_page - 1) * props.vendors.meta.per_page"
          paginator
          sort-field="fullname"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No vendors found") }}</span>
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
                  <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
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
          <Column field="fullname" :header="t('Full Name')" sortable>
            <template #body="{ data }">
              <span :class="{ 'opacity-50': data.status === 'archived' }">
                {{ data.fullname }}
              </span>
            </template>
          </Column>
          <Column field="email" :header="t('Email')" sortable>
            <template #body="{ data }">
              <span :class="{ 'opacity-50': data.status === 'archived' }">
                {{ data.email ?? "—" }}
              </span>
            </template>
          </Column>
          <Column field="phone" :header="t('Phone')" sortable>
            <template #body="{ data }">
              <span :class="{ 'opacity-50': data.status === 'archived' }">
                {{ data.phone ?? "—" }}
              </span>
            </template>
          </Column>
          <Column field="status" :header="t('Status')" sortable>
            <template #body="{ data }">
              <Tag v-if="data.status === 'active'" severity="success" :value="t('Active')" rounded />
              <Tag v-else-if="data.status === 'inactive'" severity="warn" :value="t('Inactive')" rounded />
              <Tag v-else-if="data.status === 'archived'" severity="secondary" :value="t('Archived')" rounded />
            </template>
          </Column>
          <Column field="variants_count" :header="t('Catalog Items')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Tag rounded severity="secondary" :value="data.variants_count ?? 0" />
              </div>
            </template>
          </Column>
          <Column field="purchase_orders_count" :header="t('Purchase Orders')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Tag rounded severity="secondary" :value="data.purchase_orders_count ?? 0" />
              </div>
            </template>
          </Column>
          <Column field="actions" :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center gap-2">
                <Button
                  v-can="'vendor.edit'"
                  v-tooltip.top="t('Products')"
                  icon="fa fa-table-list"
                  text
                  size="large"
                  rounded
                  @click="showProducts(data)"
                />
                <Button
                  v-can="'vendor.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="large"
                  rounded
                  @click="editVendor(data)"
                />
                <Button
                  v-if="data.status !== 'archived'"
                  v-can="'vendor.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  size="large"
                  rounded
                  @click="deleteVendor(data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
