<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Button,
  InputText,
  ConfirmDialog,
  IconField,
  InputIcon,
  SelectButton,
  Tag,
  useToast,
  useConfirm,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import ItemViewer from "@pages/Products/List/ItemViewer.vue";
import { useI18n } from "vue-i18n";
import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { ProductListResponse, ProductFilters } from "@/Types/product-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

defineOptions({ layout: AppLayout });
const props = defineProps<{
  products: {
    data: ProductListResponse[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
  filters: ProductFilters;
}>();
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

// Local filter/sort state
const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "active");
const sortField = ref(props.filters.order_by ?? "name");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);

const statusOptions = computed(() => [
  { label: t("Active"), value: "active" },
  { label: t("Inactive"), value: "inactive" },
  { label: t("Archived"), value: "archived" },
]);

// Formatted rows
const products = computed(() => props.products.data);
const expandedRows = ref<ProductListResponse[]>([]);

// Debounced filter watch
let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, (val) => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    router.visit(route("products"), {
      data: {
        filter: val,
        status: status.value,
        order_by: sortField.value,
        order_direction: sortOrder.value === -1 ? "desc" : "asc",
      },
      preserveState: true,
      replace: true,
    });
  }, 300);
});

watch(status, (val) => {
  expandedRows.value = [];
  router.visit(route("products"), {
    data: {
      status: val,
      filter: filter.value,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
    },
    preserveState: true,
    replace: true,
  });
});

// Pagination & sorting
const onPage = (event: DataTablePageEvent) => {
  router.visit(route("products"), {
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
  sortField.value = typeof event.sortField === "string" ? event.sortField : "name";
  sortOrder.value = event.sortOrder ?? 1;
  router.visit(route("products"), {
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

// Expandable rows
const isExpanded = (id: number) => expandedRows.value.some((p) => p.id === id);

const toggleExpand = (product: ProductListResponse) => {
  if (isExpanded(product.id)) {
    expandedRows.value = expandedRows.value.filter((p) => p.id !== product.id);
  } else {
    expandedRows.value.push(product);
  }
};

// Viewer
const viewerToggle = ref(false);
const selectedProduct = ref<ProductListResponse | null>(null);

const viewProduct = (product: ProductListResponse) => {
  selectedProduct.value = product;
  viewerToggle.value = true;
};

// Edit
const editProduct = (id: number) => {
  router.visit(route("products.edit", { product: id }));
};

// Delete
const deleteProduct = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to delete this product?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("products.destroy", { product: id }), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Product deleted successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not delete product"), life: 3000 });
        },
      });
    },
  });
};

