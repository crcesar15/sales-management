import { defineStore } from "pinia";
import { computed, ref } from "vue";
import type { User } from "@app-types/user-types";
import type { AuthData } from "@app-types/auth-types";
import type { SettingBase } from "@app-types/setting-types";

export const useAuthStore = defineStore("auth", () => {
  // State
  const user = ref<User | null>(null);
  const permissions = ref<string[]>([]);
  const settings = ref<SettingBase[]>([]);

  // Getters
  const isAuthenticated = computed(() => user.value !== null);
  const isGuest = computed(() => user.value === null);
  const userFullName = computed(() => user.value?.full_name ?? "");
  const systemSettings = computed(() => settings.value);
  const getSetting = (key: string, defaultValue: string | number | boolean | null = null) => {
    const setting = settings.value.find((s) => s.key === key);
    return setting?.value ?? defaultValue;
  };

  // Actions
  function setAuth(authData: AuthData | null) {
    user.value = authData?.user ?? null;
    permissions.value = authData?.permissions ?? [];
    settings.value = authData?.settings ?? [];
  }

  function clearAuth() {
    user.value = null;
    permissions.value = [];
    settings.value = [];
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
