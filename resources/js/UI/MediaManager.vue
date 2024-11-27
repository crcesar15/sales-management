<template>
  <div>
    <div
      v-if="files.length === 0"
      class="flex justify-center mt-4"
    >
      <PButton
        :label="$t('Upload Image')"
        icon="fa fa-add"
        class="uppercase"
        raised
        @click="openFileUpload"
      />
    </div>
    <div
      v-else
    >
      <div v-if="files.length > 0">
        <div class="grid grid-cols-12">
          <div
            v-for="(file, index) of files"
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
              <PButton
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
      <PButton
        v-show="files.length > 0"
        icon="fa fa-add"
        :label="$t('Upload Image')"
        class="uppercase"
        raised
        @click="openFileUpload"
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
          <PButton
            icon="fas fa-check"
            :label="$t('Save')"
            @click="saveCropped"
          />
          <PButton
            icon="fas fa-times"
            :label="$t('Cancel')"
            outlined=""
            @click="cropperToggle = false"
          />
        </div>
      </template>
      <div class="cropper-area">
        <div class="img-cropper">
          <VueCropper
            ref="cropper"
            :aspect-ratio="1"
            :src="imageAddress"
          />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script>
import Dialog from "primevue/dialog";
import PButton from "primevue/button";
import FileUpload from "primevue/fileupload";

// cropper component
import VueCropper from "vue-cropperjs";
import "cropperjs/dist/cropper.css";

export default {
  components: {
    Dialog,
    VueCropper,
    PButton,
    FileUpload,
  },
  props: {
    files: {
      type: Array,
      required: true,
    },
  },
  data() {
    return {
      cropperToggle: false,
      imageAddress: "",
    };
  },
  methods: {
    uploader(event) {
      const { files } = event;
      const file = files[0];
      const reader = new FileReader();
      reader.readAsDataURL(file);

      reader.onload = () => {
        this.imageAddress = String(reader.result);
        this.$refs.cropper.replace(reader.result);
      };
      this.cropperToggle = true;
    },
    saveCropped() {
      const formData = new FormData();
      this.$refs.cropper.getCroppedCanvas().toBlob((blob) => {
        formData.append("file", blob);
        this.$emit("upload-file", formData);
        this.cropperToggle = false;
      });
    },
    openFileUpload() {
      this.$refs.fileUpload.choose();
    },
    removeFile(fileId) {
      this.$emit("remove-file", fileId);
    },
  },
};
</script>
