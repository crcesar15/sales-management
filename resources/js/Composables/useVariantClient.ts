import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";

export function useVariantClient() {
  const { apiClient, loading } = useApi();

  const searchVariantsApi = async (filter: string) => {
    return await apiClient.get(route("api.v1.variants.search"), {
      params: { filter },
    });
  };

  const fetchVariantPurchaseUnitsApi = async (variantId: number) => {
    return await apiClient.get(route("api.v1.variants.purchase-units", variantId));
  };

  return {
    loading,
    searchVariantsApi,
    fetchVariantPurchaseUnitsApi,
  };
}
