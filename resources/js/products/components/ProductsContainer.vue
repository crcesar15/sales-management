<template>
  <div>
    <h1>Products</h1>
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
            <div class="row">
              <div class="col-12 col-md-9">
                <input
                  v-model="pagination.filter"
                  type="text"
                  class="form-control"
                  placeholder="Search"
                >
              </div>
              <div class="col-12 col-md-3">
                <Button
                  label="Add Product"
                  icon="fa fa-plus"
                  class="btn btn-primary d-block w-100"
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
          >
            <template
              #body="{ data }"
            >
              <div
                class="btn-group"
                role="group"
              >
                <button
                  type="button"
                  class="btn btn-link btn-sm"
                  @click="showProduct(data)"
                >
                  <i class="fa-solid fa-eye fa-xl" />
                </button>
                <button
                  type="button"
                  class="btn btn-link btn-sm"
                  @click="editProduct(data)"
                >
                  <i class="fa-solid fa-edit fa-xl" />
                </button>
                <button
                  type="button"
                  class="btn btn-link btn-sm"
                  @click="deleteProduct(data.id)"
                >
                  <i class="fa-solid fa-trash fa-xl" />
                </button>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>
  </div>
</template>

<script>
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";

export default {
  components: {
    DataTable,
    Column,
    Button,
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
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchProducts();
    },
    onSort(event) {
      this.pagination.page = event.page + 1;
      this.pagination.per_page = event.rows;
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
