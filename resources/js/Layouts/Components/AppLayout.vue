<script setup lang="ts">
import { computed, ref, watch } from "vue";
import Toast from "primevue/toast";
import { useLayout } from "./Composables/layout";
import AppFooter from "./AppFooter.vue";
import AppSidebar from "./AppSidebar.vue";

const {
  layoutConfig,
  layoutState,
  isSidebarActive,
  isSidebarCollapsed,
  resetMenu,
  onMenuToggle,
} = useLayout();

const outsideClickListener = ref<((event: Event) => void) | null>(null);

watch(isSidebarActive, (newVal) => {
  if (newVal) {
    bindOutsideClickListener();
  } else {
    unbindOutsideClickListener();
  }
});

const containerClass = computed(() => ({
  "layout-overlay": layoutConfig.menuMode === "overlay",
  "layout-static": layoutConfig.menuMode === "static",
  "layout-static-inactive": layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === "static",
  "layout-overlay-active": layoutState.overlayMenuActive,
  "layout-mobile-active": layoutState.staticMenuMobileActive,
  "layout-sidebar-collapsed": isSidebarCollapsed.value,
}));

function bindOutsideClickListener(): void {
  if (!outsideClickListener.value) {
    outsideClickListener.value = (event: Event) => {
      if (isOutsideClicked(event)) {
        resetMenu();
      }
    };
    document.addEventListener("click", outsideClickListener.value);
  }
}

function unbindOutsideClickListener(): void {
  if (outsideClickListener.value) {
    document.removeEventListener("click", outsideClickListener.value);
    outsideClickListener.value = null;
  }
}

function isOutsideClicked(event: Event): boolean {
  const sidebarEl = document.querySelector(".layout-sidebar");
  const mobileToggleEl = document.querySelector(".mobile-menu-toggle");
  const target = event.target as Node;

  if (!sidebarEl) return true;

  const isOutsideSidebar = !sidebarEl.isSameNode(target) && !sidebarEl.contains(target);
  const isOutsideMobileToggle = !mobileToggleEl || (!mobileToggleEl.isSameNode(target) && !mobileToggleEl.contains(target));

  return isOutsideSidebar && isOutsideMobileToggle;
}
</script>

<template>
  <div
    class="layout-wrapper"
    :class="containerClass"
  >
    <!-- Mobile Menu Toggle (visible only on mobile) -->
    <button
      class="mobile-menu-toggle"
      aria-label="Toggle menu"
      @click="onMenuToggle"
    >
      <i class="fa fa-bars" />
    </button>

    <AppSidebar />
    <div class="layout-main-container">
      <div class="layout-main">
        <slot />
      </div>
      <AppFooter />
    </div>
    <div class="layout-mask animate-fadein" />
  </div>
  <Toast />
</template>
