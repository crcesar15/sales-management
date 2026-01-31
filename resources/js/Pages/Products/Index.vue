<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Products") }}
      </h2>
      <Button
        :label="t('Add Product')"
        v-can="'product.create'"
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
          v-model:expandedRows="expandedRows"
          :value="products"
          resizable-columns
          lazy
          :page-link-size="3"
          :total-records="pagination.total"
          :rows="pagination.perPage"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ t('No products registered yet') }}
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div class="xl:col-span-3 lg:col-span-4 md:col-span-6 col-span-12 flex md:justify-start justify-center">
                <SelectButton
                  v-model="status"
                  :allow-empty="false"
                  :options="[{
                    label: t('Active'),
                    value: 'active',
                  }, {
                    label: t('Inactive'),
                    value: 'inactive',
                  }, {
                    label: t('Archived'),
                    value: 'archived',
                  }, {
                    label: t('All'),
                    value: 'all',
                  }]"
                  option-label="label"
                  option-value="value"
                  aria-labelledby="basic"
                />
              </div>
              <div
                class="
                  flex
                  xl:col-span-3
                  xl:col-start-10
                  lg:col-span-4
                  lg:col-start-9
                  md:col-span-6
                  md:col-start-7
                  col-span-12
                  md:justify-end
                  justify-center
                "
              >
                <IconField
                  icon-position="left"
                  class="w-full"
                >
                  <InputIcon class="fa fa-search" />
                  <InputText
                    v-model="pagination.filter"
                    :placeholder="t('Search')"
                    class="w-full"
                  />
                </IconField>
              </div>
            </div>
          </template>
          <Column
            field="name"
            :header="t('Product')"
            sortable
          >
            <template #body="{ data }">
              <span
                style="cursor: pointer;"
                class="text-900 font-medium hover:text-primary-500 transition-colors"
                @click="viewProduct(data)"
              >
                {{ data.name }}
              </span>
            </template>
          </Column>
          <Column
            field="media"
            :header="t('Image')"
            style="padding: 4px 12px; margin: 0px;"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="{ data }">
              <div class="flex justify-center">
                <img
                  v-if="data.variants[0].media.length"
                  :src="data.variants[0].media[0].url"
                  alt="Product Image"
                  class="
                    rounded-xl
                    border-slate-300
                    dark:border-slate-700
                    shadow-md
                  "
                  style="height: 55px; width: 55px;"
                >
                <div
                  v-else
                  class="
                    bg-surface-50
                    dark:bg-surface-950
                    rounded-xl
                    justify-center
                    items-center
                    flex
                    border-slate-300
                    dark:border-slate-700
                    shadow-md
                  "
                  style="height: 55px; width: 55px;"
                >
                  <p style="font-size: 18px; font-weight: bold;">
                    {{ data.name.substring(0, 2).toUpperCase() }}
                  </p>
                </div>
              </div>
            </template>
          </Column>
          <Column
            field="status"
            :header="t('Status')"
            header-class="flex justify-center"
            class="flex justify-center"
          >
            <template #body="{ data }">
              <div
                style="height: 55px;"
                class="flex items-center"
              >
                <Tag
                  v-if="data.status === 'active'"
                  severity="success"
                  :value="t('Active')"
                />
                <Tag
                  v-else-if="data.status === 'inactive'"
                  severity="warn"
                  :value="t('Inactive')"
                />
                <Tag
                  v-else
                  severity="danger"
                  :value="t('Archived')"
                />
              </div>
            </template>
          </Column>
          <Column
            :header="t('Inventory')"
          >
            <template #body="{ data }">
              <div v-if="data.variants.length > 1">
                {{ t('variants stock', {stock: data.stock, counter: data.variants.length}) }}
              </div>
              <div v-else>
                {{ t('variant stock', {stock: data.stock}) }}
              </div>
            </template>
          </Column>
          <Column
            field="category"
            :header="t('Category')"
          >
            <template #body="{data}">
              <Tag
                v-for="category in data.categories"
                :key="category.id"
                severity="secondary"
                :value="category.name"
              />
            </template>
          </Column>
          <Column
            field="created_at"
            :header="t('Created At')"
          />
          <Column
            field="updated_at"
            :header="t('Updated At')"
          />
          <Column
            :header="t('Actions')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="{ data }">
              <span class="flex justify-center gap-2">
                <Button
                  v-tooltip.top="t('Edit')"
                  v-can="'product.edit'"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editProduct(data.id)"
                />
                <Button
                  v-if="data.status !== 'archived'"
                  v-can="'product.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
                  size="sm"
                  class="btn-danger"
                  @click="deleteProduct(data.id)"
                />
              </span>
            </template>
          </Column>
          <Column
            style="width: 5rem"
          >
            <template #body="slotProps">
              <i
                v-if="slotProps.data.variants.length > 1"
                v-tooltip.top="!expandedRows.includes(slotProps.data) ? t('Show variants') : t('Hide variants')"
                class="fa fa-fw fa-chevron-circle-down"
                :class="{
                  'fa-chevron-down': !expandedRows.includes(slotProps.data),
                  'fa-chevron-up': expandedRows.includes(slotProps.data),
                }"
                style="cursor: pointer;"
                @click="onExpandRow(slotProps.data.id)"
              />
            </template>
          </Column>
          <template #expansion="product">
            <div>
              <DataTable
                show-gridlines
                :value="product.data.variants"
              >
                <Column
                  field="name"
                  :header="t('Variant')"
                />
                <Column
                  field="media"
                  :header="t('Image')"
                  style="padding: 4px 12px; margin: 0px;"
                  :pt="{columnHeaderContent: 'justify-center'}"
                >
                  <template #body="{ data }">
                    <div class="flex justify-center">
                      <img
                        v-if="data.media.length"
                        :src="data.media[0].url"
                        alt="Product Image"
                        class="
                          rounded-xl
                          border-slate-300
                          dark:border-slate-700
                          shadow-md
                        "
                        style="height: 55px; width: 55px;"
                      >
                      <div
                        v-else
                        class="
                          bg-surface-50
                          dark:bg-surface-950
                          rounded-xl
                          justify-center
                          items-center
                          flex
                          border-slate-300
                          dark:border-slate-700
                          shadow-md
                        "
                        style="height: 55px; width: 55px;"
                      >
                        <p style="font-size:18px; font-weight: bold">
                          {{ data.name.substring(0, 2).toUpperCase() }}
                        </p>
                      </div>
                    </div>
                  </template>
                </Column>
                <Column
                  field="status"
                  :header="t('Status')"
                  header-class="flex justify-center"
                  class="flex justify-center"
                >
                  <template #body="{ data }">
                    <div
                      style="height: 55px;"
                      class="flex items-center"
                    >
                      <Tag
                        v-if="data.status === 'active'"
                        severity="success"
                        :value="t('Active')"
                      />
                      <Tag
                        v-else-if="data.status === 'inactive'"
                        severity="warn"
                        :value="t('Inactive')"
                      />
                      <Tag
                        v-else
                        severity="danger"
                        :value="t('Archived')"
                      />
                    </div>
                  </template>
                </Column>
                <Column
                  field="price"
                  :header="t('Price')"
                >
                  <template #body="{ data }">
                    <span>
                      {{ formatCurrency(data.price) }}
                    </span>
                  </template>
                </Column>
                <Column
                  field="stock"
                  :header="$t('Stock')"
                >
                  <template #body="{data}">
                    {{ t('variant stock', {stock: data.stock}) }}
                  </template>
                </Column>
              </DataTable>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>
    <item-viewer
      v-model:show-dialog="viewerToggle"
      :product="selectedProduct"
    />
  </div>
