import { defineStore } from "pinia";
import { computed, ref } from "vue";
import type { UserResponse } from "@app-types/user-types";
import type { AuthData } from "@app-types/auth-types";
import type { SettingGrouped } from "@app-types/setting-types";

export const useAuthStore = defineStore("auth", () => {
  // State
  const user = ref<UserResponse | null>(null);
  const permissions = ref<string[]>([]);
  const settings = ref<SettingGrouped>({});

  // Getters
  const isAuthenticated = computed(() => user.value !== null);
  const isGuest = computed(() => user.value === null);
  const userFullName = computed(() => user.value?.full_name ?? "");
  const systemSettings = computed(() => settings.value);
  const getSetting = (group: string, key: string, defaultValue: string | null = null): string | null => {
    return settings.value[group]?.[key] ?? defaultValue;
  };

  // Actions
  function setAuth(authData: AuthData) {
    user.value = authData.user;
    permissions.value = authData.user?.permissions || [];
    settings.value = authData.settings;
  }

  function clearAuth() {
    user.value = null;
    permissions.value = [];
    settings.value = {};
  }

  /**
   * Check if user has a specific permission
   */
  function can(permission: string): boolean {
    return permissions.value.includes(permission);
  }

  /**
   * Check if user has ANY of the given permissions
   */
  function canAny(permissionList: string[]): boolean {
    return permissionList.some((p) => permissions.value.includes(p));
  }

  /**
   * Check if user has ALL of the given permissions
   */
  function canAll(permissionList: string[]): boolean {
    return permissionList.every((p) => permissions.value.includes(p));
  }

  return {
    // State
    user,
    permissions,
    settings,
    // Getters
    isAuthenticated,
    isGuest,
    userFullName,
    systemSettings,
    getSetting,
    // Actions
    setAuth,
    clearAuth,
    can,
    canAny,
    canAll,
  };
});
