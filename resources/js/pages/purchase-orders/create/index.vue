<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="$inertia.visit(route('purchase-orders'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ $t('Create Purchase Order') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <PButton
          icon="fa fa-save"
          :label="$t('Save')"
          style="text-transform: uppercase"
          raised
          @click="submit()"
        />
      </div>
    </div>
    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <div class="flex items-end">
                    <label for="vendor">{{ $t('Vendor') }}</label>
                    <PButton
                      v-if="selectedVendor?.id"
                      v-tooltip.top="$t('View Vendor')"
                      icon="fa fa-eye"
                      size="small"
                      text
                      class="!p-0 !my-0 ml-1 !w-5 !h-5"
                      @click="toggleVendorInfo"
                    />
                    <Popover ref="vendorInfo">
                      <div>
                        <div class="flex justify-between">
                          <h4 class="text-lg font-bold">
                            {{ $t('Vendor Information') }}
                          </h4>
                          <PButton
                            v-tooltip.top="$t('Show Vendor')"
                            icon="fa fa-up-right-from-square"
                            size="small"
                            text
                            class="!p-0 !my-0 ml-1 !w-5 !h-5"
                            @click="openVendor(selectedVendor.id)"
                          />
                        </div>
                        <p>
                          <strong>{{ $t('Fullname') }}:</strong> {{ selectedVendor?.fullname }} <br>
                          <strong>{{ $t('Email') }}:</strong> {{ selectedVendor?.email }} <br>
                          <strong>{{ $t('Phone') }}:</strong> {{ selectedVendor?.phone }} <br>
                          <strong>{{ $t('Address') }}:</strong> {{ selectedVendor?.address }} <br>
                          <strong>{{ $t('Details') }}:</strong> {{ selectedVendor?.details }} <br>
                        </p>
                      </div>
                    </Popover>
                  </div>
                  <AutoComplete
                    id="vendor"
                    v-model="selectedVendor"
                    class="w-full"
                    input-class="w-full"
                    force-selection
                    option-label="fullname"
                    :delay="500"
                    :invalid="v$.vendor.$invalid && v$.vendor.$dirty"
                    :loading="vendorsLoading"
                    :placeholder="$t('Select a Vendor')"
                    :suggestions="vendors"
                    @complete="searchVendors"
                  />
                  <small
                    v-if="v$.vendor.$invalid && v$.vendor.$dirty"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ v$.vendor.$errors[0].$message }}
                  </small>
                </div>
              </div>
            </div>
          </template>
        </Card>
        <Card
          v-show="selectedVendor?.id"
          class="mb-4"
        >
          <template #content>
            <div>
              <label for="vendor">{{ $t('Products') }}</label>
              <div class="flex w-full">
                <AutoComplete
                  id="product-selector"
                  v-model="selectedProduct"
                  class="w-full my-2"
                  force-selection
                  input-class="w-full"
                  option-label="name"
                  size="large"
                  :delay="500"
                  :placeholder="$t('Select a Product')"
                  :suggestions="availableProducts"
                  @complete="fetchProductByVendor"
                  @option-select="addProduct"
                >
                  <template #option="slotProps">
                    <div class="flex justify-between w-full">
                      <div>
                        {{ slotProps.option.name }}
                        <p class="text-gray-500">
                          Stock: {{ slotProps.option.stock }}
                        </p>
                      </div>
                      <div>
                        <p class="text-gray-500">
                          Bs. {{ slotProps.option.price }}
                        </p>
                      </div>
                    </div>
                  </template>
                </AutoComplete>
              </div>
              <order-grid
                v-model="items"
                class="mt-2"
              />
              <div
                v-show="items.length"
                class="grid grid-cols-12 mt-2"
              >
                <div
                  class="col-span-12 md:col-span-6 md:col-start-7"
                >
                  <label
                    for="subtotal"
                  >
                    Subtotal
                  </label>
                  <InputNumber
                    id="subtotal"
                    v-model="subtotal"
                    class="mt-1 mb-2"
                    mode="currency"
                    currency="BOB"
                    fluid
                    readonly
                  />
                  <label
                    for="percentage-discount"
                  >
                    Discount (Percentage)
                  </label>
                  <InputNumber
                    id="percentage-discount"
                    v-model="percentageDiscount"
                    class="mt-1 mb-2"
                    prefix="%"
                    :min="0"
                    :max="100"
                    fluid
                    @value-change="setAmountDiscount"
                  />
                  <label
                    for="amount-discount"
                  >
                    Discount (Amount)
                  </label>
                  <InputNumber
                    id="amount-discount"
                    v-model="amountDiscount"
                    class="mt-1 mb-2"
                    mode="currency"
                    currency="BOB"
                    :max="subtotal"
                    :min="0"
                    fluid
                    @value-change="setPercentageDiscount"
                  />
                  <label
                    for="total"
                  >
                    Total
                  </label>
                  <InputNumber
                    id="total"
                    v-model="total"
                    class="mt-1 mb-2"
                    mode="currency"
                    currency="BOB"
                    fluid
                    readonly
                  />
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
      <div class="md:col-span-4 col-span-12">
        <Card>
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="status">{{ $t('Status') }}</label>
              <Select
                v-model="status"
                :options="[
                  { name: $t('Draft'), value: 'draft' },
                  { name: $t('Pending'), value: 'pending' },
                  { name: $t('Paid'), value: 'paid' },
                  { name: $t('Cancelled'), value: 'cancelled' }
                ]"
                option-label="name"
                option-value="value"
              />
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="order-date">{{ $t('Order Date') }}</label>
              <DatePicker
                id="order-date"
                v-model="orderDate"
                class="w-full"
                show-icon
                :min-date="currentDate"
              />
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="expected-arrival-date">{{ $t('Expected Arrival Date') }}</label>
              <DatePicker
                id="expected-arrival-date"
                v-model="expectedArrivalDate"
                class="w-full"
                show-icon
                :min-date="currentDate"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>
    <ProductSelector
      :products="availableProducts"
      :show-modal="showSelectProductModal"
      @save="addProduct"
      @close="showSelectProductModal = false"
    />
  </div>
