<template>
  <AppLayout>
    <div class="flex justify-content-around flex-wrap">
      <div
        v-for="product in products"
        :key="product.id"
        class="flex align-items-center justify-content-center"
      >
        <Card
          style="width: 20em; min"
          class="m-5"
          :pt="{root: ['border-rounded', 'shadow-4']}"
        >
          <template #header>
            <img
              v-if="product.media.length > 0"
              :src="product.media[0].url"
              :alt="product.name"
              class="w-full p-1"
            >
            <div
              v-else
              style="height: 20em;"
              class="flex flex-wrap justify-content-center align-items-center "
            >
              <p><i class="fa fa-image" /> No images yet</p>
            </div>
          </template>
          <template #title>
            {{ product.name }}
          </template>
          <template #subtitle>
            BOB. {{ product.price }}
          </template>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import Card from "primevue/card";
import ProductItem from "./ProductItem.vue";
import AppLayout from "../../layouts/admin.vue";

export default {
  components: {
    AppLayout,
    ProductItem,
    Card,
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
