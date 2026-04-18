<script setup lang="ts">
import { Button, Dialog, FileUpload, Galleria, useToast } from "primevue";
import { ref, computed } from "vue";
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
}>();
const toast = useToast();
const { t } = useI18n();

interface MediaItem {
  id: number;
  thumb_url: string;
  full_url: string;
}

// Combined display list
const displayFiles = computed(() => [...(props.existingMedia ?? []), ...props.pendingMedia]);

// Galleria data
const galleriaItems = computed(() =>
  displayFiles.value.map((f) => ({
    itemImageSrc: f.full_url,
    thumbnailImageSrc: f.thumb_url,
    alt: "Product image",
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
const cropperRef = ref();
const fileUploadRef = ref();

const openFileUpload = () => {
  cropperVisible.value = false;
  cropperImageSrc.value = "";
  fileUploadRef.value?.choose();
};

const onUploader = (event: { files: File[] | File }) => {
  const file = Array.isArray(event.files) ? event.files[0] : event.files;
  loadImage(file);
};

const onDrop = (event: DragEvent) => {
  isDragOver.value = false;
  const file = event.dataTransfer?.files?.[0];
  if (file && file.type.startsWith("image/")) {
    loadImage(file);
  }
};

const loadImage = (file: File) => {
  const reader = new FileReader();
  reader.readAsDataURL(file);
  reader.onload = () => {
    cropperImageSrc.value = String(reader.result);
    cropperVisible.value = true;
  };
};

const openLightbox = (index: number) => {
  lightboxActiveIndex.value = index;
  lightboxVisible.value = true;
};

const flip = (x: number, y: number) => {
  cropperRef.value?.flip(x, y);
};

const rotate = (angle: number) => {
  cropperRef.value?.rotate(angle);
};

const saveCropped = () => {
  const { canvas } = cropperRef.value.getResult();
  canvas.toBlob((blob: Blob) => {
    const formData = new FormData();
    formData.append("file", blob, `${Date.now()}.webp`);

    toast.add({ severity: "info", summary: t("Uploading..."), life: 3000 });

    axios
      .post(route("products.media.store"), formData, {
        headers: { "Content-Type": "multipart/form-data" },
      })
      .then((response) => {
        const data: PendingMediaResponse = response.data;
        const newMedia: MediaItem = {
          id: data.id,
          thumb_url: data.thumb_url,
          full_url: data.full_url,
        };
        emit("update:pendingMedia", [...props.pendingMedia, newMedia]);
        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("File uploaded"),
          life: 3000,
        });
      })
      .catch((error) => {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t(error?.response?.data?.message || "Upload failed"),
          life: 3000,
        });
      });

    cropperVisible.value = false;
  });
};

const removeFile = (file: MediaItem) => {
  const isExisting = props.existingMedia?.some((m) => m.id === file.id);

  if (isExisting) {
    emit("update:removeMediaIds", [...props.removeMediaIds, file.id]);
  } else {
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
          detail: t(error?.response?.data?.message || "Remove failed"),
          life: 3000,
        });
      });
  }
};
</script>

<template>
  <div>
    <!-- Drop Zone (empty state) -->
    <div
      v-if="displayFiles.length === 0"
      class="relative flex flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed p-10 transition-colors duration-150 cursor-pointer"
      :class="
        isDragOver
          ? 'border-primary-400 bg-primary-50 dark:border-primary-300 dark:bg-primary-500/10'
          : 'border-slate-300 bg-slate-50 hover:border-primary-300 hover:bg-primary-50/50 dark:border-slate-600 dark:bg-slate-800/50 dark:hover:border-primary-400 dark:hover:bg-primary-500/5'
      "
      @click="openFileUpload()"
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
          {{ t("Drop images here or click to browse") }}
        </p>
        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
          {{ t("WebP, PNG, JPG up to 10MB") }}
        </p>
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

          <!-- Hover Overlay -->
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
              @click="openLightbox(index)"
            />
            <Button
              v-tooltip.top="t('Remove image')"
              icon="fa-solid fa-trash"
              rounded
              severity="secondary"
              size="small"
              class="!bg-white/90 !text-slate-700 hover:!bg-white dark:!bg-slate-800/90 dark:!text-slate-200 dark:hover:!bg-slate-800"
              @click="removeFile(file)"
            />
          </div>
        </div>

        <!-- Add Card -->
        <div
          class="flex aspect-square cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 transition-colors duration-150 hover:border-primary-400 hover:bg-primary-50/50 dark:border-slate-600 dark:bg-slate-800/50 dark:hover:border-primary-400 dark:hover:bg-primary-500/5"
          @click="openFileUpload()"
          @dragenter.prevent="isDragOver = true"
          @dragover.prevent="isDragOver = true"
          @dragleave.prevent="isDragOver = false"
          @drop.prevent="onDrop"
        >
          <div class="flex flex-col items-center gap-1 text-slate-400 dark:text-slate-500">
            <i class="fa-solid fa-plus text-xl" />
            <span class="text-xs">{{ t("Add") }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Hidden FileUpload -->
    <FileUpload
      ref="fileUploadRef"
      class="hidden"
      mode="basic"
      :auto="true"
      accept="image/*"
      :max-file-size="10000000"
      :multiple="false"
      :custom-upload="true"
      @uploader="onUploader"
    />

    <!-- Cropper Dialog -->
    <Dialog
      v-model:visible="cropperVisible"
      modal
      :closable="false"
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
            @click="flip(0, 1)"
          />
          <Button
            v-tooltip.top="t('Flip Horizontal')"
            icon="fa-solid fa-left-right"
            text
            rounded
            size="small"
            class="!text-white hover:!bg-white/20"
            @click="flip(1, 0)"
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
          <Button :label="t('Cancel')" icon="fa-solid fa-xmark" outlined @click="cropperVisible = false" />
          <Button :label="t('Crop & Save')" icon="fa-solid fa-check" @click="saveCropped" />
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
