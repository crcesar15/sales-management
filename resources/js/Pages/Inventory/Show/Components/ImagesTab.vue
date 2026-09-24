<script setup lang="ts">
import { Button, Badge, useToast } from "primevue";

import { Link, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import type { InventoryProductDetail, InventoryVariantDetail } from "@app-types/inventory-variant-types";

const props = defineProps<{
  product: InventoryProductDetail;
  variant: InventoryVariantDetail;
  canEdit: boolean;
  canEditProduct: boolean;
}>();
const emit = defineEmits<{
  (e: "update:dirty", value: boolean): void;
}>();
const toast = useToast();
const { t } = useI18n();

const initialMediaIds = ref<number[]>(props.variant.images.map((image) => image.id));
const selectedMediaIds = ref<number[]>([...initialMediaIds.value]);
const saving = ref(false);

watch(
  () => props.variant.images,
  (images) => {
    initialMediaIds.value = images.map((image) => image.id);
    selectedMediaIds.value = [...initialMediaIds.value];
  },
);

const isDirty = computed(() => {
  const current = [...selectedMediaIds.value].sort((a, b) => a - b);
  const initial = [...initialMediaIds.value].sort((a, b) => a - b);

  return current.length !== initial.length || current.some((id, index) => id !== initial[index]);
});

watch(
  isDirty,
  (value) => emit("update:dirty", value),
  { immediate: true },
);

const toggleMedia = (mediaId: number) => {
  if (!props.canEdit) return;

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
      preserveScroll: true,
      onSuccess: () => {
        initialMediaIds.value = [...selectedMediaIds.value];
        toast.add({ severity: "success", summary: t("Success"), detail: t("Variant images updated"), life: 3000 });
      },
      onError: (errors) => {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t(Object.values(errors as Record<string, string>)[0] ?? "An error occurred"),
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
    <div v-if="product.media.length" class="flex flex-col gap-3">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="m-0 text-lg font-semibold">{{ t("Assign product media") }}</h2>
        </div>
        <Badge :value="`${selectedMediaIds.length} / ${product.media.length}`" severity="secondary" />
      </div>
      <div class="flex flex-wrap gap-3">
        <button
          v-for="media in product.media"
          :key="media.id"
          type="button"
          class="relative overflow-hidden rounded-lg border-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed"
          :class="selectedMediaIds.includes(media.id) ? 'border-primary ring-1 ring-primary' : 'border-surface-200 dark:border-surface-700'"
          :aria-pressed="selectedMediaIds.includes(media.id)"
          :aria-label="selectedMediaIds.includes(media.id) ? t('Remove product image from variant') : t('Assign product image to variant')"
          :disabled="!canEdit"
          @click="toggleMedia(media.id)"
        >
          <img :src="media.thumb_url" :alt="t('Product image')" class="h-24 w-24 object-cover sm:h-32 sm:w-32" />
          <span
            v-if="selectedMediaIds.includes(media.id)"
            class="absolute right-1 top-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white"
            aria-hidden="true"
          >
            <i class="fa fa-check text-xs" />
          </span>
        </button>
      </div>
    </div>

    <div v-else class="flex flex-col items-center gap-3 py-8 text-center">
      <i class="fa fa-image text-4xl text-surface-400 dark:text-surface-500" aria-hidden="true" />
      <p class="m-0 text-surface-500 dark:text-surface-400">{{ t("Upload product images first to associate them with this variant.") }}</p>
      <Link v-if="canEditProduct" :href="route('products.edit', { product: product.id })">
        <Button :label="t('Go to product')" icon="fa fa-arrow-up-right-from-square" text size="small" />
      </Link>
    </div>

    <div v-if="canEdit" class="flex justify-end border-t border-surface-200 pt-4 dark:border-surface-700">
      <Button :label="t('Save images')" icon="fa fa-save" raised class="uppercase" :loading="saving" :disabled="!isDirty" @click="onSave" />
    </div>
  </div>
</template>
