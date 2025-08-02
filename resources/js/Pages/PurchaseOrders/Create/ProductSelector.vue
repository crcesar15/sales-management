<template>
  <div>
    <Dialog
      v-model:visible="showProductModal"
      modal
      position="top"
      class="!mt-8"
      :style="{ width: '50rem' }"
      :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
      :closable="false"
    >
      <template #default>
        <div class="flex flex-col">
          <div class="form-group mt-2">
            <label for="product">Product</label>
            <Select
              id="product"
              v-model="product"
              class="w-full"
              :options="products"
              option-label="name"
              filter
              :invalid="productState === false"
            />
            <small
              v-if="productState === false"
              class="text-red-400 dark:text-red-300"
            >
              {{ $t('validations.required') }}
            </small>
          </div>
          <div class="flex flex-col md:flex-row justify-around items-center gap-2 mt-2">
            <div class="w-full">
              <div class="form-group">
                <label for="quantity">Quantity</label>
                <InputNumber
                  id="quantity"
                  v-model="quantity"
                  class="w-full"
                  show-buttons
                  :step="1"
                  :min="1"
                  :invalid="quantityState === false"
                />
                <small
                  v-if="quantityState === false"
                  class="text-red-400 dark:text-red-300"
                >
                  {{ $t('validations.required') }}
                </small>
              </div>
            </div>
            <div class="w-full">
              <div class="form-group">
                <label for="price">Unit Price</label>
                <InputNumber
                  id="price"
                  v-model="price"
                  class="w-full"
                  mode="currency"
                  currency="BOB"
                  show-buttons
                  :invalid="priceState === false"
                  :step="0.50"
                />
                <small
                  v-if="priceState === false"
                  class="text-red-400 dark:text-red-300"
                >
                  {{ $t('validations.required') }}
                </small>
              </div>
            </div>
            <div class="w-full">
              <div class="form-group">
                <label for="total">Total</label>
                <InputNumber
                  id="total"
                  v-model="total"
                  class="w-full"
                  disabled
                  mode="currency"
                  currency="BOB"
                />
              </div>
            </div>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <PButton
            label="Cancel"
            icon="fa fa-times"
            outlined
            @click="closeProductModal"
          />
          <PButton
            label="Save"
            icon="fa fa-save"
            @click="saveProduct"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script>
import {
  Dialog,
  Select,
  InputNumber,
  InputText,
  Button as PButton,
} from "primevue";

export default {
  components: {
    Dialog,
    Select,
    InputNumber,
    InputText,
    PButton,
  },
  props: {
    products: {
      type: Array,
      required: true,
    },
    showModal: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      product: null,
      showProductModal: false,
      price: 0,
      quantity: 1,
      submitted: false,
    };
  },
  computed: {
    total() {
      if (!this.price || !this.quantity) {
        return 0;
      }

      return this.price * this.quantity;
    },
    productState() {
      if (this.submitted) {
        return this.product ? null : false;
      }
      return null;
    },
    quantityState() {
      if (this.submitted) {
        return this.quantity > 0 ? null : false;
      }
      return null;
    },
    priceState() {
      if (this.submitted) {
        return this.price > 0 ? null : false;
      }
      return null;
    },
  },
  watch: {
    showModal(val) {
      this.showProductModal = val;
      if (val) {
        this.selectedProduct = null;
      }
    },
    product(val) {
      if (val) {
        this.price = val.price;
      }
    },
  },
  methods: {
    validForm() {
      if (!this.product) {
        return false;
      }

      if (this.quantity <= 0) {
        return false;
      }

      if (this.price <= 0) {
        return false;
      }

      return true;
    },
    saveProduct() {
      this.submitted = true;
      if (!this.validForm()) {
        return;
      }

      this.$emit("save", {
        product: this.product,
        quantity: this.quantity,
        price: this.price,
        total: this.total,
      });

      this.closeProductModal();
    },
    closeProductModal() {
      this.$emit("close");
      this.product = null;
      this.price = 0;
      this.quantity = 1;
    },
  },
};
</script>
