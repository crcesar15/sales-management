/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import { createApp, DefineComponent, h } from "vue";
import { createI18n } from "vue-i18n";
import { createInertiaApp, Link } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createPinia } from "pinia";
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
import { useAuthStore } from "@stores/auth";
import { inertiaAuthSyncPlugin } from "@plugins/inertia-auth-sync";
import type { AuthData } from "@app-types/auth-types";

import en from "../lang/en.json";
import es from "../lang/es.json";
import { definePreset } from "@primeuix/themes";

const messages = {
  en,
  es,
};

const i18n = createI18n({
  locale: "es", // default locale
  fallbackLocale: "es",
  messages,
  legacy: false,
});

InertiaProgress.init();

// Preset
const AuraPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: "#f0fdfa", 100: "#ccfbf1", 200: "#99f6e4", 300: "#5eead4", 400: "#2dd4bf", 500: "#14b8a6", 600: "#0d9488", 700: "#0f766e", 800: "#115e59", 900: "#134e4a", 950: "#042f2e",
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
  }
});

// pages
const pages: Record<symbol, Promise<DefineComponent> | (() => Promise<DefineComponent>)> = import.meta.glob("./Pages/**/*.vue");

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, pages),
  setup({
    el, App, props, plugin,
  }) {
    // Create Pinia instance
    const pinia = createPinia();

    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(pinia)
      .use(inertiaAuthSyncPlugin)
      .use(i18n)
      .use(ZiggyVue)
      .use(PrimeVue, {
        ripple: true,
        inputStyle: "filled",
        theme: {
          preset: AuraPreset,
          options: {
            primary: "amber",
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

    // Initialize auth store from initial Inertia props
    const authStore = useAuthStore();
    authStore.setAuth(props.initialPage.props.auth as AuthData | null);
  },
});

export default i18n;
