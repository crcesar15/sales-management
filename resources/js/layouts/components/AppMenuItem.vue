<script>
import { Link } from "@inertiajs/inertia-vue3";

export default {
  components: {
    Link,
  },
  inject: ["layoutConfig", "layoutState", "setActiveMenuItem", "onMenuToggle"],
  props: {
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
  },
  data() {
    return {
      isActiveMenu: false,
      itemKey: null,
    };
  },
  onBeforeMount() {
    this.itemKey.value = this.parentItemKey ? `${this.parentItemKey}-${this.index}` : String(this.index);

    const activeItem = this.layoutState.activeMenuItem;

    this.isActiveMenu.value = activeItem === this.itemKey.value || activeItem ? activeItem.startsWith(`${this.itemKey.value}-`) : false;
  },
  watch: {
    "layoutConfig.activeMenuItem": {
      handler(newVal) {
        this.isActiveMenu = newVal === this.itemKey || newVal.startsWith(`${this.itemKey}-`);
      },
      immediate: true,
    },
  },
  methods: {
    itemClick(event, item) {
      if (item.disabled) {
        event.preventDefault();
        return;
      }

      const { overlayMenuActive, staticMenuMobileActive } = this.layoutState;

      if ((item.to || item.url) && (staticMenuMobileActive || overlayMenuActive)) {
        this.onMenuToggle();
      }

      if (item.command) {
        item.command({ originalEvent: event, item });
      }

      let foundItemKey;

      if (item.items) {
        if (this.isActiveMenu) {
          foundItemKey = this.parentItemKey;
        } else {
          foundItemKey = this.itemKey;
        }
      } else {
        foundItemKey = this.itemKey;
      }

      this.setActiveMenuItem(foundItemKey);
    },
    checkActiveRoute(item) {
      // domain
      const domain = window.location.origin;
      // remove domain from route;
      return route(item.to).replace(domain, "") === this.$inertia.page.url;
    },
  },
};

</script>

<template>
  <li :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActiveMenu }">
    <div
      v-if="root && item.visible !== false"
      class="layout-menuitem-root-text"
    >
      {{ item.label }}
    </div>
    <a
      v-if="(!item.to || item.items) && item.visible !== false"
      :href="item.url"
      :class="item.class"
      :target="item.target"
      tabindex="0"
      @click="itemClick($event, item, index)"
    >
      <i
        :class="item.icon"
        class="layout-menuitem-icon"
      />
      <span class="layout-menuitem-text">{{ item.label }}</span>
      <i
        v-if="item.items"
        class="pi pi-fw pi-angle-down layout-submenu-toggler"
      />
    </a>
    <Link
      v-if="item.to && !item.items && item.visible !== false"
      :key="item.label"
      :class="[item.class, { 'active-route': checkActiveRoute(item) }]"
      :href="route(item.to)"
      @click="itemClick($event, item, index)"
    >
      <i
        :class="item.icon"
        class="layout-menuitem-icon"
      />
      <span class="layout-menuitem-text">{{ item.label }}</span>
      <i
        v-if="item.items"
        class="pi pi-fw pi-angle-down layout-submenu-toggler"
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
