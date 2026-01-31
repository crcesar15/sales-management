import { DraftUser, User } from "@app-types/user-types";
import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import { AxiosResponse } from "axios";

export function useUserClient() {
  const { apiClient, loading }  = useApi();

  const fetchUsersApi = async <T = any>(queryParameters?: string): Promise<AxiosResponse<T>> => {
    let url: string = route('api.v1.users');

    if (queryParameters) {
      url += `?${queryParameters}`;
    }

    return await apiClient.get(url);
  };

  const showUserApi = async (id: number) => {
    return await apiClient.get(route('api.v1.users.show', id));
  };

  const createUserApi = async (user: DraftUser) => {
    return await apiClient.post(route('api.v1.users.store'), user);
  };

  const updateUserApi = async (id: number, user: DraftUser) => {
    return await apiClient.put(route('api.v1.users.update', id), user);
  };

  const destroyUserApi = async (id: number) => {
    return await apiClient.delete(route('api.v1.users.destroy', id));
  };

  const restoreUserApi = async (id: number) => {
    return await apiClient.put(route('api.v1.users.restore', id));
  };

  return {
    loading,
    fetchUsersApi,
    showUserApi,
    createUserApi,
    updateUserApi,
    destroyUserApi,
    restoreUserApi
  };
}