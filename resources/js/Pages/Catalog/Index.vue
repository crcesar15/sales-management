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
  Badge,
  Popover,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";
import AppLayout from "@layouts/admin.vue";
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import type { CatalogVariantCollection } from "@/Types/catalog-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  variants: CatalogVariantCollection;
  filters: {
    filter?: string | null;
    status?: string;
    sort_field?: string;
    sort_direction?: string;
    vendor_id?: number | null;
  };
  vendors: { id: number; fullname: string }[];
}>();

const { t } = useI18n();

const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "active");
const vendorId = ref<number | null>(props.filters.vendor_id ?? null);
const sortField = ref(props.filters.sort_field ?? "product_name");
const sortOrder = ref(props.filters.sort_direction === "desc" ? -1 : 1);
const filterPopover = ref();

const statusOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Active"), value: "active" },
  { label: t("Inactive"), value: "inactive" },
  { label: t("Archived"), value: "archived" },
]);

const vendorOptions = computed(() => [
  { label: t("All Vendors"), value: null },
  ...props.vendors.map((v) => ({ label: v.fullname, value: v.id })),
]);

const hasActiveFilters = computed(() => status.value !== "active" || vendorId.value !== null);

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== "active") count++;
  if (vendorId.value !== null) count++;
  return count;
});

function resetFilters() {
  status.value = "active";
  vendorId.value = null;
  applyFilters();
}

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("catalog"), {
    data: {
      filter: filter.value,
      status: status.value,
      sort_field: sortField.value,
      sort_direction: sortOrder.value === -1 ? "desc" : "asc",
      vendor_id: vendorId.value,
      ...overrides,
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

watch(status, () => applyFilters());
watch(vendorId, () => applyFilters());

const onPage = (event: DataTablePageEvent) => {
  applyFilters({
    page: event.page + 1,
    per_page: event.rows,
  });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "product_name";
  sortOrder.value = event.sortOrder ?? 1;
  applyFilters();
};

const viewDetails = (productVariantId: number) => {
  router.visit(route("catalog.show", { productVariant: productVariantId }));
};
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-center m-0">{{ t("Catalog") }}</h2>
    </div>

    <Toast />

    <Card>
      <template #content>
        <DataTable
          :value="props.variants.data"
          data-key="id"
          lazy
          :total-records="props.variants.meta.total"
          :rows="props.variants.meta.per_page"
          :first="(props.variants.meta.current_page - 1) * props.variants.meta.per_page"
          paginator
          sort-field="product_name"
          :sort-order="1"
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
              <div class="lg:col-span-4 lg:col-start-9 md:col-start-7 col-start-1 col-span-12 flex items-end">
                <IconField icon-position="left" class="w-full">
                  <InputIcon class="fa fa-search" />
                  <InputText v-model="filter" :placeholder="t('Search')" fluid />
                </IconField>
              </div>
            </div>

            <Popover ref="filterPopover">
              <div class="flex flex-col gap-4 p-4" style="width: 320px">
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
              <div class="text-left font-bold">{{ data.product?.name ?? "\u2014" }}</div>
              <div v-if="data.values?.length" class="flex flex-wrap gap-1 mt-1">
                <Badge v-for="opt in data.values" :key="opt.option_name" :value="opt.value" />
              </div>
            </template>
          </Column>

          <Column field="brand_name" :header="t('Brand')">
            <template #body="{ data }">
              <span>{{ data.product?.brand?.name ?? "\u2014" }}</span>
            </template>
          </Column>

          <Column field="measurement_unit" :header="t('Base Unit')">
            <template #body="{ data }">
              <Badge class="capitalize" severity="secondary" size="xlarge" :value="data.product?.measurement_unit?.name ?? '&mdash;'" />
            </template>
          </Column>

          <Column field="purchase_units" :header="t('Purchase Units')">
            <template #body="{ data }">
              <div v-if="data.purchase_units?.length" class="flex flex-wrap gap-1">
                <Badge
                  v-for="unit in data.purchase_units"
                  size="xlarge"
                  class="capitalize"
                  :key="unit.id"
                  :value="unit.name"
                  severity="secondary"
                />
              </div>
              <span v-else class="text-surface-400">&mdash;</span>
            </template>
          </Column>

          <Column field="vendor_count" :header="t('Vendors')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Badge size="large" :value="data.vendor_count" />
              </div>
            </template>
          </Column>

          <Column :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Button v-tooltip.top="t('View Details')" icon="fa fa-eye" text rounded size="large" @click="viewDetails(data.id)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
