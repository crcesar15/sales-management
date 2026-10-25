<script setup lang="ts">
import { Button, Dialog, Galleria, useToast } from "primevue";
import { computed, ref, useTemplateRef, watch } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import axios from "axios";
import "vue-advanced-cropper/dist/style.css";
import { Cropper } from "vue-advanced-cropper";
import type { PendingMediaResponse } from "@app-types/product-types";

const props = defineProps<{
  pendingMedia: MediaItem[];
  existingMedia?: MediaItem[];
  removeMediaIds: number[];
}>();
const emit = defineEmits<{
  "update:pendingMedia": [value: MediaItem[]];
  "update:removeMediaIds": [value: number[]];
  "update:processing": [value: boolean];
}>();
const toast = useToast();
const { t } = useI18n();

interface MediaItem {
  id: number;
  thumb_url: string;
  full_url: string;
}

const MAX_FILE_SIZE = 10 * 1024 * 1024;
const ACCEPTED_IMAGE_TYPES = new Set(["image/jpeg", "image/png", "image/webp"]);

// Combined display list
const displayFiles = computed(() => [...(props.existingMedia ?? []), ...props.pendingMedia]);

// Galleria data
const galleriaItems = computed(() =>
  displayFiles.value.map((f) => ({
    itemImageSrc: f.full_url,
    thumbnailImageSrc: f.thumb_url,
    alt: t("Product image"),
  })),
);

// Drag-and-drop state
const isDragOver = ref(false);

// Lightbox state
const lightboxVisible = ref(false);
const lightboxActiveIndex = ref(0);

// Cropper state
const cropperVisible = ref(false);
const cropperImageSrc = ref("");
const isReading = ref(false);
const isUploading = ref(false);
const isRemoving = ref(false);
const cropperRef = useTemplateRef<InstanceType<typeof Cropper>>("cropperRef");
const galleryInputRef = useTemplateRef<HTMLInputElement>("galleryInputRef");
const cameraInputRef = useTemplateRef<HTMLInputElement>("cameraInputRef");

const isProcessing = computed(() => isReading.value || isUploading.value || isRemoving.value);

watch(isProcessing, (value) => emit("update:processing", value), { immediate: true });

const resetCropper = () => {
  cropperVisible.value = false;
  cropperImageSrc.value = "";
};

const openGalleryPicker = () => {
  if (!isProcessing.value) {
    galleryInputRef.value?.click();
  }
};

const openCameraPicker = () => {
  if (!isProcessing.value) {
    cameraInputRef.value?.click();
  }
};

const showError = (detail: string) => {
  toast.add({ severity: "error", summary: t("Error"), detail: t(detail), life: 3000 });
};

const getErrorMessage = (error: unknown, fallback: string): string => {
  if (axios.isAxiosError<{ message?: string }>(error)) {
    return error.response?.data?.message ?? t(fallback);
  }

  return t(fallback);
};

const readFileAsDataUrl = (file: File): Promise<string> => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result));
    reader.onerror = () => reject(new Error("Unable to read image"));
    reader.onabort = () => reject(new Error("Unable to read image"));
    reader.readAsDataURL(file);
  });
};

const validateImage = (file: File): boolean => {
  if (!ACCEPTED_IMAGE_TYPES.has(file.type)) {
    showError("Unsupported image format. Please choose a JPG, PNG, or WebP image.");

    return false;
  }

  if (file.size > MAX_FILE_SIZE) {
    showError("Image must not exceed 10 MB.");

    return false;
  }

  return true;
};

const loadImage = async (file: File) => {
  if (isProcessing.value || !validateImage(file)) {
    return;
  }

  isReading.value = true;

  try {
    cropperImageSrc.value = await readFileAsDataUrl(file);
    cropperVisible.value = true;
  } catch {
    showError("Unable to read image");
  } finally {
    isReading.value = false;
  }
};

const onFileSelected = (event: Event) => {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  input.value = "";

  if (file) {
    void loadImage(file);
  }
};

const onDrop = (event: DragEvent) => {
  isDragOver.value = false;
  const file = event.dataTransfer?.files?.[0];

  if (file) {
    void loadImage(file);
  }
};

const openLightbox = (index: number) => {
  lightboxActiveIndex.value = index;
  lightboxVisible.value = true;
};

const flip = (x: boolean, y: boolean) => {
  cropperRef.value?.flip(x, y);
};

const rotate = (angle: number) => {
  cropperRef.value?.rotate(angle);
};

