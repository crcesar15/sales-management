import { SettingPayload } from "@app-types/setting-types";
import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import { AxiosResponse } from "axios";

export function useSettingClient() {
    const { apiClient, loading }  = useApi();

    const fetchSettingsApi = async <T = any>(queryParameters?: string):Promise<AxiosResponse<T>>  => {
        let url:string = route('api.v1.settings');

        if (queryParameters) {
            url += `?${queryParameters}`
        }

        return await apiClient.get(url)
    }

    const updateSettingApi = async (settings:SettingPayload[]) => {
        return await apiClient.put(route('api.v1.settings.update'), { settings });
    }

    return {
        loading,
        fetchSettingsApi,
        updateSettingApi,
    }
}