</template>

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
  DataTablePageEvent,
  DataTableSortEvent
} from "primevue"

import AppLayout from "@layouts/admin.vue";
import ItemViewer from "@pages/Products/List/ItemViewer.vue";

import { useProductClient } from "@composables/useProductClient";
import { useI18n } from "vue-i18n";
import { Product } from "@app-types/product-types";
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";

// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Layout
defineOptions({
  layout: AppLayout,
});

// List Products
const pagination = ref({
  total: 0,
  first: 0,
  page: 1,
  perPage: 10,
  sortField: "name",
  sortOrder: 1,
  filter: "",
});

const products = ref<Product[]>([]);
const expandedRows = ref<Product[]>([]);
const status = ref("active");

const {loading, fetchProductsApi} = useProductClient();
const { formatCurrency } = useCurrencyFormatter();

const fetchProducts = async () => {
  const params = new URLSearchParams();

  params.append("per_page", pagination.value.perPage.toString());
  params.append("page", pagination.value.page.toString());
  params.append("order_by", pagination.value.sortField);
  params.append("order_direction", pagination.value.sortOrder === -1 ? "desc" : "asc");
  params.append("include", "brand,categories,measurementUnit,variants.media");

  if (pagination.value.filter) {
    params.append("filter", pagination.value.filter);
  }

  if (status.value !== "all") {
    params.append("status", status.value);
  }

  try {
    const response = await fetchProductsApi(params.toString());
    products.value = response.data.data.map((product: Product) => {
      return {
        ...product,
        created_at: useDatetimeFormatter(product.created_at),
        updated_at: useDatetimeFormatter(product.updated_at),
      };
    });
    pagination.value.total = response.data.meta.total;
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error?.response?.data?.message || error),
      life: 3000,
    });
  }
};

