<template>
  <AppLayout>
    <div class="flex">
      <div class="col-2">
        <h2 class="text-2xl font-bold">
          Products
        </h2>
      </div>
      <div class="col-10 flex align-items-center justify-content-end">
        <p-button
          label="Add Product"
          class="ml-2"
          @click="$inertia.visit(route('products.create'))"
        />
      </div>
    </div>
    <ConfirmDialog />
    <Toast />
    <Card>
      <template #content>
        <DataTable
          v-model:expandedRows="expandedRows"
          :value="products"
          lazy
          :page-link-size="3"
          :total-records="pagination.total"
          :rows="pagination.rows"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="name"
          :sort-order="1"
          :row-class="rowClass"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            No products found.
          </template>
          <template #header>
            <div class="grid">
              <div class="xl:col-6 lg:col-6 md:col-6 col-12 flex md:justify-content-start justify-content-center">
                <SelectButton
                  v-model="status"
                  :options="[{
                    label: 'All',
                    value: 'all',
                  }, {
                    label: 'Active',
                    value: 'active',
                  }, {
                    label: 'Inactive',
                    value: 'inactive',
                  }, {
                    label: 'Archived',
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
                  xl:col-6
                  lg:col-6
                  md:col-6
                  col-12
                  md:justify-content-end
                  justify-content-center
                "
              >
                <IconField icon-position="left">
                  <InputIcon class="fa fa-search" />
                  <InputText
                    v-model="pagination.filter"
                    placeholder="Search"
                  />
                </IconField>
              </div>
            </div>
          </template>
          <Column
            field="name"
            header="Product"
            sortable
            header-class="surface-100"
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
            header="Image"
            style="padding: 4px 12px; margin: 0px;"
            header-class="surface-100"
          >
            <template #body="{ data }">
              <img
                v-if="data.media.length"
                :src="data.media[0].url"
                alt="Product Image"
                class="border-round"
                style="height: 55px; width: 55px;"
              >
              <div
                v-else
                class="bg-gray-200 border-round justify-content-center align-items-center flex"
                style="height: 55px; width: 55px;"
              >
                <p style="font-size: 18px; font-weight: bold;">
                  {{ data.name.substring(0, 2).toUpperCase() }}
                </p>
              </div>
            </template>
          </Column>
          <Column
            field="status"
            header="Status"
            header-class="flex justify-content-center surface-100"
            class="flex justify-content-center"
          >
            <template #body="{ data }">
              <div
                style="height: 55px;"
                class="flex align-items-center"
              >
                <span
                  v-if="data.status === 'active'"
                  class="p-tag p-tag-success"
                >
                  Active
                </span>
                <span
                  v-else-if="data.status === 'inactive'"
                  class="p-tag p-tag-warning"
                >
                  Inactive
                </span>
                <span
                  v-else
                  class="p-tag p-tag-danger"
                >
                  Archived
                </span>
              </div>
            </template>
          </Column>
          <Column
            field="price"
            header="Price"
            header-class="surface-100"
          >
            <template #body="{ data }">
              <span>
                Bs. {{ data.price }}
              </span>
            </template>
          </Column>
          <Column
            field="stock"
            header="Stock"
            header-class="surface-100"
          />
          <Column
            field="brand.name"
            header="Brand"
            header-class="surface-100"
          />
          <Column
            field="category"
            header="Categories"
            header-class="surface-100"
          />
          <Column
            header="Actions"
            header-class="flex justify-content-center surface-100"
          >
            <template #body="{ data }">
              <span class="p-buttonset flex justify-content-center">
                <p-button
                  icon="fa fa-edit"
                  text
                  size="sm"
                  @click="editProduct(data.id)"
                />
                <p-button
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
            expander
            style="width: 5rem"
            header-class="surface-100"
          />
          <template #expansion="product">
            <div>
              <DataTable
                show-gridlines
                :value="product.data.variants"
              >
                <Column
                  field="name"
                  header="Product Variant"
                  header-class="surface-100"
                />
                <Column
                  field="media"
                  header="Image"
                  style="padding: 4px 12px; margin: 0px;"
                  header-class="surface-100"
                >
                  <template #body="{ data }">
                    <img
                      v-if="data.media.length"
                      :src="data.media[0].url"
                      alt="Product Image"
                      class="border-round"
                      style="height: 55px; width: 55px;"
                    >
                    <div
                      v-else
                      class="bg-gray-200 border-round flex justify-content-center align-items-center"
                      style="height: 55px; width: 55px;"
                    >
                      <p style="font-size:18px; font-weight: bold">
                        {{ data.name.substring(0, 2).toUpperCase() }}
                      </p>
                    </div>
                  </template>
                </Column>
                <Column
                  field="status"
                  header="Status"
                  header-class="flex justify-content-center surface-100"
                  class="flex justify-content-center"
                >
                  <template #body="{ data }">
                    <div
                      style="height: 55px;"
                      class="flex align-items-center"
                    >
                      <span
                        v-if="data.status === 'active'"
                        class="p-tag p-tag-success"
                      >
                        Active
                      </span>
                      <span
                        v-else-if="data.status === 'inactive'"
                        class="p-tag p-tag-warning"
                      >
                        Inactive
                      </span>
                      <span
                        v-else
                        class="p-tag p-tag-danger"
                      >
                        Archived
                      </span>
                    </div>
                  </template>
                </Column>
                <Column
                  field="price"
                  header="Price"
                  header-class="surface-100"
                >
                  <template #body="{ data }">
                    <span>
                      Bs. {{ data.price }}
                    </span>
                  </template>
                </Column>
                <Column
                  field="stock"
                  header="Stock"
                  header-class="surface-100"
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
  </AppLayout>
</template>

<script>

import DataTable from "primevue/datatable";
import Card from "primevue/card";
import Column from "primevue/column";
import Toast from "primevue/toast";
import PButton from "primevue/button";
import ButtonGroup from "primevue/buttongroup";
import InputText from "primevue/inputtext";
import ConfirmDialog from "primevue/confirmdialog";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import SelectButton from "primevue/selectbutton";
import AppLayout from "../../layouts/admin.vue";
import ItemViewer from "./ItemViewer.vue";

export default {
  components: {
    AppLayout,
    DataTable,
    Column,
    PButton,
    InputText,
    IconField,
    InputIcon,
    ButtonGroup,
    Toast,
    ConfirmDialog,
    ItemViewer,
    Card,
    SelectButton,
  },
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
    rowClass(rowData) {
      return rowData.variants.length > 1 ? "" : "no-expander";
    },
    fetchProducts() {
      this.loading = true;
      let url = `/products?per_page=${this.pagination.rows}
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
      axios.put(`/products/${id}`, product)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "Product updated successfully",
            life: 3000,
          });
          this.fetchProducts();
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response.data.message,
            life: 3000,
          });
        });
    },
    deleteProduct(id) {
      this.$confirm.require({
        message: "Are you sure you want to delete this product?",
        header: "Delete Confirmation",
        icon: "fas fa-exclamation-triangle",
        accept: () => {
          axios.delete(route("api.products.destroy", { id }))
            .then(() => {
              this.fetchProducts();
              this.$toast.add({
                severity: "success",
                summary: "Success",
                detail: "Product deleted successfully",
                life: 3000,
              });
            })
            .catch((error) => {
              this.$toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message,
                life: 3000,
              });
            });
        },
        reject: () => {
          this.$toast.add({
            severity: "info",
            summary: "Info",
            detail: "Product deletion canceled",
            life: 3000,
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
