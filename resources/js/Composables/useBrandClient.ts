import { Brand } from "@app-types/brand-types";
import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import { AxiosResponse } from "axios";

export function useBrandClient() {
    const { apiClient, loading }  = useApi();

    const fetchBrandsApi = async <T = any>(queryParameters?: string):Promise<AxiosResponse<T>>  => {
        let url:string = route('api.v1.brands');

        if (queryParameters) {
            url += `?${queryParameters}`
        }

        return await apiClient.get(url)
    }

    const showBrandApi = async (id: number) => {
        return await apiClient.get(route('api.v1.brands.show', id));
    }

    const storeBrandApi = async (brand:Pick<Brand, 'name'>) => {
        return await apiClient.post(route('api.v1.brands.store'), brand);
    }

    const updateBrandApi = async (id:number, brand:Brand) => {
        return await apiClient.put(route('api.v1.brands.update', id), brand);
    }

    const destroyBrandApi = async (id:number) => {
        return await apiClient.delete(route('api.v1.brands.destroy', id));
    }

    return {
        loading,
        fetchBrandsApi,
        showBrandApi,
        storeBrandApi,
        updateBrandApi,
        destroyBrandApi,
    }
}