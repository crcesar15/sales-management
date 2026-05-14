import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { AxiosResponse } from "axios";

export function usePurchaseOrderClient() {
  const { apiClient, loading } = useApi();

  const fetchVendorCatalogApi = async (
    vendorId: number,
    query?: string,
  ): Promise<AxiosResponse> => {
    const params: Record<string, unknown> = {
      per_page: 50,
      status: "active",
    };
    if (query) {
      params.filter = query;
    }
    return await apiClient.get(
      route("api.v1.vendors.variants", { vendor: vendorId }),
      { params },
    );
  };

  return {
    loading,
    fetchVendorCatalogApi,
  };
}