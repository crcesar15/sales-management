<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="$inertia.visit(route('catalog'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ $t('Edit Vendors') }}
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
      <div class="col-span-12">
        <Card class="mb-4">
          <template #title>
            {{ $t('Product') }}
          </template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="flex flex-col md:col-span-6 col-span-12">
                <label for="name">{{ $t('Name') }}</label>
                <InputText
                  id="name"
                  :value="(variant.name === variant.product.name) ? variant.name : `${variant.product.name} - ${variant.name}`"
                  disabled
                />
              </div>
              <div class="flex flex-col md:col-span-6 col-span-12">
                <label for="description">{{ $t('Description') }}</label>
                <Textarea
                  id="description"
                  rows="1"
                  :value="variant.product.description"
                  disabled
                />
              </div>
              <div class="flex flex-col lg:col-span-4 md:col-span-6 col-span-12">
                <label for="brand">{{ $t('Brand') }}</label>
                <InputText
                  id="brand"
                  :value="variant.product.brand.name"
                  disabled
                />
              </div>
              <div class="flex flex-col lg:col-span-4 md:col-span-6 col-span-12">
                <label for="categories">{{ $t('Categories') }}</label>
                <InputText
                  id="categories"
                  :value="variant.product.categories.map((category) => category.name).join(', ')"
                  disabled
                />
              </div>
              <div class="flex flex-col lg:col-span-4 md:col-span-6 col-span-12">
                <label for="identifier">{{ $t('Bar Code or Identifier') }}</label>
                <InputText
                  id="identifier"
                  :value="variant.identifier"
                  disabled
                />
              </div>
            </div>
          </template>
        </Card>
        <Card>
          <template #title>
            <div class="flex justify-between mb-3">
              <div>
                {{ $t('Vendors') }}
              </div>
              <div>
                <PButton
                  icon="fa fa-plus"
                  class="uppercase"
                  :label="$t('Add Vendor')"
                  @click="addVendor()"
                />
              </div>
            </div>
          </template>
          <template #content>
            <DataTable
              :value="catalog"
              table-class="border-t"
              resizable-columns
            >
              <template #empty>
                <p class="text-red-400 dark:text-red-300">
                  {{ $t('At least one provided must be added') }}
                </p>
              </template>
              <Column
                field="vendor"
                :header="$t('Name')"
              >
                <template #body="slotProps">
                  <div class="flex flex-col">
                    <Select
                      v-model="slotProps.data.vendor"
                      class="w-full"
                      :options="vendors"
                      :placeholder="$t('Vendor')"
                      option-label="fullname"
                      option-value="id"
                      filter
                      :loading="slotProps.data.vendorsLoading"
                      :fluid="true"
                      :invalid="v$.catalog.$each.$response.$errors[slotProps.index].vendor.length > 0"
                      @filter="searchVendors"
                    />
                    <small
                      v-if="v$.catalog.$each.$response.$errors[slotProps.index].vendor.length > 0"
                      class="text-red-400 dark:text-red-300"
                    >
                      {{ v$.catalog.$each.$response.$errors[slotProps.index].vendor[0].$message }}
                    </small>
                  </div>
                </template>
              </Column>
              <Column
                field="price"
                :header="$t('Price')"
              >
                <template #body="slotProps">
                  <div class="flex flex-col">
                    <InputNumber
                      v-model="slotProps.data.price"
                      class="w-full"
                      mode="currency"
                      currency="BOB"
                      :invalid="v$.catalog.$each.$response.$errors[slotProps.index].price.length > 0"
                    />
                    <small
                      v-if="v$.catalog.$each.$response.$errors[slotProps.index].price.length > 0"
                      class="text-red-400 dark:text-red-300"
                    >
                      {{ v$.catalog.$each.$response.$errors[slotProps.index].price[0].$message }}
                    </small>
                  </div>
                </template>
              </Column>
              <Column
                field="payment_terms"
                :header="$t('Payment Term')"
              >
                <template #body="slotProps">
                  <div class="flex flex-col">
                    <Select
                      v-model="slotProps.data.payment_terms"
                      class="w-full"
                      option-label="label"
                      option-value="value"
                      :options="[
                        { label: $t('Cash'), value: 'debit' },
                        { label: $t('Credit'), value: 'credit' },
                        { label: $t('Both'), value: 'both' },
                      ]"
                      :invalid="v$.catalog.$each.$response.$errors[slotProps.index].payment_terms.length > 0"
                    />
                    <small
                      v-if="v$.catalog.$each.$response.$errors[slotProps.index].payment_terms.length > 0"
                      class="text-red-400 dark:text-red-300"
                    >
                      {{ v$.catalog.$each.$response.$errors[slotProps.index].payment_terms[0].$message }}
                    </small>
                  </div>
                </template>
              </Column>
              <Column
                field="details"
                :header="$t('Details')"
              >
                <template #body="slotProps">
                  <Textarea
                    v-model="slotProps.data.details"
                    class="w-full"
                    rows="1"
                  />
                </template>
              </Column>
              <Column
                field="actions"
                :header="$t('Actions')"
                header-class="flex justify-center"
                class="flex justify-center"
              >
                <template #body="slotProps">
                  <PButton
                    v-tooltip.top="$t('Delete')"
                    icon="fa fa-trash"
                    text
                    raised
                    rounded
                    class="mb-2"
                    @click="removeVendor(slotProps.data)"
                  />
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script>
import Card from "primevue/card";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputNumber from "primevue/inputnumber";
import { useVuelidate } from "@vuelidate/core";
import {
  helpers, required, minValue,
  createI18nMessage,
} from "@vuelidate/validators";
import AppLayout from "../../../Layouts/admin.vue";
import i18n from "../../../app";

