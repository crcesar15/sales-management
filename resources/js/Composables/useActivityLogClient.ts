import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { AxiosResponse } from "axios";
import type { ActivityLog } from "@/Types/activity-log-types";

export function useActivityLogClient() {
  const { apiClient, loading } = useApi();

  const fetchActivityLogsApi = async (
    queryParameters?: string,
  ): Promise<AxiosResponse<{ data: ActivityLog[]; meta: { total: number } }>> => {
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
