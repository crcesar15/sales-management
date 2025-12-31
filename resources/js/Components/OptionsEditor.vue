<template>
  <div>
    <div v-show="options.length === 0">
      <div class="flex">
        <Button
          icon="fa fa-plus"
          text
          :label="$t('Add options like size or color')"
          @click="addOption"
        />
      </div>
    </div>
    <div v-show="options.length > 0">
      <div
        v-for="(option,index) in options"
        :key="index"
        class="grid grid-cols-12 mb-3 gap-3"
      >
        <div
          class="col-span-12 flex flex-wrap flex-row justify-end"
          style="margin-bottom: -35px;"
        >
          <Button
            v-show="!option.saved"
            v-tooltip.left="$t('Save')"
            icon="fa fa-floppy-disk"
            style="width: 25px; height: 25px; padding: 0px; margin-right: 4px;"
            raised
            outlined
            size="small"
            :disabled="option.name.length === 0 || option.values.length === 0"
            @click="saveOption(index)"
          />
          <Button
            v-tooltip.left="$t('Delete')"
            icon="fa fa-trash"
            style="width: 25px; height: 25px; padding: 0px;"
            raised
            outlined
            size="small"
            severity="danger"
            @click="deleteOption(index)"
          />
        </div>
        <div class="md:col-span-6 col-span-12 flex flex-col gap-3">
          <label :for="`option-${index}`">{{ $t('Option Name') }}</label>
          <div class="flex flex-row">
            <InputText
              :id="`option-${index}`"
              v-model="option.name"
              class="w-full"
              :invalid="checkAvailability"
              autocomplete="off"
            />
          </div>
        </div>
        <div class="md:col-span-6 col-span-12 flex flex-col gap-3">
          <label :for="`values-${index}`">{{ $t('Option Values') }}</label>
          <div class="flex flex-row">
            <AutoComplete
              v-model="option.values"
              multiple
              :typeahead="false"
              :input-id="`values-${index}`"
              :disabled="option.name.length === 0"
              class="w-full block"
              @option-select="option.saved = false"
              @option-unselect="option.saved = false"
            />
          </div>
        </div>
      </div>
      <div class="flex">
        <Button
          icon="fa fa-plus"
          text
          :label="$t('Add another option')"
          @click="addOption"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Button,
  InputText,
  AutoComplete,
} from "primevue";

import { computed, ref } from "vue";

// Define props
const props = defineProps<{
  modelValue: Array<{ name: string; values: string[]; saved: boolean }>;
}>();

// Define emits
const emit = defineEmits<{
  (e: "update:modelValue", value: Array<{ name: string; values: string[]; saved: boolean }>): void;
  (e: "option-deleted", option: { name: string; values: string[]; saved: boolean }): void;
}>();

const options = ref(props.modelValue);

// Add option
const addOption = () => {
  options.value.push({
    name: "",
    values: [],
    saved: false,
  });
};

// Save option
const saveOption = (index: number) => {
  const originalOptions = props.modelValue;
  originalOptions[index] = options.value[index];
  options.value[index].saved = true;
  emit("update:modelValue", options.value);
};

// Delete option
const deleteOption = (index: number) => {
  // Emit event to parent component
  emit("option-deleted", options.value[index]);
  options.value.splice(index, 1);
};

const checkAvailability = computed(() => {
  if (options.value.length === 0) {
    return true;
  }
  // check if some of the option names is duplicated
  const names = options.value.map((option) => option.name);
  return names.some((name, index) => names.indexOf(name) !== index);
});
</script>
