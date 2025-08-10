<template>
  <div>
    <Dialog
      v-model:visible="showModal"
      :header="$t('Measurement Unit')"
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

import { MeasurementUnit } from "@app-types/measurement-unit-types";
import {
  Dialog,
  InputText,
  Button,
} from "primevue"
import { computed, ref, watch } from "vue";

// Define v-model:show-modal
const showModal = defineModel("show-modal", {type: Boolean, required: true});

// Define props
const props = defineProps<{
  measurementUnit: MeasurementUnit | Pick<MeasurementUnit, 'id' | 'name' | 'abbreviation'> | null;
}>();

// Define emits
const emit = defineEmits(["submitted"]);

// Open modal
let name = ref("");
let abbreviation = ref("");
let submitted = ref(false);

watch(
  showModal,
  (val) => {
    if (val) {
      name.value = props?.measurementUnit?.name || "";
      abbreviation.value = props?.measurementUnit?.abbreviation || "";
    }
  },
);

// Submit validations
const nameErrorMessage = computed(() => {
  if (submitted.value && (name.value === undefined || name.value === "")) {
    return "Name is required";
  }
  return null;
});

const abbreviationErrorMessage = computed(() => {
  if (submitted.value && (abbreviation.value === undefined || abbreviation.value === "")) {
    return "Abbreviation is required";
  }
  if (submitted.value && (abbreviation.value === undefined || abbreviation.value.length > 10)) {
    return "Abbreviation must be less than 10 characters";
  }
  return null;
});

const validate = () => {
  if (name.value === undefined || name.value === "") {
    return false;
  }

  if (abbreviation.value === undefined || abbreviation.value === "") {
    return false;
  }

  if (abbreviation.value === undefined || abbreviation.value.length > 10) {
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
    if(props.measurementUnit === null) {
      emit("submitted", { name: name.value, abbreviation: abbreviation.value });
    } else {
      emit("submitted", {
        ...props.measurementUnit,
        name: name.value,
        abbreviation: abbreviation.value,
      });
    }
  }
};

const closeModal = () => {
  showModal.value = false;
};
</script>
