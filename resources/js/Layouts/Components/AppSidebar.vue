<script setup lang="ts">
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import Menu from "primevue/menu";
import Dialog from "primevue/dialog";
import type { MenuItem } from "primevue/menuitem";
import AppMenu from "./AppMenu.vue";
import AppConfigurator from "./AppConfigurator.vue";
import { useLayout } from "./Composables/layout";

const page = usePage();
const { t } = useI18n();
const {
  toggleDarkMode,
  isDarkTheme,
  isSidebarCollapsed,
  onSidebarCollapse,
} = useLayout();

const userMenu = ref();
const showThemeDialog = ref(false);

const userName = computed(() => {
  const user = page.props.auth?.user as { name?: string } | undefined;
  return user?.name || t("User");
});

const userInitial = computed(() => {
  return userName.value.charAt(0).toUpperCase();
});

const userMenuItems = computed<MenuItem[]>(() => [
  {
    label: t("Profile"),
    icon: "fa fa-user",
    command: () => {
      router.visit(route("profile"));
    },
  },
  {
    separator: true,
  },
  {
    label: isDarkTheme.value ? t("Light Mode") : t("Dark Mode"),
    icon: isDarkTheme.value ? "fa fa-sun" : "fa fa-moon",
    command: () => {
      toggleDarkMode();
    },
  },
  {
    label: t("Theme Colors"),
    icon: "fa fa-palette",
    command: () => {
      showThemeDialog.value = true;
    },
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
  <div
    class="layout-sidebar"
    :class="{ 'sidebar-collapsed': isSidebarCollapsed }"
  >
    <!-- Header: Logo + Toggle -->
    <div class="sidebar-header">
      <a v-if="!isSidebarCollapsed" href="/" class="logo-link">
        <span class="logo-icon">S</span>
        <span class="logo-text">SAKAI</span>
      </a>
      <button
        v-tooltip.right="isSidebarCollapsed ? t('Expand sidebar') : t('Collapse sidebar')"
        class="sidebar-collapse-btn"
        :aria-label="isSidebarCollapsed ? t('Expand sidebar') : t('Collapse sidebar')"
        @click="onSidebarCollapse"
      >
        <i class="fa fa-table-columns" />
      </button>
    </div>

    <!-- Menu Section -->
    <div class="sidebar-menu">
      <AppMenu />
    </div>

    <!-- User Section -->
    <div class="sidebar-user">
      <button
        v-ripple
        class="user-button"
        @click="toggleUserMenu"
      >
        <span class="user-avatar">
          {{ userInitial }}
        </span>
        <span class="user-info">
          <span class="user-name">{{ userName }}</span>
        </span>
        <i class="fa fa-ellipsis-vertical user-menu-icon" />
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

<style lang="scss" scoped></style>
