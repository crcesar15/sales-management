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
            :max-file-size="1000000"
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
  </div>
</template>

<script>
import FileUpload from "primevue/fileupload";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import PButton from "primevue/button";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";

export default {
  components: {
    FileUpload,
    DataTable,
    Column,
    PButton,
    ConfirmDialog,
    Toast,
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
      const formData = new FormData();
      formData.append("file", files[0]);
      this.$emit("upload-file", formData);
    },
  },
};

</script>
