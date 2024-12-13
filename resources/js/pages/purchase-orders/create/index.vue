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
        <Card>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <div class="flex items-end">
                    <label for="vendor">{{ $t('Vendor') }}</label>
                    <PButton
                      v-show="vendor"
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
                          <strong>{{ $t('Fullname') }}:</strong> {{ selectedVendor.fullname }} <br>
                          <strong>{{ $t('Email') }}:</strong> {{ selectedVendor.email }} <br>
                          <strong>{{ $t('Phone') }}:</strong> {{ selectedVendor.phone }} <br>
                          <strong>{{ $t('Address') }}:</strong> {{ selectedVendor.address }} <br>
                          <strong>{{ $t('Details') }}:</strong> {{ selectedVendor.details }} <br>
                        </p>
                      </div>
                    </Popover>
                  </div>
                  <Select
                    id="vendor"
                    v-model="vendor"
                    class="w-full"
                    :options="vendors"
                    :placeholder="$t('Select a Vendor')"
                    option-value="id"
                    option-label="fullname"
                    filter
                    :loading="vendorsLoading"
                    :invalid="v$.vendor.$invalid && v$.vendor.$dirty"
                    @filter="searchVendors"
                  />
                  <small
                    v-if="v$.vendor.$invalid && v$.vendor.$dirty"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ v$.vendor.$errors[0].$message }}
                  </small>
                </div>
                <DataTable
                  v-show="vendor"
                  data-key="id"
                  :value="items"
                  edit-mode="cell"
                >
                  <template #header>
                    <div class="flex justify-between items-center">
                      <label for="Products">{{ $t('Products') }}</label>
                      <PButton
                        icon="fa fa-plus"
                        :label="$t('Product')"
                        raised
                        style="text-transform: uppercase"
                        size="small"
                        @click="showSelectProductModal = true"
                      />
                    </div>
                  </template>
                  <Column
                    field="product"
                    header="Product"
                  >
                    <template #body="{data, field}">
                      <p>{{ data[field].name }}</p>
                    </template>
                    <template #editor="{data, field}">
                      <Select
                        v-model="data[field]"
                        :options="availableProducts"
                        option-label="name"
                      />
                    </template>
                  </Column>
                  <Column
                    field="quantity"
                    header="Quantity"
                  >
                    <template #body="{data, field}">
                      <p>{{ data[field] }}</p>
                    </template>
                    <template #editor="{data, field}">
                      <InputNumber
                        v-model="data[field]"
                        show-buttons
                        :step="1"
                        :min="1"
                      />
                    </template>
                  </Column>
                  <Column
                    field="unit_price"
                    header="Unit Price"
                  >
                    <template #body="{data, field}">
                      <p>BOB. {{ data[field] }}</p>
                    </template>
                    <template #editor="{data, field}">
                      <InputNumber
                        v-model="data[field]"
                        mode="currency"
                        currency="BOB"
                        show-buttons
                        :step="0.50"
                      />
                    </template>
                  </Column>
                  <Column
                    field="subtotal"
                    header="SubTotal"
                  >
                    <template #body="{data, field}">
                      <p>{{ data[field] }}</p>
                    </template>
                  </Column>
                  <template #empty>
                    <div class="flex justify-center">
                      <p>{{ $t('Please add a product') }}</p>
                    </div>
                  </template>
                </DataTable>
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
  DataTable,
  Button as PButton,
  Card,
  Select,
  DatePicker,
  Popover,
  Column,
  InputNumber,
} from "primevue";
import ProductSelector from "./ProductSelector.vue";

import AppLayout from "../../../layouts/admin.vue";
import i18n from "../../../app";

export default {
  components: {
    PButton,
    Card,
    Select,
    DatePicker,
    Popover,
    DataTable,
    Column,
    InputNumber,
    ProductSelector,
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
      vendor: "",
      vendorsLoading: false,
      vendors: [],
      status: "draft",
      orderDate: "",
      expectedArrivalDate: "",
      items: [],
      availableProducts: [],
      showSelectProductModal: false,
    };
  },
  computed: {
    currentDate() {
      return new Date(this.date);
    },
  },
  watch: {
    vendor(value) {
      this.fetchProductByVendor(value);
      this.selectedVendor = this.vendors.find((vendor) => vendor.id === value);
    },
  },
  mounted() {
    this.orderDate = this.date;
    this.searchVendors();
  },
  methods: {
    fetchProductByVendor(id) {
      axios.get(route("api.vendors.variants", id)).then((response) => {
        this.availableProducts = response.data.data;
      });
    },
    searchVendors(event = null) {
      this.vendorsLoading = true;

      const body = {
        params: {
          per_page: 10,
          page: 1,
          order_by: "fullname",
          order_direction: "asc",
        },
      };

      if (event) {
        body.params.filter = event.value.toLowerCase();
      }

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
    },
    toggleVendorInfo(event) {
      this.$refs.vendorInfo.toggle(event);
    },
    openVendor(id) {
      // open vendor in a new tab
      window.open(route("vendors.edit", id), "_blank");
    },
    addProduct(item) {
      this.items.push({
        id: Math.random().toString(36).substr(2, 9),
        product: item.product,
        quantity: item.quantity,
        unit_price: item.price,
        subtotal: item.total,
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
