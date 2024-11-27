<template>
  <div>
    <Dialog
      v-model:visible="visible"
      :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
      :header="$t('Brand')"
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
    brand: {
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
        this.name = this.brand.name;
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
        this.$emit("submitted", this.brand.id, {
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
