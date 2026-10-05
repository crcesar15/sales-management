import type { AxiosResponse } from "axios";
import type { CatalogVariantSearchResult } from "@/Types/catalog-types";
import type { PurchasePriceHistory } from "@/Types/inventory-variant-types";
import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";

interface VariantSearchOptions {
  includes?: string[];
  storeId?: number | null;
}

export function useVariantClient() {
  const { apiClient, loading } = useApi();

  const searchVariantsApi = async <T = CatalogVariantSearchResult>(
    filter: string,
    { includes = [], storeId = null }: VariantSearchOptions = {},
  ): Promise<AxiosResponse<{ data: T[] }>> => {
    return await apiClient.get<{ data: T[] }>(route("api.v1.variants.search"), {
      params: {
        filter,
        ...(includes.length > 0 ? { includes: includes.join(",") } : {}),
        ...(storeId !== null ? { store_id: storeId } : {}),
      },
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
