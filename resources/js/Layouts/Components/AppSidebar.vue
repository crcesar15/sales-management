<script setup lang="ts">
import { ref, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import PanelMenu from "primevue/panelmenu";
import Menu from "primevue/menu";
import type { MenuItem } from "primevue/menuitem";
import { useLayout } from "./Composables/useLayout";
import { useMenuItems } from "../Composables/useMenuItems";
import type { SidebarMenuItem } from "../Types/menu";

const page = usePage();
const { t } = useI18n();
const { isDarkMode, toggleDarkMode, isSidebarCollapsed, toggleSidebar, layoutState, onMenuToggle } = useLayout();
const { filteredMenuItems, isActiveRoute, expandedKeys } = useMenuItems();

const userMenu = ref();

// ========================================
// User Section Logic
// ========================================
const userName = computed(() => {
  const user = page.props.auth?.user as { name?: string } | undefined;
  return user?.name || t("User");
});

const userInitial = computed(() => userName.value.charAt(0).toUpperCase());

const userMenuItems = computed<MenuItem[]>(() => [
  {
    label: t("Profile"),
    icon: "fa fa-user",
    command: () => router.visit(route("profile")),
  },
  { separator: true },
  {
    label: isDarkMode.value ? t("Light Mode") : t("Dark Mode"),
    icon: isDarkMode.value ? "fa fa-sun" : "fa fa-moon",
    command: () => toggleDarkMode(),
  },
  { separator: true },
  {
    label: t("Logout"),
    icon: "fa fa-sign-out-alt",
    command: () => router.post(route("logout")),
  },
]);

function toggleUserMenu(event: Event): void {
  userMenu.value.toggle(event);
}

function onNavigate(): void {
  if (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive) {
    onMenuToggle();
  }
}
</script>

<template>
  <div
    class="layout-sidebar"
    :class="{ 'sidebar-collapsed': isSidebarCollapsed }"
  >
    <!-- Header Section: Logo + Toggle -->
    <div class="sidebar-header">
      <a v-if="!isSidebarCollapsed" href="/" class="logo-link">
        <span class="logo-text">SAKAI</span>
      </a>
      <button
        v-tooltip.right="isSidebarCollapsed ? t('Expand sidebar') : t('Collapse sidebar')"
        class="sidebar-collapse-btn"
        :aria-label="isSidebarCollapsed ? t('Expand sidebar') : t('Collapse sidebar')"
        @click="toggleSidebar"
      >
        <i class="fa fa-bars" />
      </button>
    </div>

    <!-- Menu Section: Scrollable Navigation -->
    <div class="sidebar-menu">
      <PanelMenu
        v-model:expandedKeys="expandedKeys"
        :model="filteredMenuItems"
        multiple
        class="layout-panel-menu"
        :pt="{
          root: { class: 'border-none bg-transparent' },
          panel: ({ instance }: { instance: { item: SidebarMenuItem } }) => ({
            class: [
              'border-none bg-transparent',
              { 'menu-separator': instance.item?.separator },
            ],
          }),
          headerContent: { class: 'border-none bg-transparent p-0' },
          content: { class: 'border-none bg-transparent p-0' },
        }"
      >
        <template #item="{ item }">
          <Link
            v-if="item.to && !item.items"
            v-ripple
            :href="route(item.to)"
            class="menu-item"
            :class="{ 'active-route': isActiveRoute(item) }"
            @click="onNavigate"
          >
            <span class="menu-icon" :class="item.icon" />
            <span class="menu-label">{{ item.label }}</span>
          </Link>
          <a
            v-else
            v-ripple
            class="menu-item menu-parent"
            :href="item.url"
            :target="item.target"
          >
            <span class="menu-icon" :class="item.icon" />
            <span class="menu-label">{{ item.label }}</span>
            <span
              v-if="item.items"
              class="fa fa-chevron-down menu-chevron"
            />
          </a>
        </template>
      </PanelMenu>
    </div>

    <!-- User Section: Profile + Dropdown -->
    <div class="sidebar-user">
      <button
        v-ripple
        class="user-button"
        @click="toggleUserMenu"
      >
        <span class="user-avatar">{{ userInitial }}</span>
        <span class="user-info">
          <span class="user-name">{{ userName }}</span>
        </span>
      </button>
      <Menu
        ref="userMenu"
        :model="userMenuItems"
        :popup="true"
      />
    </div>
  </div>
</template>

<style lang="scss" scoped></style>
