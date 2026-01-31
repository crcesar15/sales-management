import { defineStore } from "pinia";
import { computed, ref } from "vue";
import type { User } from "@app-types/user-types";
import type { AuthData } from "@app-types/auth-types";

export const useAuthStore = defineStore("auth", () => {
  // State
  const user = ref<User | null>(null);
  const permissions = ref<string[]>([]);

  // Getters
  const isAuthenticated = computed(() => user.value !== null);
  const isGuest = computed(() => user.value === null);
  const userFullName = computed(() => user.value?.full_name ?? "");

  // Actions
  function setAuth(authData: AuthData | null) {
    user.value = authData?.user ?? null;
    permissions.value = authData?.permissions ?? [];
  }

  function clearAuth() {
    user.value = null;
    permissions.value = [];
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
    // Getters
    isAuthenticated,
    isGuest,
    userFullName,
    // Actions
    setAuth,
    clearAuth,
    can,
    canAny,
    canAll,
  };
});
