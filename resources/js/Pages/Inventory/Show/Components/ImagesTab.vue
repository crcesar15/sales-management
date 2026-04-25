<script setup lang="ts">
import { Button, Badge, useToast } from "primevue";

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

const images = computed(() => props.variant.images ?? []);
const initialMediaIds = computed(() => images.value.map((img) => img.id));

const selectedMediaIds = ref<number[]>([...initialMediaIds.value]);
const saving = ref(false);

const hasVariantImages = computed(() => images.value.length > 0);

const isDirty = computed(() => {
  const current = [...selectedMediaIds.value].sort();
  const initial = [...initialMediaIds.value].sort();
  return current.length !== initial.length || current.some((id, i) => id !== initial[i]);
});

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
    route("variant.images.sync", { product: props.variant.product_id, variant: props.variant.id }),
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
    <div
      v-if="product.media?.length"
      class="border-t border-slate-200 dark:border-slate-700 pt-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4 -mx-1"
    >
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
          <img :src="media.thumb_url" :alt="t('Product image')" class="h-40 w-40 object-cover" />
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
    <div class="flex justify-between items-center">
      <div>
        <p v-if="!hasVariantImages && selectedMediaIds.length === 0" class="text-sm text-gray-500 mt-1">
          {{ t("No variant images assigned. Select from product media below.") }}
        </p>
        <p v-else class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
          {{ t("Select from product media") }}
          <Badge :value="selectedMediaIds.length + ' / ' + (product.media?.length ?? 0)" severity="info" />
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
  </div>
</template>
