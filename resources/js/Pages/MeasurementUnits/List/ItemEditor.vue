<template>
  <div>
    <Dialog
      v-model:visible="visible"
      :header="$t('Measurement Unit')"
      :breakpoints="{ '1100px': '60vw', '750px': '75vw', '500px': '90vw' }"
      :style="{ width: '30vw' }"
      modal
      @hide="clearSelection"
    >
      <div class="flex flex-col">
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
        >
          {{ nameErrorMessage || '&nbsp;' }}
        </small>
        <label for="abbreviation">{{ $t('Abbreviation') }}</label>
        <InputText
          id="abbreviation"
          v-model="abbreviation"
          autocomplete="off"
          class="mt-2"
          :class="{ 'p-invalid': abbreviationErrorMessage }"
        />
        <small
          id="text-error"
          class="text-red-400 dark:text-red-300"
        >
          {{ abbreviationErrorMessage || '&nbsp;' }}
        </small>
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
import PButton from "primevue/button";

export default {
  components: {
    Dialog,
    InputText,
    PButton,
  },
  props: {
    measurementUnit: {
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
      abbreviation: "",
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
    abbreviationErrorMessage() {
      if (this.submitted && (this.abbreviation === undefined || this.abbreviation === "")) {
        return "Abbreviation is required";
      }

      if (this.submitted && (this.abbreviation === undefined || this.abbreviation.length > 10)) {
        return "Abbreviation must be less than 10 characters";
      }

      return null;
    },
  },
  watch: {
    showDialog(val) {
      this.visible = val;
      this.submitted = false;
      if (val) {
        this.name = this.measurementUnit.name;
        this.abbreviation = this.measurementUnit.abbreviation;
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
        this.$emit("submitted", this.measurementUnit.id, {
          name: this.name,
          abbreviation: this.abbreviation,
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

      if (this.abbreviation === undefined || this.abbreviation === "") {
        return false;
      }

      if (this.abbreviation === undefined || this.abbreviation.length > 10) {
        return false;
      }

      return true;
    },
  },
};
</script>
