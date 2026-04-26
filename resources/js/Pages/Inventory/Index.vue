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
  Tag,
  Badge,
  Checkbox,
  Popover,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { StockOverviewResponse, StockOverviewFilters } from "@/Types/stock-overview-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

defineOptions({ layout: AppLayout });
const props = defineProps<{
  variants: StockOverviewResponse;
  filters: StockOverviewFilters;
  stores: Array<{ id: number; name: string; code: string }>;
  categories: Array<{ id: number; name: string }>;
  brands: Array<{ id: number; name: string }>;
}>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const search = ref(props.filters.search ?? "");
const status = ref(props.filters.status ?? "active");
const storeId = ref<string | number>(props.filters.store_id ?? ALL);
const categoryId = ref<string | number>(props.filters.category_id ?? ALL);
const brandId = ref<string | number>(props.filters.brand_id ?? ALL);

const ALL = "__all__";

const storeOptions = computed(() => [
  { label: t("All Stores"), value: ALL },
  ...props.stores.map((s) => ({ label: s.name, value: s.id })),
]);

const categoryOptions = computed(() => [
  { label: t("All Categories"), value: ALL },
  ...props.categories.map((c) => ({ label: c.name, value: c.id })),
]);

const brandOptions = computed(() => [
  { label: t("All Brands"), value: ALL },
  ...props.brands.map((b) => ({ label: b.name, value: b.id })),
]);
const lowStock = ref(props.filters.low_stock ?? false);
const sortField = ref(props.filters.order_by ?? "product_name");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);
const filterPopover = ref();

const statusOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Active"), value: "active" },
  { label: t("Inactive"), value: "inactive" },
  { label: t("Archived"), value: "archived" },
]);

const statusLabel = (s: string) => {
  const map: Record<string, string> = { active: t("Active"), inactive: t("Inactive"), archived: t("Archived") };
  return map[s] ?? s;
};

const statusSeverity = (s: string): "success" | "warn" | "danger" | "info" => {
  const map: Record<string, "success" | "warn" | "danger"> = { active: "success", inactive: "warn", archived: "danger" };
  return map[s] ?? "info";
};

const hasActiveFilters = computed(
  () => status.value !== "active" || storeId.value !== ALL || categoryId.value !== ALL || brandId.value !== ALL || lowStock.value,
);

const activeFilterCount = computed(() => {
  let count = 0;
  if (status.value !== "active") count++;
  if (storeId.value !== ALL) count++;
  if (categoryId.value !== ALL) count++;
  if (brandId.value !== ALL) count++;
  if (lowStock.value) count++;
  return count;
});

function resetFilters() {
  status.value = "active";
  storeId.value = ALL;
  categoryId.value = ALL;
  brandId.value = ALL;
  lowStock.value = false;
  applyFilters();
}

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("inventory.variants"), {
    data: {
      search: search.value,
      status: status.value,
      store_id: storeId.value === ALL ? "" : storeId.value,
      category_id: categoryId.value === ALL ? "" : categoryId.value,
      brand_id: brandId.value === ALL ? "" : brandId.value,
      low_stock: lowStock.value,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      ...overrides,
    },
    preserveState: true,
    replace: true,
  });
}

let searchTimer: ReturnType<typeof setTimeout>;
watch(search, (val) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => applyFilters({ search: val }), 300);
});

watch(status, () => applyFilters({ status: status.value }));
watch(storeId, () => applyFilters({ store_id: storeId.value === ALL ? "" : storeId.value }));
watch(categoryId, () => applyFilters({ category_id: categoryId.value === ALL ? "" : categoryId.value }));
watch(brandId, () => applyFilters({ brand_id: brandId.value === ALL ? "" : brandId.value }));
watch(lowStock, () => applyFilters({ low_stock: lowStock.value }));