// Pagination
const onPage = (event: DataTablePageEvent) => {
  pagination.value.page = event.page + 1;
  pagination.value.perPage = event.rows;
  fetchProducts();
}

const onSort = (event: DataTableSortEvent) => {
  pagination.value.sortField = typeof event.sortField === "string" ? event.sortField : "name";
  pagination.value.sortOrder = event.sortOrder ?? 0;
  fetchProducts();
}

watch(
  () => pagination.value.filter,
  () => {
    pagination.value.page = 1;
    fetchProducts();
  },
  {
    immediate: true,
  }
);

watch(status, () => {
  expandedRows.value = [];
  pagination.value.page = 1;
  fetchProducts();
});

//Viewer
const viewerToggle = ref(false);
const selectedProduct = ref<Product>({
  id: 0,
  name: '',
  description: '',
  created_at: '',
  updated_at: '',
  status: 'active',
});

const onExpandRow = (id: number) => {
  // get product by id
  const product: Product | undefined = products.value.find((item: Product) => item.id === id);

  if (typeof product !== "undefined") {
    // check if the product is already expanded
    if (expandedRows.value.includes(product)) {
      // remove the product from the expanded rows
      expandedRows.value = expandedRows.value.filter((row) => row !== product);
    } else {
      // add the product to the expanded rows
      expandedRows.value.push(product);
    }
  }
}

const viewProduct = (product: Product) => {
  viewerToggle.value = true;
  selectedProduct.value = product;
};

// Edit Product
const editProduct = (id: number) => {
  router.visit(route("products.edit", { id }));
};

// Delete Product
const deleteProduct = (id: number) => {
  const { destroyProductApi } = useProductClient();

  confirm.require({
    message: t("Are you sure you want to delete this product?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: async () => {
      try {
        await destroyProductApi(id);

        fetchProducts();

        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("Product deleted successfully"),
          life: 3000,
        });
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: error?.response?.data?.message ?? error,
          life: 3000,
        });
      }
    },
  });
}
</script>

<style>
.sortable-column [data-pc-section="sort"] {
  padding-left: 0.2rem;
}

.sortable-column [data-pc-section="headercontent"] {
  display: flex;
  align-items: center;
}
.sortable-column th:hover {
  cursor: pointer;
  background-color: #ccc;
}
.p-datatable .p-datatable-tbody>tr.no-expander>td .p-row-toggler {
  display: none;
}
</style>
