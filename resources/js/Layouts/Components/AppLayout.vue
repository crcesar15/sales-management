<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, shallowRef, useTemplateRef, watch } from "vue";
import { useI18n } from "vue-i18n";
import Toast from "primevue/toast";
import { useLayout } from "./Composables/useLayout";
import AppFooter from "./AppFooter.vue";
import AppSidebar from "./AppSidebar.vue";

const { t } = useI18n();
const { layoutState, isSidebarActive, isSidebarCollapsed, resetMenu, onMenuToggle } = useLayout();

const outsideClickListener = shallowRef<((event: Event) => void) | null>(null);
const isMobileViewport = shallowRef(false);
const mobileMenuToggle = useTemplateRef<HTMLButtonElement>("mobileMenuToggle");

const isMobileSidebarOpen = computed(() => isMobileViewport.value && layoutState.staticMenuMobileActive);
const isMobileSidebarHidden = computed(() => isMobileViewport.value && !isMobileSidebarOpen.value);

watch(isSidebarActive, (newVal) => {
  if (newVal) {
    bindOutsideClickListener();
  } else {
    unbindOutsideClickListener();
  }
});

watch(isMobileSidebarOpen, (isOpen, _wasOpen, onCleanup) => {
  if (!isOpen) {
    document.removeEventListener("keydown", onDocumentKeydown);

    if (isMobileViewport.value) {
      void nextTick(() => mobileMenuToggle.value?.focus());
    }

    return;
  }

  let isCancelled = false;

  document.addEventListener("keydown", onDocumentKeydown);
  onCleanup(() => {
    isCancelled = true;
    document.removeEventListener("keydown", onDocumentKeydown);
  });

  void nextTick(() => {
    if (isCancelled) return;

    document.querySelector<HTMLElement>("#app-sidebar-navigation a, #app-sidebar-navigation [role='button']")?.focus();
  });
});

onMounted(() => {
  updateViewport();
  window.addEventListener("resize", updateViewport);
});

onBeforeUnmount(() => {
  unbindOutsideClickListener();
  document.removeEventListener("keydown", onDocumentKeydown);
  window.removeEventListener("resize", updateViewport);
});

const containerClass = computed(() => ({
  "layout-static-inactive": layoutState.staticMenuDesktopInactive,
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

function updateViewport(): void {
  isMobileViewport.value = window.innerWidth < 992;

  if (!isMobileViewport.value) {
    resetMenu();
  }
}

function closeMobileMenu(): void {
  if (isMobileSidebarOpen.value) {
    resetMenu();
  }
}

function onDocumentKeydown(event: KeyboardEvent): void {
  if (event.key === "Escape") {
    event.preventDefault();
    closeMobileMenu();
  }
}
</script>

<template>
  <div class="layout-wrapper" :class="containerClass">
    <!-- Mobile Menu Toggle (visible only on mobile) -->
    <button
      ref="mobileMenuToggle"
      class="mobile-menu-toggle"
      :aria-label="isMobileSidebarOpen ? t('Close navigation') : t('Open navigation')"
      :aria-expanded="isMobileSidebarOpen"
      aria-controls="app-sidebar"
      @click="onMenuToggle"
    >
      <i class="fa fa-bars" aria-hidden="true" />
    </button>

    <AppSidebar :inert="isMobileSidebarHidden" :aria-hidden="isMobileSidebarHidden ? 'true' : undefined" />
    <div class="layout-main-container" :inert="isMobileSidebarOpen" :aria-hidden="isMobileSidebarOpen ? 'true' : undefined">
      <div class="layout-main">
        <slot />
      </div>
      <AppFooter />
    </div>
    <div class="layout-mask animate-fadein" @click="closeMobileMenu" />
  </div>
  <Toast />
</template>
