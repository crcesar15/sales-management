<template>
  <div class="grid">
    <div
      v-for="product in products"
      :key="product.id"
      class="sm:col-12 md:col-6 lg:col-4 xl:col-2"
    >
      <ProductItem :product="product" />
    </div>
  </div>
</template>

<script>
import ProductItem from "./ProductItem.vue";

export default {
  components: {
    ProductItem,
  },
  data() {
    return {
      products: [],
    };
  },
  mounted() {
    this.getProducts();
  },
  methods: {
    getProducts() {
      axios.get("/products?includes=media&per_page=10&page=1")
        .then((response) => {
          this.products = response.data.data;
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};
</script>