// Restore
const restoreProduct = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to restore this product?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Restore"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.put(route("products.restore", { id }), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Product restored successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not restore product"), life: 3000 });
        },
      });
    },
  });
};
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Products") }}
      </h2>
      <Button
        v-can="'product.create'"
        :label="t('Add Product')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="router.visit(route('products.create'))"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          v-model:expanded-rows="expandedRows"
          :value="products"
          resizable-columns
          lazy
          :total-records="props.products.meta.total"
          :rows="props.products.meta.per_page"
          :first="(props.products.meta.current_page - 1) * props.products.meta.per_page"
          paginator
          sort-field="name"
          :sort-order="1"
          :page-link-size="3"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ t("No products registered yet") }}
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
          <Column field="media" :header="t('Image')" style="width: 60px">
            <template #body="{ data }">
              <div class="flex justify-center">
                <img
                  v-if="data.media.length"
                  :src="data.media[0].thumb_url"
                  alt="Product Image"
                  class="rounded-xl border-slate-300 dark:border-slate-700 shadow-md"
                  style="height: 45px; width: 45px; object-fit: cover"
                />
                <div
                  v-else
                  class="bg-surface-50 dark:bg-surface-950 rounded-xl justify-center items-center flex border-slate-300 dark:border-slate-700 shadow-md"
                  style="height: 45px; width: 45px"
                >
                  <p style="font-size: 14px; font-weight: bold">
                    {{ data.name.substring(0, 2).toUpperCase() }}
                  </p>
                </div>
              </div>
            </template>
          </Column>
          <Column field="name" :header="t('Product')" sortable>
            <template #body="{ data }">
              <span class="text-900 font-medium">
                {{ data.name }}
              </span>
            </template>
          </Column>
          <Column field="status" :header="t('Status')" sortable>
            <template #body="{ data }">
              <div style="height: 45px" class="flex items-center">
                <Tag v-if="data.status === 'active'" severity="success" :value="t('Active')" />
                <Tag v-else-if="data.status === 'inactive'" severity="warn" :value="t('Inactive')" />
                <Tag v-else severity="danger" :value="t('Archived')" />
              </div>
            </template>
          </Column>
          <Column field="price_min" :header="t('Price')" sortable>
            <template #body="{ data }">
              <div style="height: 45px" class="flex items-center">
                <span v-if="data.price_min !== data.price_max">
                  {{ formatCurrency(String(data.price_min)) }} – {{ formatCurrency(String(data.price_max)) }}
                </span>
                <span v-else>
                  {{ formatCurrency(String(data.price_min ?? 0)) }}
                </span>
              </div>
            </template>
          </Column>
          <Column :header="t('Inventory')">
            <template #body="{ data }">
              <div style="height: 45px" class="flex items-center">
                <span v-if="data.variants_count > 1">
                  {{ t("variants stock", { stock: data.stock, counter: data.variants_count }) }}
                </span>
                <span v-else>
                  {{ t("variant stock", { stock: data.stock }) }}
                </span>
              </div>
            </template>
          </Column>
          <Column :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-start gap-2">
                <Button v-tooltip.top="t('View')" icon="fa fa-eye" text rounded size="small" @click="viewProduct(data)" />
                <Button
                  v-show="data.status !== 'archived'"
                  v-can="'product.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  size="small"
                  @click="editProduct(data.id)"
                />
                <Button
                  v-show="data.status === 'archived'"
                  v-can="'product.edit'"
                  v-tooltip.top="t('Restore')"
                  icon="fa fa-trash-arrow-up"
                  text
                  rounded
                  size="small"
                  @click="restoreProduct(data.id)"
                />
                <Button
                  v-show="data.status !== 'archived'"
                  v-can="'product.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  size="small"
                  @click="deleteProduct(data.id)"
                />
                <Button
                  v-if="data.variants_count > 1"
                  v-tooltip.top="isExpanded(data.id) ? t('Hide variants') : t('Show variants')"
                  :icon="isExpanded(data.id) ? 'fa fa-chevron-up' : 'fa fa-chevron-down'"
                  text
                  rounded
                  size="small"
                  @click="toggleExpand(data)"
                />
              </div>
            </template>
          </Column>
          <template #expansion="{ data }">
            <DataTable :value="data.variants" show-gridlines size="small">
              <Column field="identifier" :header="t('Identifier')" style="width: 140px">
                <template #body="{ data: variant }">
                  <span class="text-muted-color">{{ variant.identifier ?? "—" }}</span>
                </template>
              </Column>
              <Column field="name" :header="t('Variant')">
                <template #body="{ data: variant }">
                  <span v-if="variant.name" class="font-medium">{{ variant.name }}</span>
                  <Tag v-else :value="t('Default')" severity="secondary" />
                </template>
              </Column>
              <Column field="status" :header="t('Status')">
                <template #body="{ data: variant }">
                  <Tag v-if="variant.status === 'active'" severity="success" :value="t('Active')" />
                  <Tag v-else-if="variant.status === 'inactive'" severity="warn" :value="t('Inactive')" />
                  <Tag v-else severity="danger" :value="t('Archived')" />
                </template>
              </Column>
              <Column field="price" :header="t('Price')">
                <template #body="{ data: variant }">
                  {{ formatCurrency(String(variant.price)) }}
                </template>
              </Column>
              <Column field="stock" :header="t('Stock')">
                <template #body="{ data: variant }">
                  {{ t("variant stock", { stock: variant.stock }) }}
                </template>
              </Column>
            </DataTable>
          </template>
        </DataTable>
      </template>
    </Card>
    <ItemViewer v-model:show-dialog="viewerToggle" :product="selectedProduct" />
  </div>
</template>