const canvasToBlob = (canvas: HTMLCanvasElement): Promise<Blob> => {
  return new Promise((resolve, reject) => {
    canvas.toBlob(
      (blob) => {
        if (blob) {
          resolve(blob);

          return;
        }

        reject(new Error("Unable to process image"));
      },
      "image/webp",
      0.9,
    );
  });
};

const saveCropped = async () => {
  if (isUploading.value) {
    return;
  }

  const canvas = cropperRef.value?.getResult().canvas;

  if (!canvas) {
    showError("Unable to process image");

    return;
  }

  isUploading.value = true;

  try {
    const blob = await canvasToBlob(canvas);

    if (blob.size > MAX_FILE_SIZE) {
      showError("Image must not exceed 10 MB.");

      return;
    }

    const formData = new FormData();
    const extension = blob.type === "image/webp" ? "webp" : "png";
    formData.append("file", blob, `${Date.now()}.${extension}`);

    toast.add({ severity: "info", summary: t("Uploading..."), life: 3000 });

    const response = await axios.post<PendingMediaResponse>(route("products.media.store"), formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    const newMedia: MediaItem = {
      id: response.data.id,
      thumb_url: response.data.thumb_url,
      full_url: response.data.full_url,
    };
    emit("update:pendingMedia", [...props.pendingMedia, newMedia]);
    resetCropper();
    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("File uploaded"),
      life: 3000,
    });
  } catch (error: unknown) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: getErrorMessage(error, "Upload failed"),
      life: 3000,
    });
  } finally {
    isUploading.value = false;
  }
};

const removeFile = (file: MediaItem) => {
  const isExisting = props.existingMedia?.some((m) => m.id === file.id);

  if (isExisting) {
    emit("update:removeMediaIds", [...props.removeMediaIds, file.id]);
  } else {
    if (isProcessing.value) {
      return;
    }

    isRemoving.value = true;

    axios
      .delete(route("products.media.destroy", file.id))
      .then(() => {
        emit(
          "update:pendingMedia",
          props.pendingMedia.filter((m) => m.id !== file.id),
        );
        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("File removed"),
          life: 3000,
        });
      })
      .catch((error) => {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: getErrorMessage(error, "Remove failed"),
          life: 3000,
        });
      })
      .finally(() => {
        isRemoving.value = false;
      });
  }
};
</script>

