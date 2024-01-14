import { createApp } from "vue";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import ProductsContainer from "./components/ProductsContainer.vue";

const app = createApp({
  components: {
    ProductsContainer,
  },
});

app.use(PrimeVue).use(ToastService).use(ConfirmationService);

app.mount("#app");
