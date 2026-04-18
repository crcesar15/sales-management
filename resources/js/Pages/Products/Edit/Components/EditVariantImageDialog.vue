<script setup lang="ts">
import { Button, Dialog, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { ref, onMounted } from "vue";
import { route } from "ziggy-js";
import type { ProductMedia, ProductVariantInline } from "@app-types/product-types";

const props = defineProps<{
  visible: boolean;
  productId: number;
  variant: ProductVariantInline | null;
  productMedia: ProductMedia[];
}>();
const emit = defineEmits<{
  (e: "close"): void;
}>();
const toast = useToast();
const { t } = useI18n();

const selectedMediaIds = ref<number[]>([]);
const saving = ref(false);

onMounted(() => {
  selectedMediaIds.value = props.variant?.images?.map((img) => img.id) ?? [];
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
  if (!props.variant) return;

  saving.value = true;
  router.put(
    route("variant.images.sync", { product: props.productId, variant: props.variant.id }),
    { media_ids: selectedMediaIds.value },
    {
      onSuccess: () => {
        toast.add({ severity: "success", summary: t("Success"), detail: t("Variant images updated"), life: 3000 });
        emit("close");
      },
      onError: (errs) => {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t(Object.values(errs)[0] ?? "An error occurred"),
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
  <Dialog :visible="visible" modal :style="{ width: '450px' }" @update:visible="$emit('close')">
    <template #header>
      <label>{{ t("Variant Images") }}</label>
    </template>

    <div class="flex flex-col gap-4">
      <div v-if="productMedia.length === 0" class="text-sm text-gray-500">
        {{ t("Upload product images first to associate them with this variant.") }}
      </div>

      <div v-else class="flex flex-wrap gap-2">
        <div
          v-for="media in productMedia"
          :key="media.id"
          class="relative cursor-pointer rounded-lg border-2 overflow-hidden transition-colors"
          :class="
            selectedMediaIds.includes(media.id)
              ? 'border-primary-500 ring-1 ring-primary-500'
              : 'border-surface-200 hover:border-surface-300'
          "
          @click="toggleMedia(media.id)"
        >
          <img :src="media.thumb_url" class="h-20 w-20 object-cover" />
          <div
            v-if="selectedMediaIds.includes(media.id)"
            class="absolute top-0.5 right-0.5 h-4 w-4 rounded-full bg-primary-500 flex items-center justify-center"
          >
            <i class="fa fa-check text-white text-[10px]" />
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-2">
        <Button :label="t('Cancel')" severity="secondary" outlined @click="$emit('close')" />
        <Button :label="t('Save')" :loading="saving" @click="onSave" />
      </div>
    </template>
  </Dialog>
</template>
