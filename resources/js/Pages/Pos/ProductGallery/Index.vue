<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { Button, Message } from "primevue";
import { nextTick, shallowRef } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";
import PosLayout from "@layouts/pos.vue";
import { useProductGalleryClient } from "@composables/useProductGalleryClient";
import { route } from "ziggy-js";
import ProductGalleryFilterPanel from "./Components/ProductGalleryFilters.vue";
import ProductGalleryGrid from "./Components/ProductGalleryGrid.vue";
import ProductGalleryPreview from "./Components/ProductGalleryPreview.vue";
import type {
  ProductGalleryCollection,
  ProductGalleryFilterOptions,
  ProductGalleryFilters,
  ProductGalleryVariantDetail,
} from "@/Types/product-gallery-types";

defineOptions({ layout: PosLayout });

const props = defineProps<{
  variants: ProductGalleryCollection;
  filters: ProductGalleryFilters;
  filterOptions: ProductGalleryFilterOptions;
}>();

const { t } = useI18n();
const { fetchVariantDetail } = useProductGalleryClient();
const isNavigating = shallowRef(false);
const previewVisible = shallowRef(false);
const previewLoading = shallowRef(false);
const previewFailed = shallowRef(false);
const selectedVariantId = shallowRef<number | null>(null);
const detail = shallowRef<ProductGalleryVariantDetail | null>(null);
let detailController: AbortController | null = null;

function visit(filters: ProductGalleryFilters, page = 1): void {
  isNavigating.value = true;
  router.visit(route("pos.product-gallery.index"), {
    data: { ...filters, page },
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      isNavigating.value = false;
    },
  });
}

function applyFilters(filters: ProductGalleryFilters): void {
  visit(filters);
}

function changePage(page: number): void {
  visit(props.filters, page);
}

async function openPreview(variantId: number): Promise<void> {
  detailController?.abort();
  const controller = new AbortController();
  detailController = controller;
  selectedVariantId.value = variantId;
  previewVisible.value = true;
  previewLoading.value = true;
  previewFailed.value = false;
  detail.value = null;

  try {
    const response = await fetchVariantDetail(variantId, controller.signal);
    if (detailController === controller) detail.value = response;
  } catch (error) {
    if (!axios.isCancel(error) && detailController === controller) previewFailed.value = true;
  } finally {
    if (detailController === controller && !controller.signal.aborted) previewLoading.value = false;
  }
}

function closePreview(): void {
  detailController?.abort();
  previewLoading.value = false;
  detail.value = null;
  previewFailed.value = false;
  previewVisible.value = false;
  const variantId = selectedVariantId.value;
  selectedVariantId.value = null;

  if (variantId !== null) {
    void nextTick(() => document.querySelector<HTMLButtonElement>(`[data-gallery-card="${variantId}"]`)?.focus());
  }
}

function retryPreview(): void {
  if (selectedVariantId.value !== null) void openPreview(selectedVariantId.value);
}
</script>

<template>
  <div class="min-h-[calc(100dvh-3.5rem)] p-3 sm:p-4 lg:min-h-0 lg:p-6">
    <section class="w-full rounded-lg border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900">
      <div class="sticky top-14 z-10 rounded-t-lg border-b border-surface-200 bg-surface-0 p-4 shadow-sm dark:border-surface-700 dark:bg-surface-900 lg:top-0">
        <header class="mb-4 flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 class="m-0 text-2xl font-bold">{{ t("Product Gallery") }}</h1>
            <p class="m-0 mt-1 text-sm text-surface-500 dark:text-surface-400">{{ t("Availability is shown across all stores") }}</p>
          </div>

          <div class="flex shrink-0 gap-2">
            <Button type="submit" form="product-gallery-filters" :label="t('Search')" icon="fa fa-search" raised class="min-h-11" />
            <Button type="reset" form="product-gallery-filters" :label="t('Clear filters')" icon="fa fa-xmark" severity="secondary" outlined class="min-h-11" />
          </div>
        </header>

        <div :aria-label="t('Product filters')">
          <ProductGalleryFilterPanel :filters="filters" :options="filterOptions" @apply="applyFilters" />
        </div>
      </div>

      <div class="p-3 sm:p-4 lg:p-6">
        <Message v-if="isNavigating" severity="info" :closable="false" class="mb-4">{{ t("Updating products") }}</Message>

        <div class="mb-3 flex items-center justify-between gap-3">
          <p class="m-0 text-sm text-surface-600 dark:text-surface-300">{{ t("{count} products", { count: variants.meta.total }) }}</p>
        </div>

        <ProductGalleryGrid :variants="variants" :loading="isNavigating" @select="openPreview" @page="changePage" />
      </div>
    </section>

    <ProductGalleryPreview
      v-model:visible="previewVisible"
      :detail="detail"
      :loading="previewLoading"
      :failed="previewFailed"
      @closed="closePreview"
      @retry="retryPreview"
    />
  </div>
</template>
