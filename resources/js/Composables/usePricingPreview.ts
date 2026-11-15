import { shallowRef, toValue, watch, type MaybeRefOrGetter } from "vue";
import axios from "axios";
import { route } from "ziggy-js";
import { useApi } from "@composables/useApi";
import type { PricingPreview, PricingPreviewItem } from "@app-types/pricing-types";

export function usePricingPreview(items: MaybeRefOrGetter<PricingPreviewItem[]>) {
  const { apiClient } = useApi();
  const results = shallowRef<PricingPreview[]>([]);
  const loading = shallowRef(false);
  const failed = shallowRef(false);

  watch(
    () => JSON.stringify(toValue(items)),
    (serialized, _previous, onCleanup) => {
      const payload = JSON.parse(serialized) as PricingPreviewItem[];
      results.value = [];
      failed.value = false;
      loading.value = payload.length > 0;
      const controller = new AbortController();
      const timer = setTimeout(async () => {
        if (!payload.length) return;
        try {
          const response = await apiClient.post<{ data: PricingPreview[] }>(
            route("inventory.pricing.preview"),
            { items: payload },
            { signal: controller.signal },
          );
          if (!controller.signal.aborted) results.value = response.data.data;
        } catch (error) {
          if (!axios.isCancel(error) && !controller.signal.aborted) failed.value = true;
        } finally {
          if (!controller.signal.aborted) loading.value = false;
        }
      }, 300);
      onCleanup(() => {
        clearTimeout(timer);
        controller.abort();
      });
    },
    { immediate: true },
  );

  return { results, loading, failed };
}
