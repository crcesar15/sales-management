<template>
  <Chips
    :model-value="options"
    :disabled="!editable"
    multiple
    class="w-full block"
  />
</template>

<script>
import Chips from "primevue/chips";

export default {
  components: {
    Chips,
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
      options: "",
      selectedItems: [],
    };
  },
  mounted() {
    this.options = this.value;
  },
  methods: {
    search(e) {
      if (!e.query) {
        this.suggestions = [];
      } else {
        this.suggestions = [e.query];
      }
    },
  },
};
</script>
