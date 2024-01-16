import { createApp } from "vue";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import ProductsGallery from "./components/ProductsGallery.vue";

const app = createApp({
  components: {
    ProductsGallery,
  },
});

app.use(PrimeVue).use(ToastService).use(ConfirmationService);

app.mount("#app");
