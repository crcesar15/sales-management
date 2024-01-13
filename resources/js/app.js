/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import "./bootstrap";

import { createApp } from "vue";
import PrimeVue from "primevue/config";
import Menubar from "primevue/menubar";

const app = createApp({
  components: {
    Menubar,
  },
  data() {
    return {
      navbarStyles: {
        root: ["navbar navbar-expand-lg navbar-dark bg-primary"],
        menu: ["navbar-nav"],
        menuitem: ["nav-item"],
        content: ["nav-link"],
        action: ["nav-link"],
      },
      items: [
        {
          label: "Home",
          icon: "fa fa-home",
          to: "/",
        },
        {
          label: "Products",
          icon: "fa fa-shopping-cart",
          to: "/products",
        },
        {
          label: "About",
          icon: "fa fa-exclamation-circle",
          to: "/about",
        },
      ],
    };
  },
});

app.use(PrimeVue);

app.mount("#navbar");
