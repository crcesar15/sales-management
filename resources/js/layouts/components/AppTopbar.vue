<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import { useLayout } from "./composables/layout";
import AppConfigurator from "./AppConfigurator.vue";

const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();
</script>

<template>
  <div class="layout-topbar">
    <div class="layout-topbar-logo-container">
      <button
        class="layout-menu-button layout-topbar-action"
        @click="onMenuToggle"
      >
        <i class="fa fa-bars" />
      </button>
      <Link
        to="/"
        class="layout-topbar-logo"
      >
        <span>SAKAI</span>
      </Link>
    </div>

    <div class="layout-topbar-actions">
      <div class="layout-config-menu">
        <button
          type="button"
          class="layout-topbar-action"
          @click="toggleDarkMode"
        >
          <i :class="['fa-regular', { 'fa-moon': isDarkTheme, 'fa-sun': !isDarkTheme }]" />
        </button>
        <div class="relative">
          <button
            v-styleclass="{
              selector: '@next',
              toggleClass: 'hidden',
              hideOnOutsideClick: 'true',
            }"
            type="button"
            class="layout-topbar-action layout-topbar-action-highlight palette-button"
          >
            <i class="fa fa-palette" />
          </button>
          <AppConfigurator />
        </div>
      </div>
      <div class="layout-topbar-menu hidden lg:block">
        <div class="layout-topbar-menu-content">
          <button
            type="button"
            class="layout-topbar-action"
          >
            <i class="fa fa-calendar" />
            <span>Calendar</span>
          </button>
          <button
            type="button"
            class="layout-topbar-action"
          >
            <i class="fa fa-inbox" />
            <span>Messages</span>
          </button>
          <button
            type="button"
            class="layout-topbar-action"
          >
            <i class="fa fa-user" />
            <span>Profile</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.layout-topbar button {
  border: 0px;
  background-color: transparent;
}

.layout-topbar button:hover {
  background-color: var(--surface-hover);
}

.palette-button:hover {
  background-color: var(--primary-color) !important;
  color: var(--primary-contrast-color) !important;
}
</style>
