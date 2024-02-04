<template>
  <div>
    <ConfirmDialog />
    <Toast />
    <DataTable
      :value="elements"
    >
      <template #header>
        <div class="flex justify-content-end">
          <FileUpload
            mode="basic"
            :auto="true"
            accept="image/*"
            :max-file-size="10000000"
            :multiple="false"
            :custom-upload="true"
            @uploader="uploader"
          />
        </div>
      </template>
      <template #empty>
        <h5 class="flex flex-wrap justify-content-center">
          No images yet.
        </h5>
      </template>
      <Column
        field="image"
        header="Image"
      >
        <template #body="item">
          <img
            :src="item.data.url"
            alt="File"
            class="w-10rem shadow-2 border-round"
          >
        </template>
      </Column>
      <Column
        field="filename"
        header="Filename"
      />
      <Column
        field="created_at"
        header="Created At"
      />
      <Column
        field="updated_at"
        header="Updated At"
      />
      <Column
        field="actions"
        header="Actions"
      >
        <template #body="data">
          <PButton
            icon="fas fa-trash"
            text
            @click="deleteFile(data.data.id)"
          />
        </template>
      </Column>
    </DataTable>
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
import FileUpload from "primevue/fileupload";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import PButton from "primevue/button";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";

// cropper component
import VueCropper from "vue-cropperjs";
import "cropperjs/dist/cropper.css";

export default {
  components: {
    FileUpload,
    DataTable,
    Column,
    PButton,
    ConfirmDialog,
    Toast,
    VueCropper,
    Dialog,
  },
  props: {
    files: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["delete-file"],
  data() {
    return {
      elements: [],
      imageAddress: "",
      cropperToggle: false,
    };
  },
  watch: {
    files: {
      immediate: true,
      handler(newVal) {
        this.elements = newVal;
      },
    },
  },
  methods: {
    deleteFile(fileId) {
      this.$confirm.require({
        message: "Are you sure that you want to delete this file?",
        header: "Delete Photo",
        icon: "fas fa-exclamation-triangle",
        accept: () => {
          this.$emit("delete-file", fileId);
        },
      });
    },
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
  },
};

</script>
