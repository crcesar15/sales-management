/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import "./bootstrap";

import { createApp, h } from "vue";
import { createInertiaApp, Link } from "@inertiajs/inertia-vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

import Ripple from "primevue/ripple";
import StyleClass from "primevue/styleclass";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";

import PButton from "primevue/button";

import { InertiaProgress } from "@inertiajs/progress";

InertiaProgress.init();

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob("./pages/**/*.vue")),
  setup({
    el, App, props, plugin,
  }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .component("PButton", PButton)
      .component("Link", Link)
      .use(PrimeVue, { ripple: true })
      .use(ToastService)
      .use(ConfirmationService)
      .directive("ripple", Ripple)
      .directive("styleclass", StyleClass)
      .mount(el);
  },
});
