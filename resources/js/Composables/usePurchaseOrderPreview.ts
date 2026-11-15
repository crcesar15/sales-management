import { shallowRef, toValue, watch, type MaybeRefOrGetter } from "vue";
import axios from "axios";
import { route } from "ziggy-js";
import { useApi } from "@composables/useApi";
import type { PurchaseDiscountType, PurchaseOrderFinancialPreview } from "@app-types/purchase-order-types";

interface PreviewLine {
  product_variant_id: number;
  unit_id?: number | null;
  quantity: number;
  price: number;
  discount_type: PurchaseDiscountType;
  discount_value: number | null;
}

export function usePurchaseOrderPreview(
  items: MaybeRefOrGetter<PreviewLine[]>,
  discountType: MaybeRefOrGetter<PurchaseDiscountType>,
  discountValue: MaybeRefOrGetter<number | null | undefined>,
) {
  const { apiClient } = useApi();
  const preview = shallowRef<PurchaseOrderFinancialPreview | null>(null);
  const loading = shallowRef(false);
  const failed = shallowRef(false);
  const errors = shallowRef<Record<string, string>>({});
  watch(
    () =>
      JSON.stringify({
        items: toValue(items).map((item) => ({ ...item, discount_value: item.discount_value ?? 0 })),
        discount_type: toValue(discountType),
        discount_value: toValue(discountValue) ?? 0,
      }),
    (serialized, _previous, onCleanup) => {
      const data = JSON.parse(serialized) as { items: PreviewLine[] };
      const controller = new AbortController();
      preview.value = null;
      failed.value = false;
      errors.value = {};
      loading.value = data.items.length > 0;
      const timer = setTimeout(async () => {
        if (!data.items.length) return;
        try {
          const response = await apiClient.post<{ data: PurchaseOrderFinancialPreview }>(route("api.v1.purchase-orders.preview"), data, {
            signal: controller.signal,
          });
          if (!controller.signal.aborted) preview.value = response.data.data;
        } catch (error) {
          if (controller.signal.aborted || axios.isCancel(error)) return;
          failed.value = true;
          if (axios.isAxiosError<{ errors?: Record<string, string[]> }>(error)) {
            errors.value = Object.fromEntries(
              Object.entries(error.response?.data.errors ?? {}).map(([key, messages]) => [key, messages[0]]),
            );
          }
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
  return { preview, loading, failed, errors };
}
