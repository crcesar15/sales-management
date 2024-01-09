<template>
  <div style="padding: 16px !important;">
    <q-table
      ref="tableRef"
      v-model:pagination="pagination"
      title="Products"
      :rows="products"
      :columns="columns"
      row-key="id"
      :loading="loading"
      :filter="filter"
      :rows-per-page-options="[10, 20, 50]"
      @request="onRequest"
    >
      <template #top-right>
        <q-input
          v-model="filter"
          borderless
          dense
          debounce="300"
          placeholder="Search"
        >
          <template #append>
            <q-icon name="fa fa-search" />
          </template>
        </q-input>
      </template>
      <!--<template #header>
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
                  icon="pi pi-plus"
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
          </Column>-->
    </q-table>
  </div>
</template>

<script>

export default {
  data() {
    return {
      products: [],
      pagination: {
        sortBy: "name",
        descending: false,
        page: 1,
        rowsPerPage: 10,
        rowsNumber: 0,
      },
      filter: null,
      loading: false,
      columns: [
        {
          name: "identifier", label: "# Serie", field: "identifier", sortable: true,
        },
        {
          name: "name", label: "Name", field: "name", sortable: true,
        },
        {
          name: "description", label: "Description", field: "description",
        },
        {
          name: "price", label: "Price", field: "price", sortable: true,
        },
        {
          name: "brand", label: "Brand", field: "brand", sortable: true,
        },
        {
          name: "stock", label: "Stock", field: "stock", sortable: true,
        },
        {
          name: "actions", label: "Actions", field: "actions",
        },
      ],
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
      let url = `/products?per_page=${this.pagination.rowsPerPage}&page=${this.pagination.page}`;

      if (this.pagination.sortBy) {
        url += `&order_by=${this.pagination.sortBy}`;
      }

      if (!this.pagination.descending) {
        url += "&order_direction=asc";
      } else {
        url += "&order_direction=desc";
      }

      if (this.filter) {
        url += `&filter=${this.filter}`;
      }

      axios.get(url)
        .then((response) => {
          this.products = response.data.data;
          this.pagination.rowsNumber = response.data.meta.total;
          this.loading = false;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    onRequest(requestProp) {
      this.pagination.descending = requestProp.pagination.descending;
      this.filter = requestProp.filter;
      this.pagination.page = requestProp.pagination.page;
      this.pagination.rowsNumber = requestProp.pagination.rowsNumber;
      this.pagination.rowsPerPage = requestProp.pagination.rowsPerPage;
      this.pagination.sortBy = requestProp.pagination.sortBy;

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
