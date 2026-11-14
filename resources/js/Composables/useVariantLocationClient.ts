import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";

export function useVariantLocationClient() {
  const { apiClient } = useApi();

  async function searchLocationCodes(storeId: number, search: string, signal: AbortSignal): Promise<string[]> {
    const response = await apiClient.get<{ data: string[] }>(route("api.v1.stores.location-codes.index", { store: storeId }), {
      params: { search }, signal,
    });
    return response.data.data;
  }

  return { searchLocationCodes };
}
