import { createApp } from "vue";
import ProductsContainer from "./components/ProductsContainer.vue";

const app = createApp({
  components: {
    ProductsContainer,
  },
});

app.mount("#app");
