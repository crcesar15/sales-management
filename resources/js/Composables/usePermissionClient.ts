import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { AxiosResponse } from "axios";

export function usePermissionClient() {
  const { apiClient, loading } = useApi();

  const fetchPermissionsApi = async <T = unknown>(queryParameters?: string): Promise<AxiosResponse<T>> => {
    let url: string = route("api.v1.permissions");

    if (queryParameters) {
      url += `?${queryParameters}`;
    }

    return await apiClient.get(url);
  };

  return {
    loading,
    fetchPermissionsApi,
  };
}
