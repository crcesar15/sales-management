import type { AxiosResponse } from "axios";
import type { PurchasePriceHistory } from "@/Types/inventory-variant-types";
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

  const fetchPurchasePriceHistory = async (variantId: number): Promise<AxiosResponse<{ data: PurchasePriceHistory }>> => {
    return await apiClient.get(route("api.v1.variants.purchase-price-history", variantId));
  };

  return {
    loading,
    searchVariantsApi,
    fetchVariantPurchaseUnitsApi,
    fetchPurchasePriceHistory,
  };
}
