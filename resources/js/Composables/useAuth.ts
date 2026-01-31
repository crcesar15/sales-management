import { storeToRefs } from "pinia";
import { useAuthStore } from "@stores/auth";

export function useAuth() {
  const store = useAuthStore();
  const { user, permissions, isAuthenticated, isGuest, userFullName } = storeToRefs(store);

  return {
    // Reactive state (refs)
    user,
    permissions,
    isAuthenticated,
    isGuest,
    userFullName,
    // Methods
    can: store.can,
    canAny: store.canAny,
    canAll: store.canAll,
    setAuth: store.setAuth,
    clearAuth: store.clearAuth,
  };
}
