import type { Product } from "@app-types/product-types";
import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { AxiosResponse } from "axios";

export function useProductClient() {
  const { apiClient, loading } = useApi();

  const fetchProductsApi = async <T = unknown>(queryParameters?: string): Promise<AxiosResponse<T>> => {
    let url: string = route("api.v1.products");

    if (queryParameters) {
      url += `?${queryParameters}`;
    }

    return await apiClient.get(url);
  };

  const showProductApi = async (id: number) => {
    return await apiClient.get(route("api.v1.products.show", id));
  };

  const storeProductApi = async (product: Pick<Product, "name">) => {
    return await apiClient.post(route("api.v1.products.store"), product);
  };

  const updateProductApi = async (id: number, product: Product) => {
    return await apiClient.put(route("api.v1.products.update", id), product);
  };

  const destroyProductApi = async (id: number) => {
    return await apiClient.delete(route("api.v1.products.destroy", id));
  };

  return {
    loading,
    fetchProductsApi,
    showProductApi,
    storeProductApi,
    updateProductApi,
    destroyProductApi,
  };
}
