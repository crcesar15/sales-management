/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import "./bootstrap";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/inertia-vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

import Ripple from "primevue/ripple";
import StyleClass from "primevue/styleclass";
import PrimeVue from "primevue/config";

import Menubar from "primevue/menubar";
import PMenu from "primevue/menu";
import PButton from "primevue/button";
import Sidebar from "primevue/sidebar";

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob("./pages/**/*.vue")),
  setup({
    el, App, props, plugin,
  }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .component("Menubar", Menubar)
      .component("PMenu", PMenu)
      .component("PButton", PButton)
      .component("Sidebar", Sidebar)
      .use(PrimeVue, { ripple: true })
      .directive("ripple", Ripple)
      .directive("styleclass", StyleClass)
      .mount(el);
  },
});
