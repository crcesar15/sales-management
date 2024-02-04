<template>
  <AppLayout>
    <ConfirmDialog />
    <Toast />
    <h1>Inventory</h1>
    <div class="card">
      <div class="card-body table-responsive">
        <DataTable
          :value="products"
          lazy
          :total-records="pagination.total"
          :rows="pagination.rows"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #header>
            <div class="grid">
              <div class="col-12 md:col-10">
                <input-text
                  v-model="pagination.filter"
                  type="text"
                  placeholder="Search"
                  class="w-full"
                />
              </div>
              <div class="col-12 md:col-2">
                <p-button
                  label="Product"
                  icon="fa fa-plus"
                  class="w-full"
                />
              </div>
            </div>
          </template>
          <Column
            field="identifier"
            header="# Serie"
            sortable
          />
          <Column
            field="name"
            header="Name"
            sortable
          />
          <Column
            field="description"
            header="Description"
          />
          <Column
            field="price"
            header="Price"
            sortable
          />
          <Column
            field="brand"
            header="Brand"
            sortable
          />
          <Column
            field="stock"
            header="Stock"
            sortable
          />
          <Column
            header="Actions"
            style="min-width: 178px;"
            header-class="flex justify-content-center"
          >
            <template
              #body="{ data }"
            >
              <span
                class="p-buttonset"
              >
                <p-button
                  icon="fa fa-eye"
                  text
                  size="sm"
                  @click="viewProduct(data)"
                />
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
        </DataTable>
      </div>
    </div>
    <item-viewer
      :product="selectedProduct"
      :show-dialog="viewerToggle"
      @clearSelection="selectedProduct = {}; viewerToggle = false;"
    />
  </AppLayout>
</template>

<script>

import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Toast from "primevue/toast";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import ConfirmDialog from "primevue/confirmdialog";
import AppLayout from "../../layouts/admin.vue";
import ItemViewer from "./ItemViewer.vue";

export default {
  components: {
    AppLayout,
    DataTable,
    Column,
    PButton,
    InputText,
    Toast,
    ConfirmDialog,
    ItemViewer,
  },
  data() {
    return {
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
  },
  mounted() {
    this.fetchProducts();
  },
  methods: {
    fetchProducts() {
      this.loading = true;
      let url = `/products?&per_page=${this.pagination.rows}&page=${this.pagination.page}&order_by=${this.pagination.sortField}`;

      url += "&includes=media";

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
          this.products = response.data.data;
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
      this.$inertia.visit(`/products/${productId}/edit`);
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
          axios.delete(`/products/${id}`)
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
</style>
