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
  Tag,
  Badge,
  useToast,
  useConfirm,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";
import AppLayout from "@layouts/admin.vue";
import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import type { VendorResponse } from "@/Types/vendor-types";
import type { CatalogResponse } from "@/Types/catalog-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  vendor: VendorResponse;
  catalog: {
    data: CatalogResponse[];
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

const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "active");
const sortField = ref(props.filters.order_by ?? "created_at");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);
const filterPopover = ref();

const statusOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Active"), value: "active" },
  { label: t("Inactive"), value: "inactive" },
  { label: t("Archived"), value: "archived" },
]);

const hasActiveFilters = computed(() => status.value !== "active" || filter.value !== "");

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== "active") count++;
  if (filter.value !== "") count++;
  return count;
});

const catalogItems = computed(() => props.catalog.data);

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("vendors.catalog", props.vendor.id), {
    data: {
      filter: filter.value,
      status: status.value,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      ...overrides,
    },
    preserveState: true,
    replace: true,
  });
}

function resetFilters() {
  status.value = "active";
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
  sortField.value = typeof event.sortField === "string" ? event.sortField : "created_at";
  sortOrder.value = event.sortOrder ?? 1;
  applyFilters();
};

const addEntry = () => {
  router.visit(route("vendors.catalog.create", props.vendor.id));
};

const editEntry = (entry: CatalogResponse) => {
  router.visit(route("vendors.catalog.edit", [props.vendor.id, entry.id]));
};

const deleteEntry = (entry: CatalogResponse) => {
  confirm.require({
    message: t("Are you sure you want to delete this catalog entry?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("vendors.catalog.destroy", [props.vendor.id, entry.id]), {
        onSuccess: () => {
          toast.add({
            severity: "success",
            summary: t("Success"),
            detail: t("Catalog entry deleted successfully"),
            life: 3000,
          });
        },
        onError: () => {
          toast.add({
            severity: "error",
            summary: t("Error"),
            detail: t("Could not delete catalog entry"),
            life: 3000,
          });
        },
      });
    },
  });
};

const goBack = () => {
  router.visit(route("vendors"));
};
</script>

<template>
  <div>
    <div class="flex items-center justify-between gap-2 mb-3">
      <div class="flex min-w-0 items-center gap-2 sm:gap-3">
        <Button icon="fa fa-arrow-left" text severity="secondary" class="shrink-0 hover:shadow-md" @click="goBack" />
        <h2 class="m-0 min-w-0 text-2xl font-bold leading-tight sm:text-2xl sm:leading-normal">
          <span class="hidden sm:inline">{{ vendor.fullname }} —</span>
          {{ t("Catalog") }}
        </h2>
      </div>
      <Button v-can="'catalog.create'" :label="t('Add Entry')" icon="fa fa-plus" raised class="shrink-0 uppercase" @click="addEntry" />
    </div>

    <ConfirmDialog />
    <Toast />

    <Card>
      <template #content>
        <DataTable
          :value="catalogItems"
          resizable-columns
          lazy
          :total-records="props.catalog.meta.total"
          :rows="props.catalog.meta.per_page"
          :first="(props.catalog.meta.current_page - 1) * props.catalog.meta.per_page"
          paginator
          sort-field="created_at"
          :sort-order="-1"
          @page="onPage"
          @sort="onSort"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No catalog entries found") }}</span>
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

          <Column field="product_name" :header="t('Product')" sortable>
            <template #body="{ data }">
              <span class="text-900 font-medium">{{ data.product_variant?.product?.name ?? "—" }}</span>
              <div v-if="data.product_variant?.values?.length" class="flex flex-wrap gap-1 mt-1">
                <Badge
                  v-for="opt in data.product_variant.values"
                  :key="opt.option_name"
                  :value="`${opt.option_name}: ${opt.value}`"
                  severity="secondary"
                />
              </div>
            </template>
          </Column>

          <Column field="purchase_unit.name" :header="t('Purchase Unit')">
            <template #body="{ data }">
              <span>{{ data.purchase_unit?.name ?? t("Base unit") }}</span>
            </template>
          </Column>

          <Column field="price" :header="t('Price')" sortable>
            <template #body="{ data }">
              <span>BOB {{ data.price.toFixed(2) }}</span>
            </template>
          </Column>

          <Column field="status" :header="t('Status')" sortable>
            <template #body="{ data }">
              <Tag v-if="data.status === 'active'" severity="success" :value="t('Active')" rounded />
              <Tag v-else-if="data.status === 'inactive'" severity="warn" :value="t('Inactive')" rounded />
              <Tag v-else severity="secondary" :value="t('Archived')" rounded />
            </template>
          </Column>

          <Column field="minimum_order_quantity" :header="t('MOQ')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Tag v-if="data.minimum_order_quantity" rounded severity="secondary" :value="String(data.minimum_order_quantity)" />
                <span v-else class="text-surface-400">—</span>
              </div>
            </template>
          </Column>

          <Column field="lead_time_days" :header="t('Lead Time')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Tag v-if="data.lead_time_days" rounded severity="secondary" :value="`${data.lead_time_days}d`" />
                <span v-else class="text-surface-400">—</span>
              </div>
            </template>
          </Column>

          <Column :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center gap-2">
                <Button
                  v-can="'catalog.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  size="large"
                  @click="editEntry(data)"
                />
                <Button
                  v-can="'catalog.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  size="large"
                  class="btn-danger"
                  @click="deleteEntry(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
