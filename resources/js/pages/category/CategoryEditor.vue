<template>
  <div>
    <Dialog
      v-model:visible="visible"
      header="Category Editor"
      modal
      @hide="clearSelection"
    >
      <div class="p-fluid">
        <div class="p-field">
          <label for="name">Name</label>
          <InputText
            id="name"
            v-model="name"
          />
          <small
            id="text-error"
            class="p-error"
          >{{ nameErrorMessage || '&nbsp;' }}</small>
        </div>
      </div>
      <template
        #footer
        class="flex flex-wrap justify-content-end"
      >
        <PButton
          severity="secondary"
          label="Close"
          @click="closeModal"
        />
        <PButton
          severity="primary"
          label="Save"
          @click="submit"
        />
      </template>
    </Dialog>
  </div>
</template>

<script>
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import PButton from "primevue/button";

export default {
  components: {
    Dialog,
    InputText,
    PButton,
  },
  props: {
    category: {
      type: Object,
      default: () => ({}),
    },
    showDialog: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      name: "",
      visible: false,
      submitted: false,
    };
  },
  computed: {
    nameErrorMessage() {
      if (this.submitted && (this.name === undefined || this.name === "")) {
        return "Name is required";
      }

      return null;
    },
  },
  watch: {
    showDialog(val) {
      this.visible = val;
      this.submitted = false;
      if (val) {
        this.name = this.category.name;
      }
    },
  },
  methods: {
    clearSelection() {
      this.$emit("clearSelection");
    },
    submit() {
      this.submitted = true;
      if (this.validate()) {
        this.$emit("submitted", this.category.id, {
          name: this.name,
        });
        this.visible = false;
      }
    },
    closeModal() {
      this.visible = false;
    },
    validate() {
      if (this.name === undefined || this.name === "") {
        return false;
      }

      return true;
    },
  },
};
</script>
