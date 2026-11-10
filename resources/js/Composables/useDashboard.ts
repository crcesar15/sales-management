import { router, usePoll } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, shallowRef } from "vue";
import { route } from "ziggy-js";
import type { DashboardData, DashboardFilters } from "@/Types/dashboard-types";

export function useDashboard(snapshot: () => DashboardData) {
  const loading = shallowRef(false);
  const stale = shallowRef(false);
  const accessLost = shallowRef(false);
  const errors = shallowRef<Record<string, string>>({});
  let cancel: (() => void) | undefined;
  const callbacks = {
    only: ["dashboard", "stores", "auth"],
    preserveScroll: true,
    onBefore: () => !loading.value,
    onStart: () => {
      loading.value = true;
    },
    onSuccess: () => {
      stale.value = false;
      accessLost.value = false;
      errors.value = {};
    },
    onError: (validation: Record<string, string>) => {
      errors.value = validation;
      stale.value = true;
    },
    onCancelToken: (token: { cancel: () => void }) => {
      cancel = token.cancel;
    },
    onFinish: () => {
      loading.value = false;
      cancel = undefined;
    },
  };
  const poll = usePoll(300_000, callbacks, { autoStart: false });

  function refresh() {
    if (!loading.value) router.reload(callbacks);
  }

  function apply(filters: DashboardFilters, storePage = 1) {
    cancel?.();
    loading.value = false;
    router.get(
      route("home"),
      {
        store_id: filters.store_id,
        preset: filters.preset,
        comparison: filters.comparison,
        ...(filters.preset === "custom" ? { from: filters.from, to: filters.to } : {}),
        store_page: storePage,
      },
      { ...callbacks, preserveState: true, replace: true },
    );
  }

  function visibilityChanged() {
    if (document.hidden) poll.stop();
    else {
      if (Date.now() - Date.parse(snapshot().generated_at) >= 300_000) refresh();
      poll.start();
    }
  }

  const offInvalid = router.on("invalid", (event) => {
    if (!loading.value) return;
    event.preventDefault();
    stale.value = true;
    if (event.detail.response.status === 401 || event.detail.response.status === 403) accessLost.value = true;
    const request: unknown = event.detail.response.request;
    if (
      request instanceof XMLHttpRequest &&
      request.responseURL &&
      new URL(request.responseURL).pathname !== new URL(route("home")).pathname
    ) {
      accessLost.value = true;
    }
  });
  const offException = router.on("exception", (event) => {
    if (!loading.value) return;
    event.preventDefault();
    stale.value = true;
    loading.value = false;
  });

  onMounted(() => {
    document.addEventListener("visibilitychange", visibilityChanged);
    visibilityChanged();
  });
  onUnmounted(() => {
    cancel?.();
    poll.stop();
    offInvalid();
    offException();
    document.removeEventListener("visibilitychange", visibilityChanged);
  });

  return {
    loading,
    stale,
    accessLost,
    errors,
    refresh,
    apply,
    hasWidgets: computed(() =>
      Object.entries(snapshot().capabilities).some(([key, allowed]) => ["sales", "inventory", "shifts"].includes(key) && allowed),
    ),
  };
}
