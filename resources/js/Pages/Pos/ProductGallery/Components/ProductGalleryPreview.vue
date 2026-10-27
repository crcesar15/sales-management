<script setup lang="ts">
import { Button, Dialog, Galleria, Message, Tag } from "primevue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import type { ProductGalleryVariantDetail } from "@/Types/product-gallery-types";

const props = defineProps<{
  detail: ProductGalleryVariantDetail | null;
  loading: boolean;
  failed: boolean;
}>();

const emit = defineEmits<{
  closed: [];
  retry: [];
}>();

const visible = defineModel<boolean>("visible", { default: false });
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const title = computed(() => props.detail?.product_name ?? t("Product details"));
</script>

<template>
  <Dialog v-model:visible="visible" modal :header="title" :style="{ width: '1024px', maxWidth: '96vw' }" :breakpoints="{ '768px': '100vw' }" @hide="emit('closed')">
    <div v-if="loading" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,0.8fr)]">
      <div class="aspect-square animate-pulse rounded-lg bg-surface-100 dark:bg-surface-800" />
      <div class="space-y-3">
        <div class="h-7 animate-pulse rounded bg-surface-100 dark:bg-surface-800" />
        <div class="h-20 animate-pulse rounded bg-surface-100 dark:bg-surface-800" />
        <div class="h-24 animate-pulse rounded bg-surface-100 dark:bg-surface-800" />
      </div>
    </div>

    <Message v-else-if="failed" severity="error" :closable="false">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <span>{{ t("Unable to load product details") }}</span>
        <Button :label="t('Retry')" severity="danger" outlined size="small" @click="emit('retry')" />
      </div>
    </Message>

    <div v-else-if="detail" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,0.8fr)]">
      <div class="min-w-0">
        <Galleria
          v-if="detail.images.length > 1"
          :value="detail.images"
          :show-item-navigators="true"
          :show-thumbnails="true"
          :show-indicators="false"
          :circular="true"
          container-style="max-width: 100%"
        >
          <template #item="slotProps">
            <img :src="slotProps.item.full_url" :alt="detail.product_name" class="h-[min(58vw,30rem)] w-full object-contain" />
          </template>
          <template #thumbnail="slotProps">
            <img :src="slotProps.item.thumb_url" :alt="detail.product_name" class="h-14 w-14 object-contain" />
          </template>
        </Galleria>
        <img
          v-else-if="detail.images.length === 1"
          :src="detail.images[0].full_url"
          :alt="detail.product_name"
          class="h-[min(58vw,30rem)] w-full rounded-lg border border-surface-200 object-contain dark:border-surface-700"
        />
        <div v-else class="flex h-[min(58vw,30rem)] items-center justify-center rounded-lg border border-surface-200 bg-surface-50 text-surface-400 dark:border-surface-700 dark:bg-surface-800 dark:text-surface-500">
          <i class="fa fa-image text-4xl" aria-hidden="true" />
          <span class="sr-only">{{ t("No images available") }}</span>
        </div>
      </div>

      <div class="flex min-w-0 flex-col gap-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="m-0 text-xl font-semibold">{{ detail.product_name }}</h2>
            <p v-if="detail.description" class="m-0 mt-2 whitespace-pre-line text-surface-700 dark:text-surface-200">{{ detail.description }}</p>
            <div v-if="detail.brand_name || detail.unit_name" class="mt-3 space-y-1 text-sm text-surface-500 dark:text-surface-400">
              <p v-if="detail.brand_name" class="m-0 flex items-center gap-1.5">
                <i class="fa fa-building w-4 text-center" aria-hidden="true" />
                <span class="sr-only">{{ t("Brand") }}:</span>
                <span>{{ detail.brand_name }}</span>
              </p>
              <p v-if="detail.unit_name" class="m-0 flex items-center gap-1.5">
                <i class="fa fa-weight-hanging w-4 text-center" aria-hidden="true" />
                <span class="sr-only">{{ t("Unit") }}:</span>
                <span>{{ detail.unit_name }}</span>
              </p>
            </div>
          </div>
          <Tag :value="detail.is_available ? t('In stock') : t('Out of stock')" :severity="detail.is_available ? 'success' : 'secondary'" />
        </div>

        <div class="border-y border-surface-200 py-4 dark:border-surface-700">
          <p class="m-0 text-sm text-surface-500 dark:text-surface-400">{{ t("Base price") }}</p>
          <p class="m-0 mt-1 text-2xl font-semibold">{{ formatCurrency(String(detail.price)) }}</p>
        </div>

        <div v-if="detail.option_values.length">
          <h3 class="m-0 mb-2 text-base font-semibold">{{ t("Variant") }}</h3>
          <dl class="grid grid-cols-[auto_1fr] gap-x-5 gap-y-2 text-sm">
            <template v-for="option in detail.option_values" :key="`${option.option_name}-${option.value}`">
              <dt class="text-surface-500 dark:text-surface-400">{{ option.option_name ?? t("Option") }}</dt>
              <dd class="m-0 font-medium">{{ option.value }}</dd>
            </template>
          </dl>
        </div>

        <div v-if="detail.categories.length">
          <h3 class="m-0 mb-2 text-base font-semibold">{{ t("Categories") }}</h3>
          <div class="flex flex-wrap gap-2">
            <Tag v-for="category in detail.categories" :key="category.id" icon="fa fa-tag" :value="category.name" severity="secondary" rounded />
          </div>
        </div>

        <div v-if="detail.sale_units.length">
          <h3 class="m-0 mb-2 text-base font-semibold">{{ t("Additional sale units") }}</h3>
          <ul class="m-0 space-y-2 p-0">
            <li v-for="unit in detail.sale_units" :key="unit.id" class="flex items-center justify-between gap-3 text-sm">
              <span class="flex flex-col">
                <span>{{ unit.name }}</span>
                <small class="text-surface-500 dark:text-surface-400">
                  {{ unit.name }} = {{ unit.conversion_factor }} {{ detail.unit_name ?? t("Base unit") }}
                </small>
              </span>
              <span class="font-medium">{{ formatCurrency(String(unit.price)) }}</span>
            </li>
          </ul>
        </div>

        <p v-if="detail.identifier || detail.barcode" class="m-0 text-xs text-surface-500 dark:text-surface-400">
          {{ [detail.identifier, detail.barcode].filter(Boolean).join(" · ") }}
        </p>
      </div>
    </div>
  </Dialog>
</template>
