/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import "./bootstrap";

import { createApp, h } from "vue";
import { createI18n } from "vue-i18n";
import { createInertiaApp, Link } from "@inertiajs/inertia-vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

import Ripple from "primevue/ripple";
import Tooltip from "primevue/tooltip";
import StyleClass from "primevue/styleclass";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import PButton from "primevue/button";
import { InertiaProgress } from "@inertiajs/progress";
import { ZiggyVue } from "ziggy-js";
import Aura from "@primevue/themes/aura";

import en from "../lang/en.json";
import es from "../lang/es.json";

const messages = {
  en,
  es,
};

const i18n = createI18n({
  locale: "en", // default locale
  fallbackLocale: "en",
  messages,
});

InertiaProgress.init();

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob("./pages/**/*.vue")),
  setup({
    el, App, props, plugin,
  }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(i18n)
      .component("PButton", PButton)
      .component("Link", Link)
      .use(ZiggyVue)
      .use(PrimeVue, {
        ripple: true,
        theme: {
          preset: Aura,
          options: {
            darkModeSelector: ".app-dark",
          },
        },
      })
      .use(ToastService)
      .use(ConfirmationService)
      .directive("ripple", Ripple)
      .directive("styleclass", StyleClass)
      .directive("tooltip", Tooltip)
      .mount(el);
  },
});

export default i18n;
