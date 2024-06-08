<template>
  <div>
    <div
      v-if="files.length === 0"
      class="grid justify-content-center mt-4"
    >
      <PButton
        label="Upload Image"
        outlined
        @click="openFileUpload"
      />
    </div>
    <div
      v-else
    >
      <div v-if="files.length > 0">
        <div class="grid">
          <div
            v-for="(file, index) of files"
            :key="file.id"
            class="p-2 flex flex-column align-items-center xl:col-3 lg:col-4 md:col-6 col-12"
          >
            <div class="flex flex-column align-items-center border-1 surface-border border-round">
              <img
                role="presentation"
                :src="file.url"
                width="200"
                height="200"
                class="mb-3"
              >
              <PButton
                icon="fa fa-trash"
                outlined
                rounded
                severity="danger"
                @click="removeFile(file.id)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="flex justify-content-center mt-4">
      <FileUpload
        v-show="files.length > 0"
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
      modal
      :closable="false"
    >
      <template #footer>
        <div class="flex align-items-center justify-content-center gap-2 mt-2">
          <PButton
            icon="fas fa-check"
            label="Save"
            @click="saveCropped"
          />
          <PButton
            icon="fas fa-times"
            label="Cancel"
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
