<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Products") }}
      </h2>
      <p-button
        :label="$t('Add Product')"
        style="text-transform: uppercase"
        icon="fa fa-add"
        class="ml-2"
        @click="$inertia.visit(route('products.create'))"
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
          :rows="pagination.rows"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="name"
          :sort-order="1"
          table-class="border-surface border"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ $t('No products registered yet') }}
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div class="xl:col-span-3 lg:col-span-4 md:col-span-6 col-span-12 flex md:justify-start justify-center">
                <SelectButton
                  v-model="status"
                  :allow-empty="false"
                  :options="[{
                    label: $t('All'),
                    value: 'all',
                  }, {
                    label: $t('Active'),
                    value: 'active',
                  }, {
                    label: $t('Inactive'),
                    value: 'inactive',
                  }, {
                    label: $t('Archived'),
                    value: 'archived',
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
                    :placeholder="$t('Search')"
                    class="w-full"
                  />
                </IconField>
              </div>
            </div>
          </template>
          <Column
            field="name"
            :header="$t('Product')"
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
            :header="$t('Image')"
            style="padding: 4px 12px; margin: 0px;"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="{ data }">
              <div class="flex justify-center">
                <img
                  v-if="data.media.length"
                  :src="data.media[0].url"
                  alt="Product Image"
                  class="border-round"
                  style="height: 55px; width: 55px;"
                >
                <div
                  v-else
                  class="bg-surface-50 dark:bg-surface-950 rounded-border justify-center items-center flex"
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
            :header="$t('Status')"
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
                  :value="$t('Active')"
                />
                <Tag
                  v-else-if="data.status === 'inactive'"
                  severity="warn"
                  :value="$t('Inactive')"
                />
                <Tag
                  v-else
                  severity="danger"
                  :value="$t('Archived')"
                />
              </div>
            </template>
          </Column>
          <Column
            field="price"
            :header="$t('Price')"
          >
            <template #body="{ data }">
              <span>
                Bs. {{ data.price }}
              </span>
            </template>
          </Column>
          <Column
            field="stock"
            :header="$t('Stock')"
          />
          <Column
            field="brand.name"
            :header="$t('Brand')"
          />
          <Column
            field="category"
            :header="$t('Category')"
          />
          <Column
            :header="$t('Actions')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="{ data }">
              <span class="p-buttonset flex justify-center">
                <p-button
                  v-tooltip.top="$t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="sm"
                  @click="editProduct(data.id)"
                />
                <p-button
                  v-tooltip.top="$t('Delete')"
                  icon="fa fa-trash"
                  text
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
                v-tooltip.top="!expandedRows.includes(slotProps.data) ? $t('Show variants') : $t('Hide variants')"
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
                  :header="$t('Variant')"
                />
                <Column
                  field="media"
                  :header="$t('Image')"
                  style="padding: 4px 12px; margin: 0px;"
                  :pt="{columnHeaderContent: 'justify-center'}"
                >
                  <template #body="{ data }">
                    <div class="flex justify-center">
                      <img
                        v-if="data.media.length"
                        :src="data.media[0].url"
                        alt="Product Image"
                        class="rounded-border"
                        style="height: 55px; width: 55px;"
                      >
                      <div
                        v-else
                        class="bg-gray-200 rounded-border flex justify-center items-center"
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
                  :header="$t('Status')"
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
                        :value="$t('Active')"
                      />
                      <Tag
                        v-else-if="data.status === 'inactive'"
                        severity="warn"
                        :value="$t('Inactive')"
                      />
                      <Tag
                        v-else
                        severity="danger"
                        :value="$t('Archived')"
                      />
                    </div>
                  </template>
                </Column>
                <Column
                  field="price"
                  :header="$t('Price')"
                >
                  <template #body="{ data }">
                    <span>
                      Bs. {{ data.price }}
                    </span>
                  </template>
                </Column>
                <Column
                  field="stock"
                  :header="$t('Stock')"
                />
              </DataTable>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>
    <item-viewer
      :product="selectedProduct"
      :show-dialog="viewerToggle"
      @clearSelection="selectedProduct = {}; viewerToggle = false;"
    />
  </div>
</template>

<script>

import DataTable from "primevue/datatable";
import Card from "primevue/card";
import Column from "primevue/column";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import ConfirmDialog from "primevue/confirmdialog";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import SelectButton from "primevue/selectbutton";
import Tag from "primevue/tag";
import i18n from "../../app";
import AppLayout from "../../layouts/admin.vue";
import ItemViewer from "./ItemViewer.vue";

export default {
  components: {
    DataTable,
    Column,
    PButton,
    InputText,
    IconField,
    InputIcon,
    ConfirmDialog,
    ItemViewer,
    Card,
    SelectButton,
    Tag,
  },
  layout: AppLayout,
  data() {
    return {
      status: "all",
      expandedRows: [],
      viewerToggle: false,
      editorToggle: false,
      products: [],
      pagination: {
        total: 0,
        first: 0,
        rows: 10,
        page: 1,
        sortField: "name",
        sortOrder: 1,
        filter: "",
      },
      loading: false,
      selectedProduct: {},
    };
  },
  watch: {
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetchProducts();
      },
    },
    status() {
      this.expandedRows = [];
      this.pagination.page = 1;
      this.fetchProducts();
    },
  },
  mounted() {
    this.fetchProducts();
  },
  methods: {
    onExpandRow(id) {
      // get product by id
      const product = this.products.find((item) => item.id === id);

      // check if the product is already expanded
      if (this.expandedRows.includes(product)) {
        // remove the product from the expanded rows
        this.expandedRows = this.expandedRows.filter((row) => row !== product);
      } else {
        // add the product to the expanded rows
        this.expandedRows.push(product);
      }
    },
    fetchProducts() {
      this.loading = true;
      let url = `${route("api.products")}?per_page=${this.pagination.rows}
        &page=${this.pagination.page}
        &order_by=${this.pagination.sortField}
        &status=${this.status}
      `;

      url += "&includes=media,brand,categories,measureUnit";

      if (this.pagination.sortOrder === -1) {
        url += "&order_direction=desc";
      } else {
        url += "&order_direction=asc";
      }

      if (this.pagination.filter) {
        url += `&filter=${this.pagination.filter}`;
      }

      axios.get(url)
        .then((response) => {
          this.products = response.data.data.map((product) => {
            const categories = product.categories ?? [];
            const categoryString = categories.map((category) => category.name).join(", ");
            return {
              ...product,
              category: categoryString,
            };
          });
          this.pagination.total = response.data.meta.total;
          this.loading = false;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    onPage(event) {
      this.pagination.page = event.page + 1;
      this.pagination.per_page = event.rows;
      this.fetchProducts();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchProducts();
    },
    viewProduct(product) {
      this.viewerToggle = true;
      this.selectedProduct = product;
    },
    editProduct(productId) {
      // inertia visit
      this.$inertia.visit(route("products.edit", { id: productId }));
    },
    updateProduct(id, product) {
      axios.put(`${route("api.products.store")}/${id}`, product)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: i18n.global.t("Success"),
            detail: i18n.global.t("Product updated successfully"),
            life: 3000,
          });
          this.fetchProducts();
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: i18n.global.t("Error"),
            detail: error.response.data.message,
            life: 3000,
          });
        });
    },
    deleteProduct(id) {
      this.$confirm.require({
        message: i18n.global.t("Are you sure you want to delete this product?"),
        header: i18n.global.t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: i18n.global.t("Cancel"),
        acceptLabel: i18n.global.t("Delete"),
        rejectClass: "p-button-secondary",
        accept: () => {
          axios.delete(route("api.products.destroy", { id }))
            .then(() => {
              this.fetchProducts();
              this.$toast.add({
                severity: "success",
                summary: i18n.global.t("Success"),
                detail: i18n.global.t("Product deleted successfully"),
                life: 3000,
              });
            })
            .catch((error) => {
              this.$toast.add({
                severity: "error",
                summary: i18n.global.t("Error"),
                detail: error.response.data.message,
                life: 3000,
              });
            });
        },
      });
    },
  },
};

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
