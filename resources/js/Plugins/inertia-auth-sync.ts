import { router } from "@inertiajs/vue3";
import type { App } from "vue";
import { useAuthStore } from "@stores/auth";
import type { AuthData } from "@app-types/auth-types";

export const inertiaAuthSyncPlugin = {
  install(_app: App) {
    const authStore = useAuthStore();

    // Sync auth state on every Inertia navigation success
    router.on("success", (event) => {
      const auth = event.detail.page.props.auth as AuthData | null;
      authStore.setAuth(auth);
    });
  },
};
