<template>
  <AutoComplete
    :model-value="name"
    :suggestions="suggestions"
    :disabled="!editable"
    multiple
    class="w-full block"
    @complete="search"
    @item-select="addValue"
    @item-unselect="removeValue"
  />
</template>

<script>
import AutoComplete from "primevue/autocomplete";

export default {
  components: {
    AutoComplete,
  },
  props: {
    value: {
      type: String,
      default: () => "",
    },
    editable: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      name: [],
      suggestions: [],
      selectedItems: [],
    };
  },
  mounted() {
    this.name = this.value;
  },
  methods: {
    search(e) {
      if (!e.query) {
        this.suggestions = [];
      } else {
        this.suggestions = [e.query];
      }
    },
    addValue(e) {
      this.name.push(e.value);
      this.$emit("update:modelValue", this.name);
    },
    removeValue(e) {
      this.name = this.name.filter(
        (item) => item !== e.value,
      );
      this.$emit("update:modelValue", this.name);
    },
  },
};
</script>
