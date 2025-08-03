<template>
  <div>
    <Dialog
      v-model:visible="showModal"
      :breakpoints="{ '1100px': '60vw', '750px': '75vw', '500px': '90vw' }"
      :style="{ width: '30vw' }"
      :header="$t('Brand')"
      modal
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

<script setup>
import { Dialog, InputText, Button } from "primevue";
import {
  computed, watch, ref,
} from "vue";

const emit = defineEmits(['submit']);

// Get values from the parent
const showModal = defineModel('show-modal');
const { brand } = defineProps(['brand']);

watch(
  showModal,
  (val) => {
    if (val) {
      console.log(brand);
      name.value = brand?.name ?? '';
    }
  },
);

const name = ref("");
let submitted = ref(false);

//Submit feature
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

const submit = () => {
  submitted.value = true;
  if (validate()) {
    showModal.value = false;
    submitted.value = false;
    if (brand === null) {
      emit('submitted', {name: name.value})
    } else {
      emit('submitted', {...brand, name: name.value })
    }
  }
};

const closeModal = () => {
  showModal.value = false;
};
</script>
