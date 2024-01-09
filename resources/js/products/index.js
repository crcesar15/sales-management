import { createApp } from "vue";
import { Quasar } from "quasar";
import quasarIconSet from "quasar/icon-set/fontawesome-v6";

// Import icon libraries
import "@quasar/extras/fontawesome-v6/fontawesome-v6.css";

import "quasar/src/css/index.sass";

import ProductsContainer from "./components/ProductsContainer.vue";
import TheNavbar from "../layout/TheNavbar.vue";

const app = createApp({
  components: {
    ProductsContainer,
    TheNavbar,
  },
});

app.use(Quasar, {
  iconSet: quasarIconSet,
});

app.mount("#app");
