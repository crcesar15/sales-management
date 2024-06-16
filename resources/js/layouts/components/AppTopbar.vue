<script >

export default {
  data() {
    return {
      outsideClickListener: null,
      topbarMenuActive: false,
      logoUrl: "",
    };
  },
  Methods: {
    onTopBarMenuButton() {
      this.topbarMenuActive = !this.topbarMenuActive;
    },
    onSettingsClick() {
      this.topbarMenuActive = false;
      this.$inertia.visit("/documentation");
    },
    bindOutsideClickListener() {
      if (!this.outsideClickListener) {
        this.outsideClickListener = (event) => {
          if (this.isOutsideClicked(event)) {
            this.topbarMenuActive = false;
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
      const sidebarEl = document.querySelector(".layout-topbar-menu");
      const topbarEl = document.querySelector(".layout-topbar-menu-button");

      return !(sidebarEl.isSameNode(event.target)
        || sidebarEl.contains(event.target)
        || topbarEl.isSameNode(event.target)
        || topbarEl.contains(event.target));
    },
  },
  computed: {
    topbarMenuClasses() {
      return {
        "layout-topbar-menu-mobile-active": this.topbarMenuActive.value,
      };
    },
  },
  onMounted() {
    this.bindOutsideClickListener();
  },
  onBeforeMount() {
    this.unbindOutsideClickListener();
  },
};
</script>

<template>
  <div class="layout-topbar">
    <router-link
      to="/"
      class="layout-topbar-logo"
    >
      <span>SAKAI</span>
    </router-link>

    <button
      class="p-link layout-menu-button layout-topbar-button"
      @click="$emit('on-menu-toggle')"
    >
      <i class="fa fa-bars" />
    </button>

    <button
      class="p-link layout-topbar-menu-button layout-topbar-button"
      @click="onTopBarMenuButton()"
    >
      <i class="pi pi-ellipsis-v" />
    </button>

    <div
      class="layout-topbar-menu"
      :class="topbarMenuClasses"
    >
      <button
        class="p-link layout-topbar-button"
        @click="onTopBarMenuButton()"
      >
        <i class="pi pi-calendar" />
        <span>Calendar</span>
      </button>
      <button
        class="p-link layout-topbar-button"
        @click="onTopBarMenuButton()"
      >
        <i class="pi pi-user" />
        <span>Profile</span>
      </button>
      <button
        class="p-link layout-topbar-button"
        @click="onSettingsClick()"
      >
        <i class="pi pi-cog" />
        <span>Settings</span>
      </button>
    </div>
  </div>
</template>

<style lang="scss" scoped></style>
