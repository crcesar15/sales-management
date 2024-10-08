<template>
  <div>
    <Dialog
      v-model:visible="visible"
      :header="$t('Measure Unit')"
      modal
      @hide="clearSelection"
    >
      <div class="flex flex-col w-[20rem]">
        <label for="name">{{ $t('Name') }}</label>
        <InputText
          id="name"
          v-model="name"
          autocomplete="off"
          class="mt-2"
          :class="{ 'p-invalid': nameErrorMessage }"
        />
        <small
          id="text-error"
          class="text-red-400 dark:text-red-300"
        >{{ nameErrorMessage || '&nbsp;' }}</small>
        <label for="description">{{ $t('Description') }}</label>
        <Textarea
          id="description"
          v-model="description"
          autocomplete="off"
          rows="2"
          class="mt-2"
        />
      </div>
      <template
        #footer
        class="flex flex-wrap justify-end"
      >
        <PButton
          severity="secondary"
          :label="$t('Cancel')"
          @click="closeModal"
        />
        <PButton
          severity="primary"
          :label="$t('Save')"
          @click="submit"
        />
      </template>
    </Dialog>
  </div>
</template>

<script>
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import PButton from "primevue/button";

export default {
  components: {
    Dialog,
    InputText,
    Textarea,
    PButton,
  },
  props: {
    measureUnit: {
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
      description: "",
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
        this.name = this.measureUnit.name;
        this.description = this.measureUnit.description;
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
        this.$emit("submitted", this.measureUnit.id, {
          name: this.name,
          description: this.description,
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
