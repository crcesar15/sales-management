import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";

export function useBatchClient() {
  const { apiClient, loading } = useApi();

  const fetchAvailableBatchesApi = async (productVariantId: number, storeId: number) => {
    return await apiClient.get(route("api.v1.batches.available"), {
      params: { product_variant_id: productVariantId, store_id: storeId },
    });
  };

  return {
    loading,
    fetchAvailableBatchesApi,
  };
}