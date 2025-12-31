<template>
  <div>
    <div
      v-if="files.length === 0"
      class="flex justify-center mt-4"
    >
      <SplitButton
        :label="t('Upload Image')"
        icon="fa fa-add"
        class="uppercase"
        :model=uploadImageOptions
        raised
        @click="openFileUpload(false)"
      />
    </div>
    <div
      v-else
    >
      <div v-if="files.length > 0">
        <div class="grid grid-cols-12">
          <div
            v-for="(file) of files"
            :key="file.id"
            class="p-2 flex flex-col items-center xl:col-span-3 lg:col-span-4 md:col-span-6 col-span-12"
          >
            <div class="flex flex-col items-center">
              <img
                role="presentation"
                :src="file.url"
                width="200"
                height="200"
                class="mb-3 rounded-lg border-2 border-slate-300 dark:border-slate-700"
              >
              <Button
                v-tooltip.bottom="'Remove image'"
                icon="fa fa-trash"
                rounded
                raised
                severity="danger"
                size="small"
                style="margin-top: -25px;"
                @click="removeFile(file.id)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="flex justify-center mt-4">
      <SplitButton
        v-show="props.files.length > 0"
        icon="fa fa-add"
        :label="t('Upload Image')"
        class="uppercase"
        :model=uploadImageOptions
        raised
        @click="openFileUpload(false)"
      />
      <FileUpload
        v-show="false"
        ref="fileUpload"
        choose-label="Upload Image"
        mode="basic"
        :auto="true"
        accept="image/*"
        :max-file-size="10000000"
        :multiple="false"
        :custom-upload="true"
        :pt="fileUploadPassThrough"
        @uploader="uploader"
      />
    </div>
    <Dialog
      v-model:visible="cropperToggle"
      :style="{ width: '50rem' }"
      :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
      modal
      :closable="false"
    >
      <template #footer>
        <div class="flex items-center justify-center gap-2 mt-2">
          <Button
            icon="fas fa-check"
            :label="t('Save')"
            @click="saveCropped"
          />
          <Button
            icon="fas fa-times"
            :label="t('Cancel')"
            outlined
            @click="cropperToggle = false"
          />
        </div>
      </template>
      <div class="cropper-area">
        <div class="relative">
          <div class="absolute bottom-0 left-0 right-0 z-10">
            <div class="flex">
              <ButtonGroup
                class="
                  w-full
                  p-2
                  bg-white/10
                  dark:bg-gray-800/10
                "
              >
                <Button
                  class="
                    block
                    !w-full
                    hover:!bg-white/20
                    dark:hover:!bg-gray-800/20
                  "
                  variant="text"
                  severity="secondary"
                  icon="fa-solid fa-up-down"
                  @click="flip(0,1)"
                />
                <Button
                  class="
                    block
                    !w-full
                    hover:!bg-white/20
                    dark:hover:!bg-gray-800/20
                  "
                  variant="text"
                  severity="secondary"
                  icon="fa-solid fa-left-right"
                  @click="flip(1,0)"
                />
                <Button
                  class="
                    block
                    !w-full
                    hover:!bg-white/20
                    dark:hover:!bg-gray-800/20
                  "
                  variant="text"
                  severity="secondary"
                  icon="fa-solid fa-rotate-left"
                  @click="rotate(-90)"
                />
                <Button
                  class="
                    block
                    !w-full
                    hover:!bg-white/20
                    dark:hover:!bg-gray-800/20
                  "
                  variant="text"
                  severity="secondary"
                  icon="fa-solid fa-rotate-right"
                  @click="rotate(90)"
                />
              </ButtonGroup>
            </div>
          </div>
          <Cropper
            class="w-100"
            ref="cropper"
            :stencil-props="{
              movable: true,
              resizable: true,
              aspectRatio: 1,
            }"
            :src="imageAddress"
          />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">

import {
  Dialog,
  Button,
  FileUpload,
  ButtonGroup,
  SplitButton,
} from "primevue"
import { ref } from "vue";
import { useI18n } from "vue-i18n";

// cropper component
import 'vue-advanced-cropper/dist/style.css';
import { Cropper } from 'vue-advanced-cropper'

// Set composables
const { t } = useI18n();

// Define Emits
const emit = defineEmits(['upload-file', 'remove-file']);

// Set up the props
const props = defineProps<{
  files: Array<{ id: number; url: string }>;
}>();

// Open dialog

const fileUploadPassThrough = ref();

const uploadImageOptions = [
  {
    label: 'Upload Image',
    icon: 'fa fa-upload',
    command: () => {
      openFileUpload()
    }
  },
  {
    label: 'Use Camera',
    icon: 'fa fa-camera',
    command: () => {
      openFileUpload(true)
    }
  }
];

const openFileUpload = (fromCamera = false) => {
  if (fromCamera) {
    fileUploadPassThrough.value = {
      input: {
        capture: 'environment'
      }
    };
  } else {
    fileUploadPassThrough.value = {};
  }

  cropperToggle.value = false;
  imageAddress.value = "";
  fileUpload.value?.choose();
};

// Cropper instance
const cropperToggle = ref(false);
const imageAddress = ref("");
const fileUpload = ref();
const cropper = ref();

const uploader = (event: any) => {
  const { files } = event;
  const file = files[0];
  const reader = new FileReader();
  reader.readAsDataURL(file);

  reader.onload = () => {
    imageAddress.value = String(reader.result);
    cropperToggle.value = true;
  };
};

const flip = (x:number,y:number) => {
  cropper.value.flip(x,y);
}

const rotate = (angle:number) => {
  cropper.value.rotate(angle);
}

const saveCropped = () => {
  const { canvas } = cropper.value.getResult();
  const formData = new FormData();
  canvas.toBlob((blob: Blob) => {
    formData.append("file", blob, `${Date.now()}.webp`);
    emit("upload-file", formData);
    cropperToggle.value = false;
  });
};

const removeFile = (fileId: string) => {
  emit("remove-file", fileId);
};

</script>
