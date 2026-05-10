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
  SelectButton,
  Select,
  Badge,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";
import AppLayout from "@layouts/admin.vue";
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import type { CatalogResponse, CatalogGroupedEntry } from "@/Types/catalog-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import VendorComparisonRow from "./Components/VendorComparisonRow.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  catalogEntries: {
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
    sort_field?: string;
    sort_direction?: string;
    vendor_id?: number | null;
  };
  vendors: { id: number; fullname: string }[];
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "active");
const vendorId = ref<number | null>(props.filters.vendor_id ?? null);
const sortField = ref(props.filters.sort_field ?? "product_name");
const sortOrder = ref(props.filters.sort_direction === "desc" ? -1 : 1);
const expandedRows = ref<Record<number, boolean>>({});

const groupedEntries = computed<CatalogGroupedEntry[]>(() => {
  const groups = new Map<number, CatalogGroupedEntry>();

  for (const entry of props.catalogEntries.data) {
    const key = entry.product_variant_id;
    if (!groups.has(key)) {
      groups.set(key, {
        product_variant_id: key,
        product_name: entry.product_variant?.product?.name ?? "",
        variant_name: entry.product_variant?.name ?? "",
        catalog_entries: [],
        lowest_price: Infinity,
      });
    }
    const group = groups.get(key);
    if (group) {
      group.catalog_entries.push(entry);
      if (entry.price < group.lowest_price) {
        group.lowest_price = entry.price;
      }
    }
  }

  return Array.from(groups.values());
});

const statusOptions = [
  { label: t("All"), value: "all" },
  { label: t("Active"), value: "active" },
  { label: t("Inactive"), value: "inactive" },
];

const vendorOptions = computed(() => [
  { label: t("All Vendors"), value: null },
  ...props.vendors.map((v) => ({ label: v.fullname, value: v.id })),
]);

function visitCatalog(params: Record<string, unknown> = {}) {
  router.visit(route("catalog"), {
    data: {
      filter: filter.value,
      status: status.value,
      sort_field: sortField.value,
      sort_direction: sortOrder.value === -1 ? "desc" : "asc",
      vendor_id: vendorId.value,
      ...params,
    },
    preserveState: true,
    replace: true,
  });
}

let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, (_val) => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    visitCatalog();
  }, 300);
});

watch(status, () => visitCatalog());
watch(vendorId, () => visitCatalog());

const onPage = (event: DataTablePageEvent) => {
  visitCatalog({
    page: event.page + 1,
    per_page: event.rows,
  });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "product_name";
  sortOrder.value = event.sortOrder ?? 1;
  visitCatalog();
};

const goToVendors = () => {
  router.visit(route("vendors"));
};
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-center m-0">{{ t("Product Catalog") }}</h2>
      <div class="flex flex-col justify-center">
        <Button
          v-can="'catalog.create'"
          :label="t('Add Entry')"
          icon="fa fa-plus"
          raised
          class="uppercase"
          @click="router.visit(route('vendors'))"
        />
      </div>
    </div>

    <ConfirmDialog />
    <Toast />

    <Card>
      <template #content>
        <DataTable
          v-model:expanded-rows="expandedRows"
          :value="groupedEntries"
          data-key="product_variant_id"
          lazy
          :total-records="props.catalogEntries.meta.total"
          :rows="props.catalogEntries.meta.per_page"
          :first="(props.catalogEntries.meta.current_page - 1) * props.catalogEntries.meta.per_page"
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
              <div class="md:col-span-4 col-span-12 flex md:justify-start justify-center">
                <SelectButton
                  v-model="status"
                  :allow-empty="false"
                  :options="statusOptions"
                  option-label="label"
                  option-value="value"
                />
              </div>
              <div class="xl:col-span-2 lg:col-span-3 md:col-span-4 col-span-12">
                <Select
                  v-model="vendorId"
                  :options="vendorOptions"
                  option-label="label"
                  option-value="value"
                  :placeholder="t('All Vendors')"
                  class="w-full"
                />
              </div>
              <div class="flex xl:col-span-3 xl:col-start-10 lg:col-span-4 lg:col-start-8 md:col-span-6 md:col-start-7 col-span-12 md:justify-end justify-center">
                <IconField icon-position="left" class="w-full">
                  <InputIcon class="fa fa-search" />
                  <InputText v-model="filter" :placeholder="t('Search')" fluid />
                </IconField>
              </div>
            </div>
          </template>

          <Column expander style="width: 3rem" />

          <Column field="product_name" :header="t('Product')" sortable>
            <template #body="{ data }">
              <div class="flex flex-col">
                <span class="font-bold">{{ data.product_name }}</span>
                <div v-if="data.catalog_entries[0]?.product_variant?.values?.length" class="flex flex-wrap gap-1 mt-1">
                  <Badge
                    v-for="opt in data.catalog_entries[0].product_variant.values"
                    :key="opt.option_name"
                    :value="opt.value"
                  />
                </div>
              </div>
            </template>
          </Column>

          <Column field="variant_name" :header="t('Variant')">
            <template #body="{ data }">
              <span>{{ data.variant_name }}</span>
            </template>
          </Column>

          <Column field="vendor_count" :header="t('Vendors')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Badge :value="data.catalog_entries.length" severity="info" />
              </div>
            </template>
          </Column>

          <Column field="lowest_price" :header="t('Best Price')" sortable>
            <template #body="{ data }">
              <span class="font-bold text-green-600 dark:text-green-400">
                {{ formatCurrency(String(data.lowest_price)) }}
              </span>
            </template>
          </Column>

          <Column :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body>
              <div class="flex justify-center">
                <Button
                  v-can="'catalog.create'"
                  v-tooltip.top="t('Add Vendor')"
                  icon="fa fa-plus"
                  text
                  rounded
                  size="large"
                  @click="goToVendors"
                />
              </div>
            </template>
          </Column>

          <template #expansion="{ data }">
            <VendorComparisonRow :entries="data.catalog_entries" :lowest-price="data.lowest_price" />
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>