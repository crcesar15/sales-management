<script setup lang="ts">
import { ref, watch, computed } from "vue";
import Menu from "primevue/menu";
import Dialog from "primevue/dialog";
import type { MenuItem } from "primevue/menuitem";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useLayout } from "./Composables/layout";
import AppConfigurator from "./AppConfigurator.vue";

const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();
const selectedLanguage = ref<string>("en");
const showThemeDialog = ref<boolean>(false);

const { locale, t } = useI18n();

watch(selectedLanguage, (newVal: string) => {
  locale.value = newVal;
});

const userMenu = ref();

const userMenuItems = computed<MenuItem[]>(() => [
  {
    label: t("Theme Settings"),
    items: [
      {
        label: isDarkTheme.value ? t("Light Mode") : t("Dark Mode"),
        icon: isDarkTheme.value ? "fa fa-sun" : "fa fa-moon",
        command: () => {
          toggleDarkMode();
        },
      },
      {
        label: t("Colors"),
        icon: "fa fa-palette",
        command: () => {
          showThemeDialog.value = true;
        },
      },
    ]
  },
  {
    separator: true,
  },
  {
    label: t("Language"),
    items: [
      {
        label: t("English"),
        icon: "fa fa-language",
        command: () => {
          selectedLanguage.value = "en";
        },
      },
      {
        label: t("Español"),
        icon: "fa fa-language",
        command: () => {
          selectedLanguage.value = "es";
        },
      },
    ],
  },
  {
    separator: true,
  },
  {
    label: t("Logout"),
    icon: "fa fa-sign-out-alt",
    command: () => {
      router.post(route("logout"));
    },
  },
]);

function toggleUserMenu(event: Event): void {
  userMenu.value.toggle(event);
}
</script>

<template>
  <div class="layout-topbar">
    <div class="layout-topbar-logo-container">
      <button
        class="layout-menu-button layout-topbar-action"
        @click="onMenuToggle"
      >
        <i class="fa fa-bars" />
      </button>
      <a
        to="/"
        class="layout-topbar-logo"
      >
        <span>SAKAI</span>
      </a>
    </div>

    <div class="layout-topbar-actions">
      <button
        type="button"
        class="layout-topbar-action"
        @click="toggleUserMenu"
      >
        <i class="fa fa-user-circle" />
      </button>
      <Menu
        ref="userMenu"
        :model="userMenuItems"
        :popup="true"
      />
    </div>

    <Dialog
      v-model:visible="showThemeDialog"
      :header="$t('Theme Settings')"
      :modal="true"
      :style="{ width: '320px' }"
    >
      <AppConfigurator />
    </Dialog>
  </div>
</template>
