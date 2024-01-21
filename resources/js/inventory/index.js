import { createApp } from "vue";
import { createRouter, createWebHistory } from "vue-router";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import ItemList from "./components/ItemList.vue";
import ItemEditor from "./components/ItemEditor.vue";

const routes = [
  { path: "/products", component: ItemList },
  { path: "/products/:id/edit", component: ItemEditor },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

const app = createApp({});

app.use(PrimeVue).use(ToastService).use(ConfirmationService).use(router);

app.mount("#app");
