import { Category } from "@app-types/category-types";
import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import { AxiosResponse } from "axios";

export function useCategoryClient() {
    const { apiClient, loading }  = useApi();

    const fetchCategoriesApi = async <T = any>(queryParameters?: string):Promise<AxiosResponse<T>>  => {
        let url:string = route('api.v1.categories');

        if (queryParameters) {
            url += `?${queryParameters}`
        }

        return await apiClient.get(url)
    }

    const showCategoryApi = async (id: number) => {
        return await apiClient.get(route('api.v1.categories.show', id));
    }

    const storeCategoryApi = async (brand:Pick<Category, 'name'>) => {
        return await apiClient.post(route('api.v1.categories.store'), brand);
    }

    const updateCategoryApi = async (id:number, brand:Category) => {
        return await apiClient.put(route('api.v1.categories.update', id), brand);
    }

    const destroyCategoryApi = async (id:number) => {
        return await apiClient.delete(route('api.v1.categories.destroy', id));
    }

    const restoreCategoryApi = async (id:number) => {
        return await apiClient.put(route('api.v1.categories.restore', id));
    }

    return {
        loading,
        fetchCategoriesApi,
        showCategoryApi,
        storeCategoryApi,
        updateCategoryApi,
        destroyCategoryApi,
        restoreCategoryApi,
    }
}