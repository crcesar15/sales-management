import type { UserResponse } from "./user-types";
import type { SettingGrouped } from "./setting-types";

export interface AuthState {
  user: UserResponse | null;
  permissions: string[];
  settings: SettingGrouped;
}

export interface AuthData {
  user: UserResponse | null;
  permissions: string[];
  settings: SettingGrouped;
}
