<template>
  <div>
    <div v-show="options.length === 0">
      <div class="grid">
        <div class="col-12">
          <PButton
            icon="fa fa-plus"
            text
            :label="$t('Add options like size or color')"
            @click="addOption"
          />
        </div>
      </div>
    </div>
    <div v-show="options.length > 0">
      <div
        v-for="(option,index) in options"
        :key="index"
        class="w-full grid flex-row mb-3"
      >
        <div
          class="col-12 flex flex-wrap flex-row justify-content-end"
          style="margin-bottom: -35px;"
        >
          <Button
            v-show="!option.saved"
            icon="fa fa-floppy-disk"
            style="width: 25px; height: 25px; padding: 0px; margin-right: 4px;"
            outlined
            size="small"
            :disabled="option.name.length === 0 || option.values.length === 0"
            @click="saveOption(index)"
          />
          <Button
            icon="fa fa-trash"
            style="width: 25px; height: 25px; padding: 0px;"
            outlined
            size="small"
            @click="deleteOption(index)"
          />
        </div>
        <div class="md:col-6 col-12 flex flex-column gap-3">
          <label :for="`option-${index}`">{{ $t('Option Name') }}</label>
          <div class="grid flex-row">
            <div class="col">
              <InputText
                :id="`option-${index}`"
                v-model="option.name"
                class="w-full"
                :invalid="checkAvailability"
              />
            </div>
          </div>
        </div>
        <div class="md:col-6 col-12 flex flex-column gap-3">
          <label :for="`values-${index}`">{{ $t('Option Values') }}</label>
          <div class="grid flex-row">
            <div class="col">
              <Chips
                v-model="option.values"
                :input-id="`values-${index}`"
                :disabled="option.name.length === 0"
                separator=","
                class="w-full block"
                @add="option.saved = false"
                @remove="option.saved = false"
              />
            </div>
          </div>
        </div>
      </div>
      <div class="grid">
        <div class="col-12">
          <PButton
            icon="fa fa-plus"
            text
            :label="$t('Add another option')"
            @click="addOption"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Chips from "primevue/chips";

export default {
  components: {
    Button,
    Chips,
    InputText,

  },
  props: {
    value: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      options: [],
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
    options: {
      handler(value) {
        this.$emit("update:modelValue", value);
      },
      deep: true,
    },
  },
  mounted() {
    this.options = this.value;
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
      const originalOptions = this.value;
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