const onPage = (event: DataTablePageEvent) => {
  applyFilters({ page: event.page + 1, per_page: event.rows });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "product_name";
  sortOrder.value = event.sortOrder ?? -1;
  applyFilters();
};
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-center m-0">
        {{ t("Inventory") }}
      </h2>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="variants.data"
          resizable-columns
          lazy
          :total-records="variants.meta.total"
          :rows="variants.meta.per_page"
          :first="(variants.meta.current_page - 1) * variants.meta.per_page"
          paginator
          sort-field="product_name"
          :sort-order="1"
          :page-link-size="3"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ t("No variants found") }}
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
              <div class="lg:col-span-4 lg:col-start-9 md:col-start-5 col-start-1 col-span-12 flex items-end">
                <div class="flex-1">
                  <IconField icon-position="left" class="w-full">
                    <InputIcon class="fa fa-search" />
                    <InputText v-model="search" :placeholder="t('Search')" class="w-full" />
                  </IconField>
                </div>
              </div>
            </div>

            <Popover ref="filterPopover">
              <div class="flex flex-col gap-4 p-4" style="width: 320px">
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Status") }}</label>
                  <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Store") }}</label>
                  <Select v-model="storeId" :options="storeOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Category") }}</label>
                  <Select v-model="categoryId" :options="categoryOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div>
                  <label class="text-sm font-medium mb-1 block">{{ t("Brand") }}</label>
                  <Select v-model="brandId" :options="brandOptions" option-label="label" option-value="value" class="w-full" />
                </div>
                <div class="flex items-center gap-2">
                  <Checkbox v-model="lowStock" :binary="true" input-id="low-stock-popover" />
                  <label for="low-stock-popover" class="text-sm">{{ t("Low Stock Only") }}</label>
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

          <Column :header="t('Image')" style="width: 60px">
            <template #body="{ data }">
              <div class="flex justify-center">
                <img
                  v-if="data.images.length"
                  :src="data.images[0].thumb_url"
                  :alt="data.product_name ?? t('Variant image')"
                  class="rounded-xl border-slate-300 dark:border-slate-700 shadow-md"
                  style="height: 45px; width: 45px; object-fit: cover"
                />
                <div
                  v-else
                  class="bg-surface-50 dark:bg-surface-950 rounded-xl justify-center items-center flex border-slate-300 dark:border-slate-700 shadow-md"
                  style="height: 45px; width: 45px"
                >
                  <p style="font-size: 14px; font-weight: bold">
                    {{ (data.product_name ?? "").substring(0, 2).toUpperCase() }}
                  </p>
                </div>
              </div>
            </template>
          </Column>

          <Column field="product_name" :header="t('Product')" sortable>
            <template #body="{ data }">
              <span class="text-900 font-medium">{{ data.product_name }}</span>
              <div v-if="data.values.length" class="flex flex-wrap gap-1 mt-1">
                <Badge v-for="val in data.values" :key="val.id" :value="`${val.option_name}: ${val.value}`" severity="secondary" />
              </div>
            </template>
          </Column>

          <Column field="brand_name" :header="t('Brand')" sortable>
            <template #body="{ data }">
              {{ data.brand_name ?? "---" }}
            </template>
          </Column>

          <Column field="identifier" :header="t('Identifier')" sortable>
            <template #body="{ data }">
              {{ data.identifier ?? "---" }}
            </template>
          </Column>

          <Column field="price" :header="t('Price')" sortable>
            <template #body="{ data }">
              {{ formatCurrency(String(data.price)) }}
            </template>
          </Column>

          <Column field="total_stock" :header="t('Stock')" sortable>
            <template #body="{ data }">
              <span :class="{ 'text-red-500 font-bold': data.is_low_stock }">{{ data.total_stock }}</span>
            </template>
          </Column>

          <Column :header="t('Status')" style="width: 180px">
            <template #body="{ data }">
              <div class="flex flex-wrap gap-1">
                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                <Tag v-if="data.is_low_stock" :value="t('Low Stock')" severity="danger" />
              </div>
            </template>
          </Column>

          <Column :header="t('Actions')" style="width: 100px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button
                  v-tooltip.top="t('Manage')"
                  icon="fa-solid fa-list"
                  text
                  rounded
                  size="small"
                  :aria-label="t('Manage')"
                  @click="router.visit(route('inventory.variants.show', { product: data.product_id, variant: data.id }))"
                />
                <Button
                  v-tooltip.top="t('Stock Details')"
                  icon="fa-solid fa-eye"
                  text
                  rounded
                  size="small"
                  :aria-label="t('Stock Details')"
                  @click="router.visit(route('inventory.stock.show', { variant: data.id }))"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
