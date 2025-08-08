import { Brand } from "../Types/brand-types";
import { useApi } from "./useApi";
import { route } from "ziggy-js";
import { AxiosResponse } from "axios";

export function useBrandsAPI() {
    const { apiClient, loading }  = useApi();

    const fetchBrandsAPI = async <T = any>(queryParameters?: string):Promise<AxiosResponse<T>>  => {
        let url:string = route('api.brands');

        if (queryParameters) {
            url += `?${queryParameters}`
        }

        return await apiClient.get(url)
    }

    const showBrandAPI = async (id: number) => {
        return await apiClient.get(route('api.brands.show', id));
    }

    const storeBrandAPI = async (brand:Pick<Brand, 'name'>) => {
        return await apiClient.post(route('api.brands.store'), brand);
    }

    const updateBrandAPI = async (id:number, brand:Brand) => {
        return await apiClient.put(route('api.brands.update', id), brand);
    }

    const destroyBrandAPI = async (id:number) => {
        return await apiClient.delete(route('api.brands.destroy', id));
    }

    return {
        loading,
        fetchBrandsAPI,
        showBrandAPI,
        storeBrandAPI,
        updateBrandAPI,
        destroyBrandAPI,
    }
}