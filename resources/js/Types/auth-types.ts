import type { User } from "./user-types";

export interface AuthState {
  user: User | null;
  permissions: string[];
}

export interface AuthData {
  user: User | null;
  permissions: string[];
}
