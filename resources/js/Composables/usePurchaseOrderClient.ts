import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { AxiosResponse } from "axios";

export function usePurchaseOrderClient() {
  const { apiClient, loading } = useApi();

  const searchVendorsApi = async (query: string): Promise<AxiosResponse> => {
    return await apiClient.get(route("api.v1.vendors"), {
      params: {
        filter: query,
        per_page: 15,
        status: "active",
        order_by: "fullname",
        order_direction: "asc",
      },
    });
  };

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
    searchVendorsApi,
    fetchVendorCatalogApi,
  };
}