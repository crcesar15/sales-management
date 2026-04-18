import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { UserResponse } from "@app-types/user-types";
import type { SettingGrouped } from "@app-types/setting-types";

type UserWithPermissions = UserResponse & { permissions: string[] };

export function useAuth() {
  const page = usePage();

  const user = computed<UserWithPermissions | null>(
    () => (page.props.auth as unknown as { user: UserWithPermissions | null })?.user ?? null,
  );

  const permissions = computed<string[]>(() => user.value?.permissions ?? []);

  const settings = computed<SettingGrouped>(() => (page.props.auth as unknown as { settings: SettingGrouped })?.settings ?? {});

  const isAuthenticated = computed(() => user.value !== null);
  const isGuest = computed(() => user.value === null);
  const userFullName = computed(() => user.value?.full_name ?? "");

  const getSetting = (group: string, key: string, defaultValue: string | null = null): string | null => {
    return settings.value[group]?.[key] ?? defaultValue;
  };

  function can(permission: string): boolean {
    return permissions.value.includes(permission);
  }

  function canAny(permissionList: string[]): boolean {
    return permissionList.some((p) => permissions.value.includes(p));
  }

  function canAll(permissionList: string[]): boolean {
    return permissionList.every((p) => permissions.value.includes(p));
  }

  return {
    user,
    permissions,
    settings,
    isAuthenticated,
    isGuest,
    userFullName,
    getSetting,
    can,
    canAny,
    canAll,
  };
}
