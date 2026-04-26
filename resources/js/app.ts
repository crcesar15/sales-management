/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import { createApp, type DefineComponent, h } from "vue";
import { createI18n } from "vue-i18n";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import Ripple from "primevue/ripple";
import Tooltip from "primevue/tooltip";
import StyleClass from "primevue/styleclass";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import { InertiaProgress } from "@inertiajs/progress";
import { ZiggyVue } from "ziggy-js";
import { definePreset } from "@primeuix/themes";
import Aura from "@primeuix/themes/aura";
import Can from "./Directives/can";
import { configureYupLocale } from "./validations/yupLocale";

import en from "../lang/en.json";
import es from "../lang/es.json";

const messages = {
  en,
  es,
};

const AuraPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: "#edf3fa",
      100: "#cddcee",
      200: "#92b8da",
      300: "#4f8dbc",
      400: "#156fa8",
      500: "#00539b",
      600: "#003d74",
      700: "#002d57",
      800: "#00213f",
      900: "#00162b",
      950: "#000c19",
    },
    colorScheme: {
      light: {
        primary: {
          color: "{primary.500}",
          contrastColor: "#ffffff",
          hoverColor: "{primary.600}",
          activeColor: "{primary.700}",
        },
        highlight: {
          background: "{primary.50}",
          focusBackground: "{primary.100}",
          color: "{primary.700}",
          focusColor: "{primary.800}",
        },
      },
      dark: {
        primary: {
          color: "{primary.400}",
          contrastColor: "{surface.900}",
          hoverColor: "{primary.300}",
          activeColor: "{primary.200}",
        },
        highlight: {
          background: "color-mix(in srgb, {primary.400}, transparent 84%)",
          focusBackground: "color-mix(in srgb, {primary.400}, transparent 76%)",
          color: "rgba(255,255,255,.87)",
          focusColor: "rgba(255,255,255,.87)",
        },
      },
    },
  },
});

const i18n = createI18n({
  locale: "es", // default locale
  fallbackLocale: "es",
  messages,
  legacy: false,
});

configureYupLocale(i18n.global.t);

InertiaProgress.init();

// pages
const pages: Record<symbol, Promise<DefineComponent> | (() => Promise<DefineComponent>)> = import.meta.glob("./Pages/**/*.vue");

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, pages),
  setup({ el, App, props, plugin }) {
    createApp({ setup: () => () => h(App, props) })
      .use(plugin)
      .use(i18n)
      .use(ZiggyVue)
      .use(PrimeVue, {
        ripple: true,
        inputStyle: "outlined",
        theme: {
          preset: AuraPreset,
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
