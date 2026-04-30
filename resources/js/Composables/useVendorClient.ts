import type { VendorPayload } from "@app-types/vendor-types";
import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { AxiosResponse } from "axios";

export function useVendorClient() {
  const { apiClient, loading } = useApi();

  const fetchVendorsApi = async <T = unknown>(queryParameters?: string): Promise<AxiosResponse<T>> => {
    let url: string = route("api.v1.vendors");

    if (queryParameters) {
      url += `?${queryParameters}`;
    }

    return await apiClient.get(url);
  };

  const showVendorApi = async (id: number) => {
    return await apiClient.get(route("api.v1.vendors.show", id));
  };

  const storeVendorApi = async (vendor: VendorPayload) => {
    return await apiClient.post(route("api.v1.vendors.store"), vendor);
  };

  const updateVendorApi = async (id: number, vendor: VendorPayload) => {
    return await apiClient.put(route("api.v1.vendors.update", id), vendor);
  };

  const destroyVendorApi = async (id: number) => {
    return await apiClient.delete(route("api.v1.vendors.destroy", id));
  };

  return {
    loading,
    fetchVendorsApi,
    showVendorApi,
    storeVendorApi,
    updateVendorApi,
    destroyVendorApi,
  };
}