</template>

<script>
import { useVuelidate } from "@vuelidate/core";
import {
  createI18nMessage,
  required,
} from "@vuelidate/validators";

import {
  Button as PButton,
  Card,
  Select,
  DatePicker,
  Popover,
  AutoComplete,
  InputNumber,
} from "primevue";
import ProductSelector from "./ProductSelector.vue";
import OrderGrid from "./OrderGrid.vue";

import AppLayout from "../../../layouts/admin.vue";
import i18n from "../../../app";

export default {
  components: {
    PButton,
    Card,
    Select,
    DatePicker,
    Popover,
    ProductSelector,
    OrderGrid,
    AutoComplete,
    InputNumber,
  },
  layout: AppLayout,
  props: {
    date: {
      type: String,
      default: "",
    },
    time: {
      type: String,
      default: "",
    },
  },
  setup() {
    return {
      v$: useVuelidate(),
    };
  },
  data() {
    return {
      selectedVendor: null,
      vendorsLoading: false,
      vendors: [],
      status: "draft",
      orderDate: "",
      expectedArrivalDate: "",
      items: [],
      availableProducts: [],
      selectedProduct: null,
      showSelectProductModal: false,
      amountDiscount: 0,
      percentageDiscount: 0,
    };
  },
  computed: {
    currentDate() {
      return new Date(this.date);
    },
    subtotal() {
      return this.items.reduce((acc, item) => acc + (item.quantity * item.unit_price), 0);
    },
    total() {
      const discount = this.amountDiscount || (this.subtotal * this.percentageDiscount / 100);
      return this.subtotal - discount;
    },
  },
  mounted() {
    this.orderDate = this.date;
    this.searchVendors();
  },
  methods: {
    setAmountDiscount() {
      this.$nextTick(() => {
        this.amountDiscount = ((this.subtotal * this.percentageDiscount) / 100).toFixed(2);
      });
    },
    setPercentageDiscount() {
      this.$nextTick(() => {
        this.percentageDiscount = ((this.amountDiscount / this.subtotal) * 100).toFixed(2);
      });
    },
    fetchProductByVendor(event) {
      if (event.query.trim().length) {
        const vendorId = this.selectedVendor.id;
        axios.get(route("api.vendors.variants", {
          vendor: vendorId, per_page: 10, includes: "product,vendors", order_by: "name", order_direction: "asc",
        }))
          .then((response) => {
            this.availableProducts = response.data.data;
          });
      }
    },
    searchVendors(event = null) {
      if (event !== null && event.query.trim().length) {
        this.vendorsLoading = true;
        const body = {
          params: {
            per_page: 10,
            page: 1,
            order_by: "fullname",
            order_direction: "asc",
            filter: event.query.toLowerCase(),
          },
        };

        axios
          .get(route("api.vendors"), body)
          .then((response) => {
            this.vendors = response.data.data;
          })
          .catch((error) => {
            this.$toast.add({
              severity: "error",
              summary: this.$t("Error"),
              detail: error.response.data.message,
              life: 3000,
            });
          })
          .finally(() => {
            this.vendorsLoading = false;
          });
      }
    },
    toggleVendorInfo(event) {
      this.$refs.vendorInfo.toggle(event);
    },
    openVendor(id) {
      // open vendor in a new tab
      window.open(route("vendors.edit", id), "_blank");
    },
    addProduct(event) {
      const item = event.value;

      // check if the product is already in the list
      const index = this.items.findIndex((i) => i.product.id === item.id);

      if (index === -1) {
        this.items.push({
          id: Math.random().toString(36).substring(2, 11),
          product: item,
          quantity: 1,
          unit_price: item.price,
          subtotal: item.price,
        });
      } else {
        this.$toast.add({
          severity: "warn",
          summary: this.$t("Warning"),
          detail: this.$t("Product already added"),
          life: 3000,
        });
      }

      this.$nextTick(() => {
        this.selectedProduct = null;
      });
    },
    productSubTotal(quantity, price) {
      if (!quantity || !price) {
        return 0;
      }

      return quantity * price;
    },
  },
  validations() {
    const { t } = i18n.global;

    const withI18nMessage = createI18nMessage({
      t,
      messagesPath: "validations",
    });

    return {
      vendor: {
        required: withI18nMessage(required),
      },
    };
  },
};
</script>
