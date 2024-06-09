<template>
  <AutoComplete
    :input="name"
    :suggestions="suggestions"
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
    input: {
      type: String,
      default: () => "",
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
    this.name = this.input;
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
      this.selectedItems.push(e.value);
      this.$emit("update", this.selectedItems);
    },
    removeValue(e) {
      this.selectedItems = this.selectedItems.filter(
        (item) => item !== e.value,
      );
      this.$emit("update", this.selectedItems);
    },
  },
};
</script>
