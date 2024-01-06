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
  </div>
</template>

<script>

export default {
  data() {
    return {
      products: [],
    };
  },
  mounted() {
    this.fetchProducts();
  },
  methods: {
    fetchProducts() {
      axios.get("products")
        .then((response) => {
          console.log(response.data.data);
          this.products = response.data.data;
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};

</script>
