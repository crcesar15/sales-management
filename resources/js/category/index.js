import { createApp } from "vue";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import CategoryList from "./components/CategoryList.vue";

const app = createApp({
  components: {
    CategoryList,
  },
});

app.use(PrimeVue).use(ToastService).use(ConfirmationService);

app.mount("#app");
