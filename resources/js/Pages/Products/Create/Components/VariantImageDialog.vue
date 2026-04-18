<script setup lang="ts">
import { Button, Dialog } from "primevue";

import { useI18n } from "vue-i18n";
import { ref, watch } from "vue";

const props = withDefaults(
  defineProps<{
    visible: boolean;
    pendingMedia: MediaItem[];
    selectedIds?: number[];
  }>(),
  {
    selectedIds: () => [],
  },
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "save", ids: number[]): void;
}>();

const { t } = useI18n();

interface MediaItem {
  id: number;
  thumb_url: string;
  full_url: string;
}

const selectedMediaIds = ref<number[]>([]);

watch(
  () => props.visible,
  (val) => {
    if (val) {
      selectedMediaIds.value = [...props.selectedIds];
    }
  },
);

const toggleMedia = (mediaId: number) => {
  const index = selectedMediaIds.value.indexOf(mediaId);
  if (index === -1) {
    selectedMediaIds.value.push(mediaId);
  } else {
    selectedMediaIds.value.splice(index, 1);
  }
};

const onSave = () => {
  emit("save", [...selectedMediaIds.value]);
};
</script>

<template>
  <Dialog :visible="visible" modal :style="{ width: '450px' }" @update:visible="$emit('close')">
    <template #header>
      <label>{{ t("Variant Images") }}</label>
    </template>

    <div class="flex flex-col gap-4">
      <div v-if="pendingMedia.length === 0" class="text-sm text-gray-500">
        {{ t("Upload product images first to associate them with this variant.") }}
      </div>

      <div v-else class="flex flex-wrap gap-2">
        <div
          v-for="media in pendingMedia"
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
        <Button :label="t('Save')" @click="onSave" />
      </div>
    </template>
  </Dialog>
</template>
