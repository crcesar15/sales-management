<template>
  <div>
    <Dialog
      v-model:visible="visible"
      header="Product Editor"
      modal
      @hide="clearSelection"
    >
      <div
        class="p-fluid"
      >
        <div
          class="p-field"
        >
          <label
            for="price"
          >
            Price
          </label>
          <InputText
            id="price"
            v-model="price"
          />
        </div>
        <div
          class="p-field"
        >
          <label
            for="stock"
          >
            Stock
          </label>
          <InputText
            id="stock"
            v-model="stock"
          />
        </div>
      </div>
      <template #footer>
        <div
          class="flex flex-wrap justify-content-end"
        >
          <PButton
            severity="secondary"
            label="Close"
            @click="closeModal"
          />
          <PButton
            severity="primary"
            label="Save"
            @click="save"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script>
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import Dialog from "primevue/dialog";

export default {
  components: {
    PButton,
    InputText,
    Dialog,
  },
  props: {
    product: {
      type: Object,
      required: true,
    },
    showDialog: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      visible: false,
      price: 0,
      stock: 0,
    };
  },
  watch: {
    showDialog(value) {
      this.visible = value;
      if (value) {
        this.price = this.product.price;
        this.stock = this.product.stock;
      }
    },
  },
  methods: {
    closeModal() {
      this.visible = false;
    },
    clearSelection() {
      this.$emit("clearSelection");
    },
    save() {
      this.$emit("save", this.product.id, {
        price: this.price,
        stock: this.stock,
      });
      this.visible = false;
    },
  },
};
</script>
