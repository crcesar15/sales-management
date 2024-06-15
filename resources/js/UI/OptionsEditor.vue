<template>
  <div>
    <div v-show="options.length === 0">
      <div class="grid">
        <div class="col-12">
          <PButton
            icon="fa fa-plus"
            text
            label="Add options like size or color"
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
        <div class="md:col-5 col-4 flex flex-column gap-3">
          <label>Option Name</label>
          <div class="grid flex-row">
            <div class="col">
              <InputText
                v-model="option.name"
                class="w-full"
                :invalid="checkAvailability"
              />
            </div>
          </div>
        </div>
        <div class="md:col-6 col-6 flex flex-column gap-3">
          <label>Option Values</label>
          <div class="grid flex-row">
            <div class="col">
              <OptionValue
                :value="option.values"
                :editable="option.name.length > 0"
                class="w-full"
                @update:modelValue="updateOption(index)"
              />
            </div>
          </div>
        </div>
        <div class="md:col-1 col-2 flex flex-wrap flex-row justify-content-center align-content-center">
          <Button
            v-show="option.edited"
            icon="fa fa-check"
            severity="success"
            outlined
            rounded
            size="small"
            @click="saveOption(index)"
          />
          <Button
            icon="fa fa-trash"
            severity="danger"
            outlined
            rounded
            size="small"
            @click="deleteOption(index)"
          />
        </div>
      </div>
      <div class="grid">
        <div class="col-12">
          <PButton
            icon="fa fa-plus"
            text
            label="Add another option"
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
import OptionValue from "./OptionValue.vue";

export default {
  components: {
    Button,
    OptionValue,
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
  mounted() {
    this.options = this.value;
  },
  methods: {
    addOption() {
      this.options.push({
        name: "",
        values: [],
        edited: false,
      });
    },
    updateOption(value, index) {
      console.log(index);
      this.options[index].values = value;
      this.options[index].edited = true;
    },
    saveOption(index) {
      const originalOptions = this.value;
      originalOptions[index] = this.options[index];
      this.options[index].edited = false;

      this.$emit("update:modelValue", originalOptions);
    },
    deleteOption(index) {
      this.options.splice(index, 1);
      this.$emit("update:modelValue", this.options);
    },
  },
};
</script>
