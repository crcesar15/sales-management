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
        <div class="col-5 flex flex-column gap-3">
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
        <div class="col-6 flex flex-column gap-3">
          <label>Option Values</label>
          <div class="grid flex-row">
            <div class="col">
              <OptionValue
                v-model="option.values"
                class="w-full"
              />
            </div>
          </div>
        </div>
        <div class="col-1 flex flex-column justify-content-center">
          <Button
            icon="fa fa-trash"
            severity="contrast"
            text
            @click="() => options.splice(index, 1)"
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
    input: {
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
      handler(val) {
        console.log(val);
        this.$emit("custom", val);
      },
      deep: true,
    },
  },
  mounted() {
    this.options = this.input;
  },
  methods: {
    addOption() {
      this.options.push({
        name: "",
        values: [],
      });
    },
  },
};
</script>
