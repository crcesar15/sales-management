<template>
  <div>
    <Dialog
      v-model:visible="showModal"
      :header="$t('Category')"
      :breakpoints="{ '1100px': '60vw', '750px': '75vw', '500px': '90vw' }"
      :style="{ width: '30vw' }"
      modal
      @hide="closeModal"
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
        >{{ nameErrorMessage || '&nbsp;' }}</small>
      </div>
      <template
        #footer
        class="flex flex-wrap justify-end"
      >
        <Button
          severity="secondary"
          :label="$t('Cancel')"
          @click="closeModal"
        />
        <Button
          severity="primary"
          :label="$t('Save')"
          @click="submit"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">

import {
  Dialog,
  InputText,
  Button
} from "primevue"
import { computed, ref, watch } from "vue";

// Define v-model:show-dialog
const showModal = defineModel("show-modal", { type: Boolean, required: true });

// Define props
const props = defineProps({
  category: {
    type: Object,
    default: () => ({})
  },
})

// Define emits
const emit = defineEmits(["submitted"]);

// Open modal
let name = ref("");
let submitted = ref(false);

watch(
  showModal,
  (val) => {
    if (val) {
      name.value = props.category?.name ?? "";
    }
  },
);

// Submit Validations
const nameErrorMessage = computed(() => {
  if (submitted.value && (name.value === undefined || name.value === "")) {
    return "Name is required";
  }
  return null;
});

const validate = () => {
  if (name.value === undefined || name.value === "") {
    return false;
  }
  return true;
};

// Submit
const submit = () => {
  submitted.value = true;
  if (validate()) {
    showModal.value = false;
    submitted.value = false;
    if (props.category === null) {
      emit("submitted", { name: name.value });
    } else {
      emit("submitted", { ...props.category, name: name.value });
    }
  }
};

const closeModal = () => {
  showModal.value = false;
}
</script>
