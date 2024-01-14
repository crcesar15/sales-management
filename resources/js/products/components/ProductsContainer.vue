<template>
  <div>
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
          :pt="tableStyle"
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
                  label="Add Product"
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
                  @click="showProduct(data)"
                />
                <p-button
                  icon="fa fa-edit"
                  text
                  size="sm"
                  @click="editProduct(data)"
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
    <product-viewer
      :product="selectedProduct"
      @clearSelection="selectedProduct = {}"
    />
  </div>
</template>

<script>
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Toast from "primevue/toast";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import ProductViewer from "./ProductViewer.vue";

export default {
  components: {
    DataTable,
    Column,
    PButton,
    InputText,
    Toast,
    ProductViewer,
  },
  data() {
    return {
      tableStyle: {
        table: {
          class: "table mt-3",
          style: "border: 1px solid #dee2e6;",
        },
        thead: {
          class: "table-light",
        },
      },
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
      let url = `/products?per_page=${this.pagination.rows}&page=${this.pagination.page}&order_by=${this.pagination.sortField}`;

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
    deleteProduct(id) {
      axios.delete(`/products/${id}`)
        .then(() => {
          this.fetchProducts();
        })
        .catch((error) => {
          console.log(error);
        });
    },
    showProduct(product) {
      this.selectedProduct = product;
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
