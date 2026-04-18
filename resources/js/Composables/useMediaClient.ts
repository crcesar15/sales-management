import { useApi } from "./useApi";
import { route } from "ziggy-js";
import type { AxiosResponse } from "axios";

export const useMediaClient = () => {
  const { apiClient, loading } = useApi();

  const uploadDraftFile = async (file: FormData): Promise<AxiosResponse> => {
    return await apiClient.post(route("api.v1.media.draft.store"), file, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  };

  const destroyDraftFile = async (id: number): Promise<AxiosResponse> => {
    return await apiClient.delete(route("api.v1.media.draft.destroy", id));
  };

  return {
    uploadDraftFile,
    destroyDraftFile,
    loading,
  };
};
