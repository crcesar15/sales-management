<template>
  <div>
    <ConfirmDialog />
    <Toast />
    <DataTable
      :value="elements"
    >
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
            size="small"
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
    onUpload(event) {
      this.elements = event.files;
    },
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
  },
};

</script>
