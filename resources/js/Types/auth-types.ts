import type { UserResponse } from "./user-types";
import type { SettingGrouped } from "./setting-types";

export interface AuthState {
  user: UserResponse & { permissions: string[] } | null;
  settings: SettingGrouped;
}

export interface AuthData {
  user: UserResponse & { permissions: string[] } | null;
  settings: SettingGrouped;
}
