import { type UserResponse } from "./user-types";

interface Store {
  name: string;
  code: string;
  address: string | null;
  city: string | null;
  state: string | null;
  zip_code: string | null;
  phone: string | null;
  email: string | null;
  status: string;
}

interface StoreResponse extends Store {
  id: number;
  users_count?: number;
  users?: UserResponse[];
  deleted_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export { type Store, type StoreResponse };