export default {
  components: {
    Card,
    PButton,
    InputText,
    Textarea,
    Select,
    DataTable,
    Column,
    InputNumber,
  },
  layout: AppLayout,
  props: {
    variant: {
      type: Object,
      required: true,
    },
    savedCatalog: {
      type: Array,
      required: true,
    },
    savedVendors: {
      type: Array,
      required: true,
    },
  },
  setup() {
    return {
      v$: useVuelidate(),
    };
  },
  data() {
    return {
      catalog: [{
        vendorsLoading: false,
        vendor: null,
        price: 0,
        payment_terms: "debit",
        details: "",
      }],
      vendors: [],
    };
  },
  mounted() {
    this.catalog = this.savedCatalog.map((vendor) => ({
      vendorsLoading: false,
      vendor: vendor.id,
      price: vendor.pivot.price,
      payment_terms: vendor.pivot.payment_terms,
      details: vendor.pivot.details,
    }));

    this.vendors = this.savedVendors;
  },
  validations() {
    const { t } = i18n.global;

    const withI18nMessage = createI18nMessage({
      t,
      messagesPath: "validations",
    });

    return {
      catalog: {
        required: withI18nMessage(required),
        $each: helpers.forEach({
          vendor: {
            required: withI18nMessage(required, { messagePath: () => ("validations.required") }),
          },
          price: {
            required: withI18nMessage(required, { messagePath: () => ("validations.required") }),
            minValue: withI18nMessage(minValue(0.5), { messagePath: () => ("validations.minValue") }),
          },
          payment_terms: {
            required: withI18nMessage(required, { messagePath: () => ("validations.required") }),
          },
        }),
      },
    };
  },
  methods: {
    searchVendors(event) {
      this.vendorsLoading = true;

      axios
        .get(route("api.vendors"), {
          params: {
            per_page: 10,
            page: 1,
            order_by: "fullname",
            order_direction: "asc",
            filter: event.value.toLowerCase(),
          },
        })
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
    submit() {
      this.v$.$touch();

      if (!this.v$.$invalid) {
        const selectedVendors = this.catalog.map((item) => ({
          id: item.vendor,
          price: item.price,
          payment_terms: item.payment_terms,
          details: item.details,
        }));

        // check if any of the selected vendors is duplicated
        const duplicates = selectedVendors.filter((vendor, index, self) => (
          index !== self.findIndex((t) => (
            t.id === vendor.id
          ))
        ));

        if (duplicates.length > 0) {
          this.$toast.add({
            severity: "error",
            summary: this.$t("Error"),
            detail: this.$t("You have selected the same vendor more than once"),
            life: 3000,
          });

          return;
        }

        axios.put(
          route("api.variants.vendors.update", this.variant.id),
          { vendors: selectedVendors },
        )
          .then(() => {
            this.$toast.add({
              severity: "success",
              summary: this.$t("Success"),
              detail: this.$t("Vendors updated successfully"),
              life: 3000,
            });

            this.$inertia.visit(route("catalog"));
          })
          .catch((error) => {
            this.$toast.add({
              severity: "error",
              summary: this.$t("Error"),
              detail: error.response.data.message,
              life: 3000,
            });
          });
      } else {
        this.$toast.add({
          severity: "error",
          summary: this.$t("Error"),
          detail: this.$t("Please review the errors in the form"),
          life: 3000,
        });
      }
    },
    addVendor() {
      this.catalog.push({
        vendorsLoading: false,
        vendor: null,
        price: 0,
        payment_terms: "debit",
        details: "",
      });
    },
    removeVendor(vendor) {
      const index = this.catalog.indexOf(vendor);
      this.catalog.splice(index, 1);
    },
  },
};
</script>
