<script setup lang="ts">
import { Paginator } from "primevue";
import type { PageState } from "primevue/paginator";
import { useI18n } from "vue-i18n";
import ProductGalleryCard from "./ProductGalleryCard.vue";
import type { ProductGalleryCollection } from "@/Types/product-gallery-types";

defineProps<{
  variants: ProductGalleryCollection;
  loading: boolean;
}>();

const emit = defineEmits<{
  select: [variantId: number];
  page: [page: number];
}>();

const { t } = useI18n();

function onPage(event: PageState): void {
  emit("page", event.page + 1);
}
</script>

<template>
  <div aria-live="polite">
    <div v-if="loading" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6" :aria-label="t('Loading')">
      <div v-for="index in 12" :key="index" class="overflow-hidden rounded-lg border border-surface-200 dark:border-surface-700">
        <div class="aspect-[4/3] animate-pulse bg-surface-100 dark:bg-surface-800" />
        <div class="h-28 animate-pulse bg-surface-50 dark:bg-surface-900" />
      </div>
    </div>

    <div v-else-if="variants.data.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
      <ProductGalleryCard v-for="variant in variants.data" :key="variant.id" :variant="variant" @select="emit('select', $event)" />
    </div>

    <div v-else class="flex min-h-72 flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-surface-300 p-6 text-center text-surface-500 dark:border-surface-600 dark:text-surface-400">
      <i class="fa fa-box-open text-3xl" aria-hidden="true" />
      <p class="m-0 font-medium">{{ t("No products found") }}</p>
      <p class="m-0 text-sm">{{ t("Try adjusting your search or filters") }}</p>
    </div>

    <Paginator
      v-if="variants.meta.total > variants.meta.per_page"
      class="mt-5"
      :rows="variants.meta.per_page"
      :first="(variants.meta.current_page - 1) * variants.meta.per_page"
      :total-records="variants.meta.total"
      :page-link-size="3"
      @page="onPage"
    />
  </div>
</template>
