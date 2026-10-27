import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { ProductGalleryVariantDetail } from "@/Types/product-gallery-types";

export function useProductGalleryClient() {
  const { apiClient, loading } = useApi();

  async function fetchVariantDetail(variantId: number, signal?: AbortSignal): Promise<ProductGalleryVariantDetail> {
    const { data } = await apiClient.get<{ data: ProductGalleryVariantDetail }>(route("api.v1.pos.product-gallery.show", variantId), { signal });

    return data.data;
  }

  return { loading, fetchVariantDetail };
}
