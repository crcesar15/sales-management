import type { User } from "./user-types";
import type { SettingBase } from "./setting-types";

export interface AuthState {
  user: User | null;
  permissions: string[];
  settings: SettingBase[];
}

export interface AuthData {
  user: User | null;
  permissions: string[];
  settings: SettingBase[];
}
