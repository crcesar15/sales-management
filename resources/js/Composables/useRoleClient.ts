import { Role } from "@app-types/role-types";
import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import { AxiosResponse } from "axios";

export function useRoleClient() {
    const { apiClient, loading }  = useApi();

    const fetchRolesApi = async <T = any>(queryParameters?: string):Promise<AxiosResponse<T>>  => {
        let url:string = route('api.roles');

        if (queryParameters) {
            url += `?${queryParameters}`
        }

        return await apiClient.get(url)
    }

    const showRoleApi = async (id: number) => {
        return await apiClient.get(route('api.roles.show', id));
    }

    const storeRoleApi = async (role:Pick<Role, 'name'>) => {
        return await apiClient.post(route('api.roles.store'), role);
    }

    const updateRoleApi = async (id:number, role:Role) => {
        return await apiClient.put(route('api.roles.update', id), role);
    }

    const destroyRoleApi = async (id:number) => {
        return await apiClient.delete(route('api.roles.destroy', id));
    }

    return {
        loading,
        fetchRolesApi,
        showRoleApi,
        storeRoleApi,
        updateRoleApi,
        destroyRoleApi,
    }
}