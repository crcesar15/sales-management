<template>
  <div>
    <h1>Products</h1>
    <table class="table">
      <thead>
        <tr>
          <th scope="col">
            # Serie
          </th>
          <th scope="col">
            Name
          </th>
          <th scope="col">
            Description
          </th>
          <th scope="col">
            Price
          </th>
          <th scope="col">
            Brand
          </th>
          <th scope="col">
            Stock
          </th>
          <th scope="col">
            Actions
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="product in products"
          :key="product.id"
        >
          <td>{{ product.identifier }}</td>
          <td>{{ product.name }}</td>
          <td>{{ product.description }}</td>
          <td>{{ product.price }}</td>
          <td>{{ product.brand }}</td>
          <td>{{ product.stock }}</td>
          <td>
            <div
              class="btn-group btn-group-sm"
              role="group"
              aria-label="Basic example"
            >
              <button
                type="button"
                class="btn btn-primary"
              >
                Show
              </button>
              <button
                type="button"
                class="btn btn-primary"
              >
                Edit
              </button>
              <button
                type="button"
                class="btn btn-primary"
              >
                Delete
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
    <div class="row">
      <div class="col-12 col-sm-6 offset-sm-6">
        <nav aria-label="product navigation">
          <ul class="pagination justify-content-md-end justify-content-center">
            <li
              class="page-item"
              @click="fetchProducts(current_page - 1)"
            >
              <a
                class="page-link"
                :class="{ disabled: pagination.current_page === 1 }"
                aria-label="Previous"
              >
                <span aria-hidden="true">&laquo;</span>
              </a>
            </li>
            <template
              v-if="pagination.total_pages < 10"
            >
              <li
                v-for="(current_page, index) in pagination.total_pages"
                :key="index"
                class="page-item"
                :class="{ active: pagination.current_page - 1 === index }"
                style="cursor: pointer"
                @click="fetchProducts(index + 1)"
              >
                <a
                  class="page-link"
                >{{ current_page }}</a>
              </li>
            </template>
            <template
              v-if="pagination.total_pages >= 10"
            >
              <li
                v-for="index in pagination.last_page"
                :key="index"
                class="page-item"
                :class="{ active: pagination.current_page === index }"
                @click="fetchProducts(index)"
              >
                <a
                  class="page-link"
                >{{ index }}</a>
              </li>
            </template>
            <li
              class="page-item"
              style="cursor: pointer;"
              @click="fetchProducts(pagination.current_page + 1)"
            >
              <a
                class="page-link"
                aria-label="Next"
              >
                <span aria-hidden="true">&raquo;</span>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>

export default {
  data() {
    return {
      products: [],
      pagination: {
        current_page: 1,
        last_page: 1,
        total_pages: 1,
      },
    };
  },
  mounted() {
    this.fetchProducts();
  },
  methods: {
    fetchProducts(page = false) {
      const url = page ? `/products?page=${page}` : "/products";

      axios.get(url)
        .then((response) => {
          this.products = response.data.data;
          this.pagination = {
            current_page: response.data.meta.current_page,
            last_page: response.data.meta.last_page,
            total_pages: response.data.meta.total_pages,
          };
          console.log(this.pagination);
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};

</script>
