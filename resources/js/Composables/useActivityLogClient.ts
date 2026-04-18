import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { AxiosResponse } from "axios";

export function useActivityLogClient() {
  const { apiClient, loading } = useApi();

  const fetchActivityLogsApi = async <T = unknown>(queryParameters?: string): Promise<AxiosResponse<T>> => {
    let url: string = route("api.v1.activity-logs");

    if (queryParameters) {
      url += `?${queryParameters}`;
    }

    return await apiClient.get(url);
  };

  return {
    loading,
    fetchActivityLogsApi,
  };
}
