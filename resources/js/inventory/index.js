import { createApp } from "vue";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import ItemList from "./components/ItemList.vue";

const app = createApp({
  components: {
    ItemList,
  },
});

app.use(PrimeVue).use(ToastService).use(ConfirmationService);

app.mount("#app");
