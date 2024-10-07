<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          @click="$inertia.visit(route('catalog'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ $t('Edit Suppliers') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <PButton
          icon="fa fa-save"
          :label="$t('Save')"
          style="text-transform: uppercase"
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
                <label for="identifier">{{ $t('Identifier') }}</label>
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
                {{ $t('Suppliers') }}
              </div>
              <div>
                <PButton
                  icon="fa fa-plus"
                  size="small"
                  :label="$t('Add Supplier')"
                  @click="addSupplier()"
                />
              </div>
            </div>
          </template>
          <template #content>
            <DataTable
              :value="catalog"
              table-class="border-surface border"
              resizable-columns
            >
              <Column
                field="supplier"
                :header="$t('Name')"
              >
                <template #body="slotProps">
                  <div class="flex flex-col">
                    <AutoComplete
                      v-model="slotProps.data.supplier"
                      dropdown
                      class="w-full"
                      force-selection
                      :suggestions="suppliers"
                      :placeholder="$t('Supplier')"
                      option-label="fullname"
                      :loading="slotProps.data.suppliersLoading"
                      :fluid="true"
                      :invalid="v$.catalog.$each.$response.$errors[slotProps.index].supplier.length > 0"
                      @complete="searchSuppliers"
                    />
                    <small
                      v-if="v$.catalog.$each.$response.$errors[slotProps.index].supplier.length > 0"
                      class="text-red-400 dark:text-red-300"
                    >
                      {{ v$.catalog.$each.$response.$errors[slotProps.index].supplier[0].$message }}
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
                :header="$t('Payment Terms')"
              >
                <template #body="slotProps">
                  <div class="flex flex-col">
                    <Select
                      v-model="slotProps.data.payment_terms"
                      class="w-full"
                      option-label="label"
                      option-value="value"
                      :options="[
                        { label: $t('Debit'), value: 'debit' },
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
              >
                <template #body="slotProps">
                  <PButton
                    v-tooltip.top="$t('View Supplier')"
                    icon="fa-solid fa-eye"
                    text
                    size="small"
                    :disabled="!slotProps.data.supplier"
                    @click="viewSupplier(slotProps.data)"
                  />
                  <PButton
                    v-tooltip.top="$t('Remove Supplier')"
                    icon="fa fa-trash"
                    text
                    size="small"
                    @click="removeSupplier(slotProps.data)"
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
import { Inertia } from "@inertiajs/inertia";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputNumber from "primevue/inputnumber";
import AutoComplete from "primevue/autocomplete";
import { useVuelidate } from "@vuelidate/core";
import {
  helpers, required, minLength, minValue, requiredIf,
  createI18nMessage,
} from "@vuelidate/validators";
import AppLayout from "../../../layouts/admin.vue";
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
    AutoComplete,
  },
  layout: AppLayout,
  props: {
    variant: {
      type: Object,
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
        suppliersLoading: false,
        supplier: null,
        price: 0,
        payment_terms: "debit",
        details: "",
      }],
      suppliers: [],
    };
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
          supplier: {
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
    searchSuppliers(event) {
      this.suppliersLoading = true;

      axios
        .get(route("api.suppliers"), {
          params: {
            per_page: 10,
            page: 1,
            order_by: "fullname",
            order_direction: "asc",
            filter: event.query.toLowerCase(),
          },
        })
        .then((response) => {
          this.suppliers = response.data.data;
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
          this.suppliersLoading = false;
        });
    },
    submit() {
      this.v$.$touch();

      if (!this.v$.$error) {
        return 1;
      }

      const data = {
        catalog: this.catalog.map((item) => ({
          supplier_id: item.supplier.id,
          price: item.price,
          payment_terms: item.payment_terms,
          details: item.details,
        })),
      };

      Inertia.post(route("catalog.suppliers.update", this.variant.id), data);
    },
    addSupplier() {
      this.catalog.push({
        suppliersLoading: false,
        supplier: null,
        price: 0,
        payment_terms: "debit",
        details: "",
      });
    },
    removeSupplier(supplier) {
      const index = this.catalog.indexOf(supplier);
      this.catalog.splice(index, 1);
    },
  },
};
</script>
