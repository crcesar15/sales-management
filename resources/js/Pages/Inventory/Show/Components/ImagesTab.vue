<script setup lang="ts">
import { Button, Badge, Galleria, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { ref, computed } from "vue";
import { route } from "ziggy-js";
import type { InventoryVariantDetail, InventoryProductDetail } from "@/Types/inventory-variant-types";

const props = defineProps<{
  product: InventoryProductDetail;
  variant: InventoryVariantDetail;
}>();
const toast = useToast();
const { t } = useI18n();

const images = props.variant.images ?? [];
const initialMediaIds = images.map((img) => img.id);

const selectedMediaIds = ref<number[]>([...initialMediaIds]);
const saving = ref(false);

const hasVariantImages = computed(() => images.length > 0);

const displayImages = computed(() => {
  if (hasVariantImages.value) return images;
  return props.product.media ?? [];
});

const isDirty = computed(() => {
  const current = [...selectedMediaIds.value].sort();
  const initial = [...initialMediaIds].sort();
  return current.length !== initial.length || current.some((id, i) => id !== initial[i]);
});

// Lightbox
const lightboxVisible = ref(false);
const lightboxActiveIndex = ref(0);

const galleriaItems = computed(() =>
  displayImages.value.map((img) => ({
    itemImageSrc: img.full_url ?? img.thumb_url,
    thumbnailImageSrc: img.thumb_url,
    alt: t("Variant image"),
  })),
);

const openLightbox = (index: number) => {
  lightboxActiveIndex.value = index;
  lightboxVisible.value = true;
};

const toggleMedia = (mediaId: number) => {
  const index = selectedMediaIds.value.indexOf(mediaId);
  if (index === -1) {
    selectedMediaIds.value.push(mediaId);
  } else {
    selectedMediaIds.value.splice(index, 1);
  }
};

const onSave = () => {
  saving.value = true;
  router.put(
    route("variant.images.sync", { product: props.product.id, variant: props.variant.id }),
    { media_ids: selectedMediaIds.value },
    {
      onSuccess: () => {
        toast.add({ severity: "success", summary: t("Success"), detail: t("Variant images updated"), life: 3000 });
      },
      onError: (errs) => {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t(Object.values(errs as Record<string, string>)[0] ?? "An error occurred"),
          life: 3000,
        });
      },
      onFinish: () => {
        saving.value = false;
      },
    },
  );
};
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <p v-if="!hasVariantImages" class="text-sm text-gray-500 mt-1">
          {{ t("No variant images assigned. Select from product media below.") }}
        </p>
      </div>
      <Button
        v-can="'inventory.edit'"
        :label="t('Save')"
        icon="fa fa-save"
        raised
        :loading="saving"
        :disabled="!isDirty"
        class="uppercase"
        @click="onSave"
      />
    </div>

    <!-- Current images display -->
    <div v-if="displayImages.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
      <div
        v-for="(img, index) in displayImages"
        :key="img.id"
        class="group relative aspect-square overflow-hidden rounded-lg border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800 cursor-pointer"
        @click="openLightbox(index)"
      >
        <img :src="img.thumb_url" :alt="t('Variant image')" class="h-full w-full object-cover" />

        <!-- Hover overlay -->
        <div
          class="absolute inset-0 flex items-center justify-center gap-2 bg-black/40 opacity-0 backdrop-blur-[2px] transition-opacity duration-150 group-hover:opacity-100"
        >
          <Button
            v-tooltip.top="t('Preview')"
            icon="fa-solid fa-eye"
            rounded
            severity="secondary"
            size="small"
            class="!bg-white/90 !text-slate-700 hover:!bg-white dark:!bg-slate-800/90 dark:!text-slate-200 dark:hover:!bg-slate-800"
            @click.stop="openLightbox(index)"
          />
        </div>
      </div>
    </div>

    <!-- Fallback notice -->
    <div
      v-if="!hasVariantImages && product.media?.length"
      class="text-sm text-amber-600 dark:text-amber-400 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg flex items-center gap-2"
    >
      <i class="fa fa-info-circle" />
      {{ t("Showing product-level images as fallback.") }}
    </div>

    <!-- Divider + Product media selector -->
    <div
      v-if="product.media?.length"
      class="border-t border-slate-200 dark:border-slate-700 pt-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4 -mx-1"
    >
      <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
        {{ t("Select from product media") }}
        <Badge :value="selectedMediaIds.length + ' / ' + (product.media?.length ?? 0)" severity="info" />
      </h4>
      <div class="flex flex-wrap gap-3">
        <div
          v-for="media in product.media ?? []"
          :key="media.id"
          class="relative cursor-pointer rounded-lg border-2 overflow-hidden transition-all duration-150"
          :class="
            selectedMediaIds.includes(media.id)
              ? 'border-primary-500 ring-1 ring-primary-500 opacity-100'
              : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 opacity-70 hover:opacity-100'
          "
          @click="toggleMedia(media.id)"
        >
          <img :src="media.thumb_url" :alt="t('Product image')" class="h-24 w-24 object-cover" />
          <div
            v-if="selectedMediaIds.includes(media.id)"
            class="absolute top-1 right-1 h-5 w-5 rounded-full bg-primary-500 flex items-center justify-center"
          >
            <i class="fa fa-check text-white text-xs" />
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-8">
      <i class="fa fa-image text-4xl text-slate-300 dark:text-slate-500 mb-3 block" />
      <p class="text-gray-500">{{ t("Upload product images first to associate them with this variant.") }}</p>
      <Button
        :label="t('Go to product')"
        icon="fa fa-arrow-up-right-from-square"
        variant="text"
        size="small"
        class="mt-3"
        @click="router.visit(route('products.edit', { product: product.id }))"
      />
    </div>

    <!-- Lightbox -->
    <Galleria
      v-model:visible="lightboxVisible"
      v-model:active-index="lightboxActiveIndex"
      :value="galleriaItems"
      :full-screen="true"
      :show-item-navigators="true"
      :show-thumbnails="true"
      :circular="true"
      :num-visible="5"
      container-style="max-width: 100%"
    >
      <template #item="slotProps">
        <div class="flex h-[40vh] w-[100vh] md:h-[50vh] md:w-[35vw] items-center justify-center bg-slate-900">
          <img
            v-if="slotProps.item"
            :src="slotProps.item.itemImageSrc"
            :alt="slotProps.item.alt"
            class="h-[40vh] w-[100vh] md:h-[50vh] md:w-[35vw] object-contain"
          />
        </div>
      </template>
      <template #thumbnail="slotProps">
        <img
          v-if="slotProps.item"
          :src="slotProps.item.thumbnailImageSrc"
          :alt="slotProps.item.alt"
          class="h-16 w-16 rounded object-cover"
        />
      </template>
    </Galleria>
  </div>
</template>

<style>
.p-galleria-item {
  background-color: var(--surface-900);
}
</style>
