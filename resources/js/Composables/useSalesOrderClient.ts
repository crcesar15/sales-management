import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { SalesOrderHandoverPreview } from "@/Types/sales-order-types";

export function useSalesOrderClient() {
  const { apiClient, loading } = useApi();

  /**
   * Search product variants by name/identifier.
   * Returns enriched data including stock, price, brand, and sale units for AutoComplete suggestions.
   * When `storeId` is provided, stock is scoped to that store's active batches only.
   */
  const searchVariantsApi = async (filter: string, storeId?: number | null) => {
    if (!filter || filter.length < 2) return { data: [] };
    return await apiClient.get(route("api.v1.variants.search"), {
      params: {
        filter,
        includes: "saleUnits",
        ...(storeId ? { store_id: storeId } : {}),
      },
    });
  };

  /**
   * Fetch full variant details including sale units and pricing.
   * Used after a variant is selected from search results.
   */
  const fetchVariantDetailsApi = async (variantId: number) => {
    return await apiClient.get(route("api.v1.variants.search"), {
      params: {
        filter: String(variantId),
        filter_by: "id",
        per_page: 1,
        includes: "saleUnits",
      },
    });
  };

  const generateHandoverPreviewApi = async (salesOrderId: number) => {
    return await apiClient.get<{ data: SalesOrderHandoverPreview }>(route("api.v1.sales-orders.handover-preview", salesOrderId));
  };

  return {
    loading,
    searchVariantsApi,
    fetchVariantDetailsApi,
    generateHandoverPreviewApi,
  };
}
