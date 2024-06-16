<script>
import AppTopbar from "./AppTopbar.vue";
import AppSidebar from "./AppSidebar.vue";

export default {
  components: {
    AppTopbar,
    AppSidebar,
  },
  provide() {
    return {
      layoutConfig: this.layoutConfig,
      layoutState: this.layoutState,
      setActiveMenuItem: this.setActiveMenuItems,
      onMenuToggle: this.onMenuToggle,
    };
  },
  data() {
    return {
      layoutConfig: {
        ripple: true,
        darkTheme: false,
        inputStyle: "outlined",
        menuMode: "static",
        theme: "aura-light-green",
        scale: 14,
        activeMenuItem: null,
      },
      layoutState: {
        staticMenuDesktopInactive: false,
        overlayMenuActive: false,
        profileSidebarVisible: false,
        configSidebarVisible: false,
        staticMenuMobileActive: false,
        menuHoverActive: false,
      },
      outsideClickListener: null,
    };
  },
  computed: {
    isSidebarActive() {
      return this.layoutState.overlayMenuActive || this.layoutState.staticMenuMobileActive;
    },
    isDarkTheme() {
      return this.layoutConfig.darkTheme;
    },
    containerClass() {
      return {
        "layout-theme-light": this.layoutConfig.darkTheme === "light",
        "layout-theme-dark": this.layoutConfig.darkTheme === "dark",
        "layout-overlay": this.layoutConfig.menuMode === "overlay",
        "layout-static": this.layoutConfig.menuMode === "static",
        "layout-static-inactive": this.layoutState.staticMenuDesktopInactive && this.layoutConfig.menuMode === "static",
        "layout-overlay-active": this.layoutState.overlayMenuActive,
        "layout-mobile-active": this.layoutState.staticMenuMobileActive,
        "p-ripple-disabled": this.layoutConfig.ripple === false,
      };
    },
  },
  watch: {
    isSidebarActive(newVal) {
      if (newVal) {
        this.bindOutsideClickListener();
      } else {
        this.unbindOutsideClickListener();
      }
    },
  },
  methods: {
    setScale(scale) {
      this.layoutConfig.scale = scale;
    },
    setActiveMenuItems(item) {
      this.layoutConfig.activeMenuItem = item || item;
    },
    onMenuToggle() {
      if (this.layoutConfig.menuMode === "overlay") {
        this.layoutState.overlayMenuActive = !this.layoutState.overlayMenuActive;
      }

      if (window.innerWidth > 991) {
        this.layoutState.staticMenuDesktopInactive = !this.layoutState.staticMenuDesktopInactive;
      } else {
        this.layoutState.staticMenuMobileActive = !this.layoutState.staticMenuMobileActive;
      }
    },
    bindOutsideClickListener() {
      if (!this.outsideClickListener) {
        this.outsideClickListener = (event) => {
          if (this.isOutsideClicked(event)) {
            this.layoutState.overlayMenuActive = false;
            this.layoutState.staticMenuMobileActive = false;
            this.layoutState.menuHoverActive = false;
          }
        };
        document.addEventListener("click", this.outsideClickListener);
      }
    },
    unbindOutsideClickListener() {
      if (this.outsideClickListener) {
        document.removeEventListener("click", this.outsideClickListener);
        this.outsideClickListener = null;
      }
    },
    isOutsideClicked(event) {
      const sidebarEl = document.querySelector(".layout-sidebar");
      const topBarEl = document.querySelector(".layout-menu-button");

      return !(sidebarEl.isSameNode(event.target)
        || sidebarEl.contains(event.target)
        || topBarEl.isSameNode(event.target)
        || topBarEl.contains(event.target));
    },
  },
};

</script>

<template>
  <div
    class="layout-wrapper"
    :class="containerClass"
  >
    <app-topbar @on-menu-toggle="onMenuToggle()" />
    <div class="layout-sidebar">
      <app-sidebar />
    </div>
    <div class="layout-main-container">
      <div class="layout-main">
        <slot />
      </div>
    </div>
    <div class="layout-mask" />
  </div>
  <Toast />
</template>

<style lang="scss" scoped></style>
