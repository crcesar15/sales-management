<template>
  <div>
    <Card>
      <template #title>
        <h3>Item Editor</h3>
      </template>
      <template #content>
        <div class="grid">
          <div class="col-12 md:col-6 flex flex-column gap-2">
            <label for="identifier">Identifier</label>
            <InputText
              id="identifier"
              v-model="identifier"
            />
          </div>
          <div class="col-12 md:col-6 flex flex-column gap-2">
            <label for="name">Name</label>
            <InputText
              id="name"
              v-model="name"
            />
          </div>
          <div class="col-12 flex flex-column gap-2">
            <label for="description">Description</label>
            <Textarea
              id="description"
              v-model="description"
            />
          </div>
          <div class="col-12 md:col-6 flex flex-column gap-2">
            <label for="price">Price</label>
            <InputNumber
              id="price"
              v-model="price"
            />
          </div>
          <div class="col-12 md:col-6 flex flex-column gap-2">
            <label for="stock">Stock</label>
            <InputNumber
              id="stock"
              v-model="stock"
            />
          </div>
          <div class="col-12 md:col-6 flex flex-column gap-2">
            <label for="brand">Brand</label>
            <InputText
              id="brand"
              v-model="brand"
            />
          </div>
          <div class="col-12 md:col-6 flex flex-column gap-2">
            <label for="category">Category</label>
            <InputText
              id="category"
              v-model="category"
            />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script>
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import InputNumber from "primevue/inputnumber";

export default {
  components: {
    Card,
    InputText,
    Textarea,
    InputNumber,
  },
  data() {
    return {
      productId: null,
      identifier: "",
      name: "",
      description: "",
      price: 0,
      stock: 0,
      brand: "",
      category: "",
    };
  },
  mounted() {
    this.productId = this.$route.params.id;
    this.getProduct();
  },
  methods: {
    getProduct() {
      // Fetch product from API
      axios.get(`/products/${this.productId}?includes=media,category`)
        .then((response) => {
          const product = response.data.data;
          this.identifier = product.identifier;
          this.name = product.name;
          this.description = product.description;
          this.price = product.price;
          this.stock = product.stock;
          this.brand = product.brand;
          this.category = product.category.name;
        })
        .catch(() => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch product",
            life: 3000,
          });
        });
    },
  },
};
</script>
