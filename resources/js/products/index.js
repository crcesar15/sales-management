import { createApp } from "vue";
import PrimeVue from "primevue/config";
import ProductsContainer from "./components/ProductsContainer.vue";

const app = createApp({
  components: {
    ProductsContainer,
  },
});

app.use(PrimeVue);

app.mount("#app");
