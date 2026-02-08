<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import PanelMenu from "primevue/panelmenu";
import { useMenuItems } from "../Composables/useMenuItems";
import { useLayout } from "./Composables/layout";
import type { SidebarMenuItem } from "../Types/menu";

const page = usePage();
const { menuItems } = useMenuItems();
const { layoutState, onMenuToggle } = useLayout();

const expandedKeys = ref<Record<string, boolean>>({});

const userPermissions = computed<string[]>(
  () => (page.props.auth?.permissions || []) as string[],
);

function hasPermission(item: SidebarMenuItem): boolean {
  if (!item.can) {
    return true;
  }
  return userPermissions.value.includes(item.can);
}

const filteredMenuItems = computed<SidebarMenuItem[]>(() => {
  return menuItems.value
    .map((group) => {
      if (!group.items) {
        return group;
      }

      const visibleChildren = group.items.filter((child) => hasPermission(child));
      if (visibleChildren.length === 0) {
        return null;
      }

      return { ...group, items: visibleChildren };
    })
    .filter(Boolean) as SidebarMenuItem[];
});

function getPathname(url: string): string {
  try {
    return new URL(url).pathname;
  } catch {
    return url;
  }
}

function isActiveRoute(item: SidebarMenuItem): boolean {
  if (!item.to) {
    return false;
  }
  try {
    const pathname = getPathname(route(item.to));
    return page.url.startsWith(pathname);
  } catch {
    return false;
  }
}

function computeExpandedKeys(): Record<string, boolean> {
  const keys: Record<string, boolean> = {};
  for (const group of filteredMenuItems.value) {
    if (group.items) {
      for (const child of group.items) {
        if (isActiveRoute(child) && group.key) {
          keys[group.key] = true;
          break;
        }
      }
    }
  }
  return keys;
}

expandedKeys.value = computeExpandedKeys();

watch(() => page.url, () => {
  const keys = computeExpandedKeys();
  expandedKeys.value = { ...expandedKeys.value, ...keys };
});

function onNavigate(): void {
  if (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive) {
    onMenuToggle();
  }
}
</script>

<template>
  <PanelMenu
    v-model:expandedKeys="expandedKeys"
    :model="filteredMenuItems"
    multiple
    class="layout-panel-menu"
    :pt="{
      panel: {
        style: 'border: none !important',
      },
    }"
  >
    <template #item="{ item }">
      <Link
        v-if="item.to && !item.items"
        v-ripple
        :href="route(item.to)"
        class="flex items-center cursor-pointer px-4 py-2"
        :class="{ 'active-route': isActiveRoute(item) }"
        @click="onNavigate"
      >
        <span :class="item.icon" />
        <span class="ml-2">{{ item.label }}</span>
      </Link>
      <a
        v-else
        v-ripple
        class="flex items-center cursor-pointer px-4 py-2"
        :href="item.url"
        :target="item.target"
      >
        <span :class="item.icon" />
        <span class="ml-2">{{ item.label }}</span>
        <span
          v-if="item.items"
          class="fa fa-angle-down text-primary ml-auto"
        />
      </a>
    </template>
  </PanelMenu>
</template>
