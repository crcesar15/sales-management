<script setup lang="ts">
import { computed, useTemplateRef } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import Menu from "primevue/menu";
import type { MenuItem } from "primevue/menuitem";
import { useLayout } from "./Composables/useLayout";

defineProps<{ compact?: boolean }>();

const page = usePage();
const { t } = useI18n();
const { isDarkMode, toggleDarkMode } = useLayout();
const userMenu = useTemplateRef<{ toggle: (event: Event) => void }>("userMenu");

const userName = computed(() => {
  const user = page.props.auth?.user as { name?: string } | undefined;
  return user?.name || t("User");
});

const userInitial = computed(() => userName.value.charAt(0).toUpperCase());

const userMenuItems = computed<MenuItem[]>(() => [
  {
    label: t("Profile"),
    icon: "fa fa-user",
    command: () => router.visit(route("profile")),
  },
  { separator: true },
  {
    label: isDarkMode.value ? t("Light Mode") : t("Dark Mode"),
    icon: isDarkMode.value ? "fa fa-sun" : "fa fa-moon",
    command: () => toggleDarkMode(),
  },
  { separator: true },
  {
    label: t("Logout"),
    icon: "fa fa-sign-out-alt",
    command: () => router.post(route("logout")),
  },
]);

function toggleUserMenu(event: Event): void {
  userMenu.value?.toggle(event);
}
</script>

<template>
  <div class="app-user-menu" :class="{ 'app-user-menu-compact': compact }">
    <button v-ripple class="user-button" :aria-label="userName" @click="toggleUserMenu">
      <span class="user-avatar">{{ userInitial }}</span>
      <span v-if="!compact" class="user-info">
        <span class="user-name">{{ userName }}</span>
      </span>
    </button>
    <Menu ref="userMenu" :model="userMenuItems" popup />
  </div>
</template>

<style lang="scss" scoped>
.app-user-menu {
  .user-button {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.5rem;
    border: none;
    background: transparent;
    border-radius: var(--p-content-border-radius);
    cursor: pointer;
    transition: background 150ms ease;
    text-align: left;

    &:hover {
      background: var(--surface-hover);
    }

    &:focus-visible {
      outline: 2px solid var(--primary-color);
      outline-offset: -2px;
    }
  }

  .user-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: var(--primary-color);
    color: var(--primary-contrast-color);
    font-size: 1rem;
    font-weight: 600;
    flex-shrink: 0;
  }

  .user-info {
    flex: 1;
    margin-left: 0.75rem;
    overflow: hidden;
    transition: opacity 200ms ease;
  }

  .user-name {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}

.app-user-menu-compact {
  .user-button {
    justify-content: center;
    width: 44px;
    height: 44px;
    padding: 0;
  }
}
</style>
