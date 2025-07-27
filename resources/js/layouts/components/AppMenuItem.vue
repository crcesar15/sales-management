<script setup>
import {
  onBeforeMount, ref, watch, onMounted,
} from "vue";
import { Link } from "@inertiajs/vue3";
import { useLayout } from "./composables/layout";

const { layoutState, setActiveMenuItem, onMenuToggle } = useLayout();

const props = defineProps({
  item: {
    type: Object,
    default: () => ({}),
  },
  index: {
    type: Number,
    default: 0,
  },
  root: {
    type: Boolean,
    default: true,
  },
  parentItemKey: {
    type: String,
    default: null,
  },
});

const isActiveMenu = ref(false);
const itemKey = ref(null);
const isVisible = ref(false);

onBeforeMount(() => {
  itemKey.value = props.parentItemKey ? `${props.parentItemKey}-${props.index}` : String(props.index);

  const activeItem = layoutState.activeMenuItem;

  isActiveMenu.value = activeItem === itemKey.value || activeItem ? activeItem.startsWith(`${itemKey.value}-`) : false;

  if (["Roles", "Users", "Categories", "Brands"].includes(props.item.label)) {
    isVisible.value = props.item.can ?? false;
  } else {
    isVisible.value = true;
  }
});

onMounted(() => {
  isActiveMenu.value = window.location.href.includes(props.item.to);
});

watch(
  () => layoutState.activeMenuItem,
  (newVal) => {
    isActiveMenu.value = newVal === itemKey.value || newVal.startsWith(`${itemKey.value}-`);
  },
);

function itemClick(event, item) {
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
      setActiveMenuItem(props.parentItemKey);
    } else {
      setActiveMenuItem(itemKey.value);
    }
  } else {
    setActiveMenuItem(itemKey.value);
  }
}
</script>

<template>
  <li :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActiveMenu }">
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
      v-if="item.to && !item.items && item.visible !== false"
      v-can="isVisible"
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
          :key="child"
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
