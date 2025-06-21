<template>
  <div>
    <Dialog
      v-model:visible="showModal"
      modal
      :header="$t('Vendors')"
      :style="{ width: '50rem' }"
      :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
      :close-on-escape="true"
      @hide="close"
    >
      <template #header>
        <h3 class="text-2xl">
          {{ $t(modalTitle) }}
        </h3>
      </template>
      <template #default>
        <div class="grid grid-cols-12 gap-4">
          <div class="col-span-12">
            <label for="product-name">{{ $t("Product") }}</label>
            <Select
              id="product-name"
              v-model="productId"
              class="w-full"
              :options="products"
              :placeholder="$t('Product')"
              option-label="name"
              option-value="id"
              filter
              :loading="productsLoading"
              :fluid="true"
              @filter="searchProducts"
            >
              <template #option="slotProps">
                <div class="flex flex-row gap-2">
                  <p>{{ slotProps.option.name }}</p>
                  <span class="font-bold w-fit">({{ slotProps.option.variant }})</span>
                </div>
              </template>
            </Select>
          </div>
          <div class="col-span-12 flex flex-col">
            <label for="product-price">{{ $t("Price") }}</label>
            <InputNumber
              id="product-price"
              v-model="price"
              mode="currency"
              currency="BOB"
              :readonly="false"
            />
          </div>
          <div class="col-span-12 flex flex-col">
            <label for="product-payment-term">{{ $t("Payment Term") }}</label>
            <Select
              id="product-payment-term"
              v-model="paymentTerm"
              :options="payment_terms"
              option-value="value"
              option-label="label"
            />
          </div>
          <div class="col-span-12 flex flex-col">
            <label for="product-details">{{ $t("Details") }}</label>
            <Textarea
              id="product-details"
              v-model="details"
              :readonly="false"
            />
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <PButton
            :label="$t('Cancel')"
            icon="fa fa-times"
            outlined
            @click="close"
          />
          <PButton
            :label="$t('Save')"
            icon="fa fa-save"
            @click="save"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script>
import Select from "primevue/select";
import Dialog from "primevue/dialog";
import PButton from "primevue/button";
import Textarea from "primevue/textarea";
import InputNumber from "primevue/inputnumber";
import Tag from "primevue/tag";
import i18n from "../../../app";

export default {
  components: {
    Textarea,
    Select,
    PButton,
    Dialog,
    InputNumber,
    Tag,
  },
  props: {
    product: {
      type: Object,
      required: true,
      default: () => ({
        id: null,
        price: 0,
        payment_terms: null,
        details: null,
      }),
    },
    show: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      productId: "",
      paymentTerm: "",
      details: "",
      price: "",
      payment_terms: [
        { label: "Cash", value: "debit" },
        { label: "Credit", value: "credit" },
      ],
      showModal: false,
      products: [],
      productsLoading: false,
    };
  },
  computed: {
    modalTitle() {
      return this.product?.id ? "Edit Product" : "Add Product";
    },
  },
  watch: {
    product: {
      handler(val) {
        if (val?.id) {
          this.products = [val];
        } else {
          this.searchProducts(null);
        }

        this.productId = val?.id;
        this.price = val?.price;
        this.paymentTerm = val?.payment_terms;
        this.details = val?.details;
      },
      deep: true,
    },
    show: {
      handler(val) {
        this.showModal = val;
      },
      deep: true,
    },
  },
  methods: {
    close() {
      this.$emit("close");
    },
    save() {
      const formattedProduct = {
        id: this.productId,
        price: this.price,
        payment_terms: this.paymentTerm,
        details: this.details,
      };
      this.$emit("save", formattedProduct);
    },
    searchProducts(event) {
      this.productsLoading = true;

      axios.get(
        route("api.variants"),
        {
          params: {
            per_page: 10,
            page: 1,
            order_by: "name",
            order_direction: "asc",
            filter: event ? event.value.toLowerCase() : "",
            includes: "product",
          },
        },
      ).then((response) => {
        let products = response.data.data;

        if (this.product?.id) {
          // add the selected product to the list
          products = products.filter((product) => product.id !== this.product.id);
        }

        this.products = products;
        this.productsLoading = false;
      });
    },
  },
};
</script>
