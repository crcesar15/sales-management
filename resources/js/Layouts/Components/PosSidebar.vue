<script setup lang="ts">
import { computed, ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { Menu } from "primevue";
import type { MenuItem } from "primevue/menuitem";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import { usePosLayout } from "@composables/usePosLayout";
import { useLayout } from "@layouts/Components/Composables/useLayout";
import { usePosStore } from "@/Composables/usePosStore";
import { useAuth } from "@/Composables/useAuth";

interface PosNavigationItem {
  key: string;
  label: string;
  icon: string;
  enabled: boolean;
}

const { t } = useI18n();
const page = usePage();
const { isSidebarCollapsed, openCashMovementDialog } = usePosLayout();
const { isDarkMode, toggleDarkMode } = useLayout();
const posStore = usePosStore();
const { can } = useAuth();
const userMenu = ref();

const itemClass =
  "flex min-h-12 w-full items-center gap-3 rounded-md px-3 text-left text-base font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary-500";

const navigationItems = computed<PosNavigationItem[]>(() => [
  { key: "pos", label: t("POS"), icon: "fa fa-cash-register", enabled: true },
  { key: "sales", label: t("Sales"), icon: "fa fa-receipt", enabled: false },
  { key: "customers", label: t("Customers"), icon: "fa fa-users", enabled: false },
  { key: "product-gallery", label: t("Product Gallery"), icon: "fa fa-images", enabled: false },
  {
    key: "cash-movements",
    label: t("Cash movements"),
    icon: "fa fa-money-bill-transfer",
    enabled: posStore.isShiftOpen && can("cash_movement.create"),
  },
]);

const userName = computed(() => {
  const user = page.props.auth?.user as { name?: string } | undefined;
  return user?.name || t("User");
});

const userInitial = computed(() => userName.value.charAt(0).toUpperCase());

const userMenuItems = computed<MenuItem[]>(() => [
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
  userMenu.value.toggle(event);
}
</script>

<template>
  <aside
    id="pos-sidebar"
    class="flex shrink-0 flex-col overflow-hidden border-r border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900"
    :class="isSidebarCollapsed ? 'w-16' : 'w-60'"
  >
    <nav class="flex flex-1 flex-col gap-1 p-2" :aria-label="t('POS navigation')">
      <template v-for="item in navigationItems" :key="item.key">
        <Link
          v-if="item.key === 'pos'"
          :href="route('pos')"
          :class="[
            itemClass,
            isSidebarCollapsed ? 'justify-center px-2' : '',
            'bg-primary-50 text-primary-700 dark:bg-primary-950 dark:text-primary-300',
          ]"
          :aria-label="item.label"
          aria-current="page"
          :title="isSidebarCollapsed ? item.label : undefined"
        >
          <i :class="item.icon" class="w-5 shrink-0 text-center" aria-hidden="true" />
          <span v-if="!isSidebarCollapsed" class="truncate">{{ item.label }}</span>
        </Link>

        <button
          v-else-if="item.enabled"
          v-ripple
          type="button"
          :class="[
            itemClass,
            isSidebarCollapsed ? 'justify-center px-2' : '',
            'text-surface-700 hover:bg-surface-100 dark:text-surface-200 dark:hover:bg-surface-800',
          ]"
          :aria-label="item.label"
          :title="isSidebarCollapsed ? item.label : undefined"
          @click="openCashMovementDialog"
        >
          <i :class="item.icon" class="w-5 shrink-0 text-center" aria-hidden="true" />
          <span v-if="!isSidebarCollapsed" class="truncate">{{ item.label }}</span>
        </button>

        <button
          v-else
          type="button"
          disabled
          :class="[itemClass, isSidebarCollapsed ? 'justify-center px-2' : '', 'cursor-not-allowed text-surface-400 dark:text-surface-500']"
          :aria-label="item.label"
          :title="isSidebarCollapsed ? item.label : undefined"
        >
          <i :class="item.icon" class="w-5 shrink-0 text-center" aria-hidden="true" />
          <span v-if="!isSidebarCollapsed" class="truncate">{{ item.label }}</span>
        </button>
      </template>
    </nav>

    <div class="border-t border-surface-200 p-2 dark:border-surface-700">
      <button
        v-ripple
        type="button"
        :class="[
          itemClass,
          isSidebarCollapsed ? 'justify-center px-2' : '',
          'text-surface-600 hover:bg-surface-100 hover:text-surface-900 dark:text-surface-300 dark:hover:bg-surface-800 dark:hover:text-surface-0',
        ]"
        :aria-label="t('User settings')"
        aria-haspopup="menu"
        :title="isSidebarCollapsed ? t('User settings') : undefined"
        @click="toggleUserMenu"
      >
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-600 text-sm font-semibold text-white">
          {{ userInitial }}
        </span>
        <span v-if="!isSidebarCollapsed" class="min-w-0 flex-1 truncate">{{ userName }}</span>
        <i v-if="!isSidebarCollapsed" class="fa fa-ellipsis-vertical shrink-0" aria-hidden="true" />
      </button>
      <Menu ref="userMenu" :model="userMenuItems" popup />
    </div>
  </aside>
</template>
