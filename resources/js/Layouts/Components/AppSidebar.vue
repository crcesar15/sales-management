<script setup lang="ts">
import { onBeforeUnmount, onMounted, shallowRef } from "vue";
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import PanelMenu from "primevue/panelmenu";
import { useLayout } from "./Composables/useLayout";
import { useMenuItems } from "../Composables/useMenuItems";
import type { SidebarMenuItem } from "../Types/menu";
import AppUserMenu from "./AppUserMenu.vue";

const { t } = useI18n();
const { isSidebarCollapsed, toggleSidebar, layoutState, onMenuToggle } = useLayout();
const { directMenuItems, groupedMenuItems, expandedKeys, updateExpandedKeys, isActiveRoute, isActiveGroup } = useMenuItems();

const isMobileViewport = shallowRef(false);

onMounted(() => {
  updateViewport();
  window.addEventListener("resize", updateViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", updateViewport);
});

function toggleSidebarControl(): void {
  if (isMobileViewport.value) {
    onMenuToggle();
    return;
  }

  toggleSidebar();
}

function updateViewport(): void {
  isMobileViewport.value = window.innerWidth < 992;
}

function onNavigate(): void {
  if (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive) {
    onMenuToggle();
  }
}

function menuItemHref(item: SidebarMenuItem): string {
  return item.to ? route(item.to) : "";
}

function onPanelOpen(): void {
  if (isSidebarCollapsed.value && window.innerWidth >= 992) {
    toggleSidebar();
  }
}

function onPanelClose(event: { item: SidebarMenuItem }): void {
  if (!isSidebarCollapsed.value || window.innerWidth < 992 || typeof event.item.key !== "string") {
    return;
  }

  updateExpandedKeys({ [event.item.key]: true });
  toggleSidebar();
}
</script>

<template>
  <aside id="app-sidebar" class="layout-sidebar" :class="{ 'sidebar-collapsed': isSidebarCollapsed }">
    <!-- Header Section: Logo + Toggle -->
    <div class="sidebar-header">
      <Link :href="route('home')" class="logo-link" @click="onNavigate">
        <span class="logo-text">SAKAI</span>
      </Link>
      <button
        v-tooltip.right="isMobileViewport ? t('Close navigation') : isSidebarCollapsed ? t('Expand sidebar') : t('Collapse sidebar')"
        class="sidebar-collapse-btn"
        :aria-label="isMobileViewport ? t('Close navigation') : isSidebarCollapsed ? t('Expand sidebar') : t('Collapse sidebar')"
        :aria-expanded="isMobileViewport ? true : !isSidebarCollapsed"
        aria-controls="app-sidebar-navigation"
        @click="toggleSidebarControl"
      >
        <i :class="isMobileViewport ? 'fa fa-times' : 'fa fa-bars'" aria-hidden="true" />
      </button>
    </div>

    <!-- Menu Section: Scrollable Navigation -->
    <nav id="app-sidebar-navigation" class="sidebar-menu" :aria-label="t('Main navigation')">
      <div class="sidebar-direct-links">
        <Link
          v-for="item in directMenuItems"
          :key="item.key"
          v-ripple
          :href="menuItemHref(item)"
          class="menu-item"
          :class="{ 'active-route': isActiveRoute(item) }"
          :aria-current="isActiveRoute(item) ? 'page' : undefined"
          @click="onNavigate"
        >
          <span class="menu-icon" :class="item.icon" aria-hidden="true" />
          <span class="menu-label">{{ item.label }}</span>
        </Link>
      </div>

      <PanelMenu
        :expanded-keys="expandedKeys"
        :model="groupedMenuItems"
        class="layout-panel-menu"
        @update:expanded-keys="updateExpandedKeys"
        @panel-open="onPanelOpen"
        @panel-close="onPanelClose"
        :pt="{
          root: { class: 'border-none bg-transparent' },
          panel: { class: 'border-none bg-transparent' },
          headerContent: { class: 'border-none bg-transparent p-0' },
          content: { class: 'border-none bg-transparent p-0' },
        }"
      >
        <template #item="{ item, props }">
          <Link
            v-if="item.to"
            v-ripple
            v-bind="props.action"
            :href="menuItemHref(item)"
            class="menu-item"
            :class="{ 'active-route': isActiveRoute(item) }"
            :aria-current="isActiveRoute(item) ? 'page' : undefined"
            @click="onNavigate"
          >
            <span class="menu-icon" :class="item.icon" aria-hidden="true" />
            <span class="menu-label">{{ item.label }}</span>
          </Link>
          <span v-else v-ripple class="menu-item menu-parent" :class="{ 'active-group': isActiveGroup(item) }">
            <span class="menu-icon" :class="item.icon" aria-hidden="true" />
            <span class="menu-label">{{ item.label }}</span>
            <span class="fa fa-chevron-down menu-chevron" aria-hidden="true" />
          </span>
        </template>
      </PanelMenu>
    </nav>

    <!-- User Section: Profile + Dropdown -->
    <div class="sidebar-user">
      <AppUserMenu />
    </div>
  </aside>
</template>

<style lang="scss" scoped></style>