<template>
  <div>
    <!-- Drop Zone (empty state) -->
    <div
      v-if="displayFiles.length === 0"
      class="relative flex flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed p-10 transition-colors duration-150"
      :class="
        isDragOver
          ? 'border-primary-400 bg-primary-50 dark:border-primary-300 dark:bg-primary-500/10'
          : 'border-slate-300 bg-slate-50 hover:border-primary-300 hover:bg-primary-50/50 dark:border-slate-600 dark:bg-slate-800/50 dark:hover:border-primary-400 dark:hover:bg-primary-500/5'
      "
      @dragenter.prevent="isDragOver = true"
      @dragover.prevent="isDragOver = true"
      @dragleave.prevent="isDragOver = false"
      @drop.prevent="onDrop"
    >
      <div
        class="flex h-14 w-14 items-center justify-center rounded-full transition-colors duration-150"
        :class="
          isDragOver
            ? 'bg-primary-100 text-primary-600 dark:bg-primary-500/20 dark:text-primary-300'
            : 'bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500'
        "
      >
        <i class="fa-solid fa-cloud-arrow-up text-2xl" />
      </div>
      <div class="text-center">
        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
          {{ t("Drop images here") }}
        </p>
        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
          {{ t("WebP, PNG, JPG up to 10MB") }}
        </p>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row">
        <Button
          :label="t('Choose image')"
          icon="fa-solid fa-images"
          severity="secondary"
          :disabled="isProcessing"
          @click="openGalleryPicker"
        />
        <Button :label="t('Take photo')" icon="fa-solid fa-camera" :disabled="isProcessing" @click="openCameraPicker" />
      </div>
    </div>

    <!-- Image Grid (has images) -->
    <div v-else>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        <!-- Image Cards -->
        <div
          v-for="(file, index) in displayFiles"
          :key="file.id"
          class="group relative aspect-square overflow-hidden rounded-lg border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800"
        >
          <!-- Image -->
          <img :src="file.thumb_url || file.full_url" :alt="t('Product image')" class="h-full w-full object-cover" />

          <!-- Persistent controls keep touch actions available on every device. -->
          <div
            class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-gradient-to-t from-black/60 to-transparent px-2 pb-2 pt-8"
          >
            <Button
              v-tooltip.top="t('Preview')"
              icon="fa-solid fa-eye"
              rounded
              severity="secondary"
              size="small"
              class="!bg-white/90 !text-slate-700 hover:!bg-white dark:!bg-slate-800/90 dark:!text-slate-200 dark:hover:!bg-slate-800"
              :disabled="isProcessing"
              @click="openLightbox(index)"
            />
            <Button
              v-tooltip.top="t('Remove image')"
              icon="fa-solid fa-trash"
              rounded
              severity="secondary"
              size="small"
              class="!bg-white/90 !text-slate-700 hover:!bg-white dark:!bg-slate-800/90 dark:!text-slate-200 dark:hover:!bg-slate-800"
              :disabled="isProcessing"
              @click="removeFile(file)"
            />
          </div>
        </div>

        <!-- Add Card -->
        <div
          class="flex aspect-square flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-3 transition-colors duration-150 hover:border-primary-400 hover:bg-primary-50/50 dark:border-slate-600 dark:bg-slate-800/50 dark:hover:border-primary-400 dark:hover:bg-primary-500/5"
          @dragenter.prevent="isDragOver = true"
          @dragover.prevent="isDragOver = true"
          @dragleave.prevent="isDragOver = false"
          @drop.prevent="onDrop"
        >
          <Button
            icon="fa-solid fa-images"
            severity="secondary"
            text
            :label="t('Choose image')"
            :disabled="isProcessing"
            @click="openGalleryPicker"
          />
          <Button icon="fa-solid fa-camera" text :label="t('Take photo')" :disabled="isProcessing" @click="openCameraPicker" />
        </div>
      </div>
    </div>

    <input ref="galleryInputRef" type="file" accept="image/*" class="hidden" @change="onFileSelected" />
    <input ref="cameraInputRef" type="file" accept="image/*" capture="environment" class="hidden" @change="onFileSelected" />

    <!-- Cropper Dialog -->
    <Dialog
      v-model:visible="cropperVisible"
      modal
      :closable="false"
      :close-on-escape="!isUploading"
      :style="{ width: '50rem' }"
      :breakpoints="{ '1199px': '80vw', '575px': '95vw' }"
      :pt="{
        header: { class: '!pb-0 !pt-4 !px-5' },
        content: { class: '!pt-2 !pb-4' },
      }"
    >
      <template #header>
        <div class="flex w-full items-center gap-2">
          <i class="fa-solid fa-crop-simple text-lg text-primary-500" />
          <span class="text-lg font-semibold">{{ t("Crop Image") }}</span>
        </div>
      </template>

      <div class="relative">
        <Cropper
          ref="cropperRef"
          class="h-[400px] w-full rounded-md"
          :stencil-props="{
            movable: true,
            resizable: true,
            aspectRatio: 1,
          }"
          :src="cropperImageSrc"
        />

        <!-- Cropper Toolbar -->
        <div
          class="absolute bottom-3 left-1/2 z-10 flex -translate-x-1/2 items-center gap-1 rounded-full border border-white/20 bg-black/50 px-3 py-1.5 backdrop-blur-md"
        >
          <Button
            v-tooltip.top="t('Flip Vertical')"
            icon="fa-solid fa-up-down"
            text
            rounded
            size="small"
            class="!text-white hover:!bg-white/20"
            @click="flip(false, true)"
          />
          <Button
            v-tooltip.top="t('Flip Horizontal')"
            icon="fa-solid fa-left-right"
            text
            rounded
            size="small"
            class="!text-white hover:!bg-white/20"
            @click="flip(true, false)"
          />
          <div class="mx-1 h-5 w-px bg-white/30" />
          <Button
            v-tooltip.top="t('Rotate Left')"
            icon="fa-solid fa-rotate-left"
            text
            rounded
            size="small"
            class="!text-white hover:!bg-white/20"
            @click="rotate(-90)"
          />
          <Button
            v-tooltip.top="t('Rotate Right')"
            icon="fa-solid fa-rotate-right"
            text
            rounded
            size="small"
            class="!text-white hover:!bg-white/20"
            @click="rotate(90)"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-2 pt-2">
          <Button :label="t('Cancel')" severity="secondary" :disabled="isUploading" @click="resetCropper" />
          <Button :label="t('Crop & Save')" :loading="isUploading" :disabled="isUploading" @click="saveCropped" />
        </div>
      </template>
    </Dialog>

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
