<script setup lang="ts">
import { Tag } from "primevue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import type { ProductGalleryVariant } from "@/Types/product-gallery-types";

const props = defineProps<{
  variant: ProductGalleryVariant;
}>();

const emit = defineEmits<{
  select: [variantId: number];
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const fallbackLabel = computed(() => props.variant.product_name.slice(0, 2).toUpperCase());
</script>

<template>
  <button
    type="button"
    :data-gallery-card="variant.id"
    class="group flex min-h-0 w-full flex-col overflow-hidden rounded-lg border border-surface-200 bg-surface-0 text-left transition-colors hover:border-primary-300 hover:bg-surface-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:border-surface-700 dark:bg-surface-900 dark:hover:border-primary-600 dark:hover:bg-surface-800"
    :aria-label="t('View {product}', { product: variant.product_name })"
    @click="emit('select', variant.id)"
  >
    <div class="relative h-40 w-full bg-surface-100 sm:h-44 dark:bg-surface-800">
      <img
        v-if="variant.thumbnail_url"
        :src="variant.thumbnail_url"
        :alt="variant.product_name"
        class="block h-full w-full object-contain"
        loading="lazy"
      />
      <div v-else class="flex h-full w-full items-center justify-center text-4xl font-semibold text-surface-400 dark:text-surface-500">
        {{ fallbackLabel }}
      </div>
      <Tag
        :value="variant.is_available ? t('In stock') : t('Out of stock')"
        :severity="variant.is_available ? 'success' : 'danger'"
        class="absolute right-2 top-2"
      />
    </div>

    <div class="flex min-h-28 flex-1 flex-col gap-1.5 p-3">
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
          <p class="m-0 line-clamp-2 font-semibold text-surface-900 dark:text-surface-0">{{ variant.product_name }}</p>
          <p v-if="variant.option_values.length" class="m-0 line-clamp-1 text-sm text-surface-600 dark:text-surface-300">
            {{ variant.option_values.join(" · ") }}
          </p>
        </div>

        <div v-if="variant.brand_name || variant.unit_name" class="max-w-[45%] shrink-0 space-y-1 text-xs text-surface-500 dark:text-surface-400">
          <p v-if="variant.brand_name" class="m-0 flex items-center justify-end gap-1.5 truncate">
            <i class="fa fa-building w-4 text-center" aria-hidden="true" />
            <span class="sr-only">{{ t("Brand") }}:</span>
            <span class="truncate">{{ variant.brand_name }}</span>
          </p>
          <p v-if="variant.unit_name" class="m-0 flex items-center justify-end gap-1.5 truncate">
            <i class="fa fa-weight-hanging w-4 text-center" aria-hidden="true" />
            <span class="sr-only">{{ t("Unit") }}:</span>
            <span class="truncate">{{ variant.unit_name }}</span>
          </p>
        </div>
      </div>
      <div class="mt-auto flex items-end justify-between gap-2 pt-1">
        <span class="text-lg font-semibold text-surface-900 dark:text-surface-0">{{ formatCurrency(String(variant.price)) }}</span>
      </div>
    </div>
  </button>
</template>
