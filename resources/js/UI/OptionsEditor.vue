<template>
  <div>
    <div v-show="options.length === 0">
      <div class="flex">
        <PButton
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
            icon="fa fa-floppy-disk"
            style="width: 25px; height: 25px; padding: 0px; margin-right: 4px;"
            raised
            outlined
            size="small"
            :disabled="option.name.length === 0 || option.values.length === 0"
            @click="saveOption(index)"
          />
          <Button
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
        <PButton
          icon="fa fa-plus"
          text
          :label="$t('Add another option')"
          @click="addOption"
        />
      </div>
    </div>
  </div>
</template>

<script>

import Button from "primevue/button";
import InputText from "primevue/inputtext";
import AutoComplete from "primevue/autocomplete";

export default {
  components: {
    Button,
    AutoComplete,
    InputText,

  },
  props: {
    modelValue: {
      type: Array,
      required: true,
    },
  },
  data() {
    return {
      options: this.modelValue,
    };
  },
  computed: {
    checkAvailability() {
      if (this.options.length === 0) {
        return true;
      }
      // check if some of the option names is duplicated
      const names = this.options.map((option) => option.name);
      return names.some((name, index) => names.indexOf(name) !== index);
    },
  },
  watch: {
    modelValue: {
      handler(value) {
        this.options = value;
      },
      deep: true,
    },
    options: {
      handler(value) {
        this.$emit("update:modelValue", value);
      },
      deep: true,
    },
  },
  methods: {
    addOption() {
      this.options.push({
        name: "",
        values: [],
        saved: false,
      });
    },
    saveOption(index) {
      const originalOptions = this.modelValue;
      originalOptions[index] = this.options[index];
      this.options[index].saved = true;
    },
    deleteOption(index) {
      this.$emit("option-deleted", this.options[index]);
      this.options.splice(index, 1);
    },
  },
};
</script>
