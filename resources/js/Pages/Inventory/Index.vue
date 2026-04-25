<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Button,
  InputText,
  IconField,
  InputIcon,
  SelectButton,
  Tag,
  Badge,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { InventoryVariantListResponse, InventoryFilters } from "@/Types/inventory-variant-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

defineOptions({ layout: AppLayout });
const props = defineProps<{
  variants: InventoryVariantListResponse;
  filters: InventoryFilters;
}>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "all");
const sortField = ref("product_name");
const sortOrder = ref(1);

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

let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, (val) => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    router.visit(route("inventory.variants"), {
      data: { filter: val, status: status.value, order_by: sortField.value, order_direction: sortOrder.value === -1 ? "desc" : "asc" },
      preserveState: true,
      replace: true,
    });
  }, 300);
});

watch(status, () => {
  router.visit(route("inventory.variants"), {
    data: {
      status: status.value,
      filter: filter.value,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
    },
    preserveState: true,
    replace: true,
  });
});

const onPage = (event: DataTablePageEvent) => {
  router.visit(route("inventory.variants"), {
    data: {
      page: event.page + 1,
      per_page: event.rows,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      filter: filter.value,
      status: status.value,
    },
    preserveState: true,
    replace: true,
  });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "created_at";
  sortOrder.value = event.sortOrder ?? -1;
  router.visit(route("inventory.variants"), {
    data: {
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      filter: filter.value,
      status: status.value,
    },
    preserveState: true,
    replace: true,
  });
};
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
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
              <div class="md:col-span-6 col-span-12 flex md:justify-start justify-center">
                <SelectButton v-model="status" :allow-empty="false" :options="statusOptions" option-label="label" option-value="value" />
              </div>
              <div
                class="flex xl:col-span-3 xl:col-start-10 lg:col-span-4 lg:col-start-9 md:col-span-6 md:col-start-7 col-span-12 md:justify-end justify-center"
              >
                <IconField icon-position="left" class="w-full">
                  <InputIcon class="fa fa-search" />
                  <InputText v-model="filter" :placeholder="t('Search')" class="w-full" />
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

          <Column field="stock" :header="t('Stock')" sortable>
            <template #body="{ data }">
              {{ data.stock }}
            </template>
          </Column>

          <Column field="status" :header="t('Status')" sortable>
            <template #body="{ data }">
              <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <Column :header="t('Actions')">
            <template #body="{ data }">
              <Button
                v-tooltip.top="t('Manage')"
                icon="fa-solid fa-list"
                text
                rounded
                size="small"
                :aria-label="t('Manage')"
                @click="router.visit(route('inventory.variants.show', { product: data.product_id, variant: data.id }))"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
