import type { CustomerPayload } from "@/Types/customer-types";
import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { AxiosResponse } from "axios";

export function useCustomerClient() {
  const { apiClient, loading } = useApi();

  const searchCustomersApi = async <T = unknown>(term: string): Promise<AxiosResponse<T>> => {
    return await apiClient.get(route("api.v1.customers.search"), {
      params: { q: term },
    });
  };

  const findByTaxIdApi = async <T = unknown>(taxId: string): Promise<AxiosResponse<T>> => {
    return await apiClient.get(route("api.v1.customers.find-by-tax-id"), {
      params: { tax_id: taxId },
    });
  };

  const fetchCustomersApi = async <T = unknown>(queryParameters?: string): Promise<AxiosResponse<T>> => {
    let url: string = route("api.v1.customers");
    if (queryParameters) {
      url += `?${queryParameters}`;
    }
    return await apiClient.get(url);
  };

  const storeCustomerApi = async (customer: CustomerPayload): Promise<AxiosResponse> => {
    return await apiClient.post(route("api.v1.customers.store"), customer);
  };

  const updateCustomerApi = async (id: number, customer: CustomerPayload): Promise<AxiosResponse> => {
    return await apiClient.put(route("api.v1.customers.update", id), customer);
  };

  const destroyCustomerApi = async (id: number): Promise<AxiosResponse> => {
    return await apiClient.delete(route("api.v1.customers.destroy", id));
  };

  return {
    loading,
    searchCustomersApi,
    findByTaxIdApi,
    fetchCustomersApi,
    storeCustomerApi,
    updateCustomerApi,
    destroyCustomerApi,
  };
}