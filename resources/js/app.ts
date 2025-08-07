/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import { createApp, DefineComponent, h } from "vue";
import { createI18n } from "vue-i18n";
import { createInertiaApp, Link } from "@inertiajs/vue3";
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
import Aura from "@primeuix/themes/aura";
import Can from "./Directives/can";

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
  legacy: false,
});

InertiaProgress.init();

// pages
const pages: Record<symbol, Promise<DefineComponent> | (() => Promise<DefineComponent>)> = import.meta.glob("./Pages/**/*.vue");

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, pages),
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
      .directive("can", Can)
      .mount(el);
  },
});

export default i18n;
