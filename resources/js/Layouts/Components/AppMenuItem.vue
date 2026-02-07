<script setup lang="ts">
import {
  onBeforeMount, ref, watch, computed,
} from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { AppMenuItemProps } from "../Types/menu";
import { useLayout } from "./Composables/layout";

const { layoutState, setActiveMenuItem, onMenuToggle } = useLayout();

const props = withDefaults(defineProps<AppMenuItemProps>(), {
  root: true,
  parentItemKey: null,
});

const page = usePage();
const isActiveMenu = ref<boolean>(false);
const itemKey = ref<string>("");
const isVisible = ref<boolean>(true);

// Compute visibility based on permissions
const hasPermission = computed<boolean>(() => {
  if (!props.item.can) {
    return true;
  }
  const userPermissions = (page.props.auth?.permissions || []) as string[];
  return userPermissions.includes(props.item.can);
});

onBeforeMount(() => {
  itemKey.value = props.parentItemKey ? `${props.parentItemKey}-${props.index}` : String(props.index);

  const activeItem = layoutState.activeMenuItem;

  isActiveMenu.value = activeItem === itemKey.value || (activeItem ? activeItem.startsWith(`${itemKey.value}-`) : false);

  // Set visibility based on permission
  isVisible.value = hasPermission.value;

  // Check if current route matches this menu item
  if (props.item.to && page.url) {
    isActiveMenu.value = page.url.includes(props.item.to);
  }
});

watch(
  () => layoutState.activeMenuItem,
  (newVal) => {
    if (newVal) {
      isActiveMenu.value = newVal === itemKey.value || newVal.startsWith(`${itemKey.value}-`);
    }
  },
);

// Watch for route changes
watch(
  () => page.url,
  (newUrl) => {
    if (props.item.to && newUrl) {
      isActiveMenu.value = newUrl.includes(props.item.to);
    }
  },
);

function itemClick(event: Event, item: AppMenuItemProps["item"]): void {
  if (item.disabled) {
    event.preventDefault();
    return;
  }

  if ((item.to || item.url) && (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive)) {
    onMenuToggle();
  }

  if (item.command) {
    item.command({ originalEvent: event, item });
  }

  if (item.items) {
    if (isActiveMenu.value) {
      setActiveMenuItem(props.parentItemKey || "");
    } else {
      setActiveMenuItem(itemKey.value);
    }
  } else {
    setActiveMenuItem(itemKey.value);
  }
}
</script>

<template>
  <li
    v-if="isVisible"
    :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActiveMenu }"
  >
    <div
      v-if="root && item.visible !== false"
      class="layout-menuitem-root-text"
    >
      {{ $t(item.label) }}
    </div>
    <a
      v-if="(!item.to || item.items) && item.visible !== false"
      :href="item.url"
      :class="item.class"
      :target="item.target"
      tabindex="0"
      @click="itemClick($event, item)"
    >
      <i
        :class="item.icon"
        class="layout-menuitem-icon"
      />
      <span class="layout-menuitem-text">{{ $t(item.label) }}</span>
      <i
        v-if="item.items"
        class="fa fa-fw fa-angle-down layout-submenu-toggler"
      />
    </a>
    <Link
      v-if="item.to && !item.items && item.visible !== false && isVisible"
      :class="[item.class, { 'active-route': isActiveMenu }]"
      tabindex="0"
      :href="route(item.to)"
      @click="itemClick($event, item)"
    >
      <i
        :class="item.icon"
        class="layout-menuitem-icon"
      />
      <span class="layout-menuitem-text">{{ $t(item.label) }}</span>
      <i
        v-if="item.items"
        class="fa fa-fw fa-angle-down layout-submenu-toggler"
      />
    </Link>
    <Transition
      v-if="item.items && item.visible !== false"
      name="layout-submenu"
    >
      <ul
        v-show="root ? true : isActiveMenu"
        class="layout-submenu"
      >
        <app-menu-item
          v-for="(child, i) in item.items"
          :key="child.label"
          :index="i"
          :item="child"
          :parent-item-key="itemKey"
          :root="false"
        />
      </ul>
    </Transition>
  </li>
</template>

<style lang="scss" scoped></style>
