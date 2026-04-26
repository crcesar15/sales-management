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
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type {
  StockOverviewResponse,
  StockOverviewFilters,
} from "@/Types/stock-overview-types";
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
const storeId = ref<number | null>(props.filters.store_id ?? null);
const categoryId = ref<number | null>(props.filters.category_id ?? null);
const brandId = ref<number | null>(props.filters.brand_id ?? null);
const lowStock = ref(props.filters.low_stock ?? false);
const sortField = ref(props.filters.order_by ?? "product_name");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);

const storeOptions = [
  { label: t("All Stores"), value: null },
  ...props.stores.map((s) => ({ label: s.name, value: s.id })),
];

const categoryOptions = [
  { label: t("All Categories"), value: null },
  ...props.categories.map((c) => ({ label: c.name, value: c.id })),
];

const brandOptions = [
  { label: t("All Brands"), value: null },
  ...props.brands.map((b) => ({ label: b.name, value: b.id })),
];

function applyFilters(overrides: Record<string, unknown> = {}) {
  router.visit(route("inventory.stock"), {
    data: {
      search: search.value,
      store_id: storeId.value ?? "",
      category_id: categoryId.value ?? "",
      brand_id: brandId.value ?? "",
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

watch(storeId, () => applyFilters({ store_id: storeId.value ?? "" }));
watch(categoryId, () => applyFilters({ category_id: categoryId.value ?? "" }));
watch(brandId, () => applyFilters({ brand_id: brandId.value ?? "" }));
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
        {{ t("Stock Overview") }}
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
              <div class="xl:col-span-2 lg:col-span-3 md:col-span-4 col-span-12">
                <Select v-model="storeId" :options="storeOptions" option-label="label" option-value="value" class="w-full" />
              </div>
              <div class="xl:col-span-2 lg:col-span-3 md:col-span-4 col-span-12">
                <Select v-model="categoryId" :options="categoryOptions" option-label="label" option-value="value" class="w-full" />
              </div>
              <div class="xl:col-span-2 lg:col-span-3 md:col-span-4 col-span-12">
                <Select v-model="brandId" :options="brandOptions" option-label="label" option-value="value" class="w-full" />
              </div>
              <div class="xl:col-span-2 lg:col-span-3 md:col-span-4 col-span-12 flex items-center gap-2">
                <Checkbox v-model="lowStock" :binary="true" input-id="low-stock" />
                <label for="low-stock" class="text-sm">{{ t("Low Stock Only") }}</label>
              </div>
              <div class="xl:col-span-2 xl:col-start-11 lg:col-span-3 lg:col-start-10 md:col-span-4 col-span-12">
                <IconField icon-position="left" class="w-full">
                  <InputIcon class="fa fa-search" />
                  <InputText v-model="search" :placeholder="t('Search')" class="w-full" />
                </IconField>
              </div>
            </div>
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

          <Column field="total_stock" :header="t('Total Stock')" sortable>
            <template #body="{ data }">
              <span :class="{ 'text-red-500 font-bold': data.is_low_stock }">{{ data.total_stock }}</span>
            </template>
          </Column>

          <Column field="is_low_stock" :header="t('Status')" style="width: 120px">
            <template #body="{ data }">
              <Tag v-if="data.is_low_stock" :value="t('Low Stock')" severity="danger" />
              <Tag v-else :value="t('In Stock')" severity="success" />
            </template>
          </Column>

          <Column :header="t('Actions')" style="width: 80px">
            <template #body="{ data }">
              <Button
                v-tooltip.top="t('View Details')"
                icon="fa-solid fa-eye"
                text
                rounded
                size="small"
                :aria-label="t('View Details')"
                @click="router.visit(route('inventory.stock.show', { variant: data.id }))"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
