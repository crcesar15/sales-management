import { Brand } from "../Types/brand-types";
import { useApi } from "./useApi";
import { route } from "ziggy-js";
import { AxiosResponse } from "axios";

export function useBrandsClient() {
    const { apiClient, loading }  = useApi();

    const fetchBrandsApi = async <T = any>(queryParameters?: string):Promise<AxiosResponse<T>>  => {
        let url:string = route('api.brands');

        if (queryParameters) {
            url += `?${queryParameters}`
        }

        return await apiClient.get(url)
    }

    const showBrandApi = async (id: number) => {
        return await apiClient.get(route('api.brands.show', id));
    }

    const storeBrandApi = async (brand:Pick<Brand, 'name'>) => {
        return await apiClient.post(route('api.brands.store'), brand);
    }

    const updateBrandApi = async (id:number, brand:Brand) => {
        return await apiClient.put(route('api.brands.update', id), brand);
    }

    const destroyBrandApi = async (id:number) => {
        return await apiClient.delete(route('api.brands.destroy', id));
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