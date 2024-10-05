<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import { ref, watch } from "vue";
import Dropdown from "primevue/dropdown";
import { useI18n } from "vue-i18n";
import { useLayout } from "./composables/layout";
import AppConfigurator from "./AppConfigurator.vue";

const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();
const selectedLanguage = ref("en");
const languages = ref([
  { name: "EN", code: "en" },
  { name: "ES", code: "es" },
]);

const t = useI18n();

watch(selectedLanguage, (newVal) => {
  t.locale.value = newVal;
});

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
      <a
        to="/"
        class="layout-topbar-logo"
      >
        <span>SAKAI</span>
      </a>
    </div>

    <div class="layout-topbar-actions">
      <div class="layout-config-menu">
        <button
          type="button"
          class="layout-topbar-action"
          @click="toggleDarkMode"
        >
          <i :class="['fa', { 'fa-moon': isDarkTheme, 'fa-sun': !isDarkTheme }]" />
        </button>
        <div class="relative">
          <button
            v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
            type="button"
            class="layout-topbar-action layout-topbar-action-highlight"
          >
            <i class="fa fa-palette" />
          </button>
          <AppConfigurator />
        </div>
      </div>

      <button
        v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
        class="layout-topbar-menu-button layout-topbar-action"
      >
        <i class="fa fa-ellipsis-v" />
      </button>
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
