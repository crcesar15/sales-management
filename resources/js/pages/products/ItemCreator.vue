<template>
  <AppLayout>
    <Toast />
    <div class="col-12 flex">
      <PButton
        icon="fa fa-arrow-left"
        text
        severity="secondary"
        @click="$inertia.visit(route('products'))"
      />
      <h4 class="ml-2">
        Add Product
      </h4>
    </div>
    <div class="grid">
      <div class="md:col-8 col-12">
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-column gap-2 mb-3">
              <label for="name">Name</label>
              <InputText
                id="name"
                v-model="name"
              />
            </div>
            <div class="flex flex-column gap-2 mb-3">
              <label for="description">Description</label>
              <Textarea
                id="description"
                v-model="description"
              />
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            Media
          </template>
          <template #content>
            <div class="flex flex-column">
              <MediaManager
                :files="files"
                @upload-file="uploadFile"
                @remove-file="removeFile"
              />
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            Pricing
          </template>
          <template #content>
            <div class="grid">
              <div class="lg:col-4 md:col-6 col-12 flex flex-column gap-2 mb-3">
                <label for="price">Price</label>
                <InputNumber
                  id="price"
                  v-model="price"
                  mode="currency"
                  currency="BOB"
                />
              </div>
            </div>
            <div class="grid">
              <div class="flex flex-column lg:col-4 md:col-6 col-12 gap-2 mb-3">
                <label for="cost">Cost Per Item</label>
                <InputNumber
                  id="cost"
                  v-model="cost"
                  input-class="w-full"
                  mode="currency"
                  currency="BOB"
                />
              </div>
              <div class="flex flex-column lg:col-4 md:col-6 col-12 gap-2 mb-3">
                <label for="profit">Profit</label>
                <InputText
                  id="profit"
                  :value="profit"
                  disabled
                />
              </div>
              <div class="flex flex-column lg:col-4 md:col-6 col-12 gap-2 mb-3">
                <label for="margin">Margin</label>
                <InputText
                  id="margin"
                  :value="margin"
                  disabled
                />
              </div>
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            <div class="flex justify-content-between flex-wrap">
              <div>
                Options
              </div>
              <div class="flex align-items-center">
                <label
                  for="hasVariants"
                  class="mr-3"
                  style="font-weight: lighter; font-size: 14px;"
                >
                  This product has variants?
                </label>
                <InputSwitch v-model="hasVariants" />
              </div>
            </div>
          </template>
          <template #content>
            <OptionsEditor
              v-show="hasVariants"
              v-model="options"
              @option-deleted="removeItemVariantByOption"
            />
          </template>
        </Card>
        <Card
          v-show="options.length > 0"
          class="mb-4"
        >
          <template #title>
            <div class="flex justify-content-between flex-wrap">
              <div>
                Variants
              </div>
              <div>
                <PButton
                  label="Add Variant"
                  class="mr-2"
                  @click="addVariant"
                >
                  Add Variant
                </PButton>
                <PButton
                  outlined
                  label="Generate Variants"
                  @click="generateVariants()"
                />
              </div>
            </div>
          </template>
          <template #content>
            <DataTable
              v-show="variants.length > 0"
              :value="variants"
            >
              <Column
                field="media"
                header="Media"
              >
                <template #body="slotProps">
                  <img
                    v-if="slotProps.data.media.length > 0"
                    :src="slotProps.data.media[0].url"
                    alt="product"
                    style="width: 50px; height: 50px;"
                  >
                  <div v-else>
                    No image
                  </div>
                </template>
              </Column>
              <Column
                style="font-weight: 500;"
                field="name"
                header="Variant"
              />
              <Column
                field="price"
                header="Price"
              >
                <template #body="slotProps">
                  <InputNumber
                    v-model="slotProps.data.price"
                    mode="currency"
                    currency="BOB"
                  />
                </template>
              </Column>
              <Column
                field="identifier"
                header="Identifier"
              >
                <template #body="slotProps">
                  <InputText
                    v-model="slotProps.data.identifier"
                  />
                </template>
              </Column>
              <Column
                field="actions"
                header="Actions"
              >
                <template #body="slotProps">
                  <PButton
                    icon="fa fa-trash"
                    severity="danger"
                    outlined
                    @click="removeVariant(slotProps.data.hash)"
                  />
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>
      <div class="md:col-4 col-12">
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-column gap-2 mb-3">
              <label for="status">Status</label>
              <Dropdown
                v-model="status"
                :options="[
                  { name: 'Active', value: 'active' },
                  { name: 'Inactive', value: 'inactive' },
                  { name: 'Archived', value: 'archived' }
                ]"
                option-label="name"
                option-value="value"
              />
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            Product Organization
          </template>
          <template #content>
            <div class="flex flex-column gap-2 mb-3">
              <label for="category">Category</label>
              <MultiSelect
                id="category"
                v-model="category"
                display="chip"
                filter
                :options="categories"
                option-label="name"
                option-value="id"
              />
            </div>
            <div class="flex flex-column gap-2 mb-3">
              <label for="brand">Brand</label>
              <Dropdown
                id="brand"
                v-model="brand"
                filter
                :options="brands"
                option-label="name"
                option-value="id"
              />
            </div>
            <div class="flex flex-column gap-2 mb-3">
              <label for="measure_unit">Measure Unit</label>
              <Dropdown
                id="measure_unit"
                v-model="measureUnit"
                :options="measureUnits"
                option-label="name"
                option-value="id"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import Card from "primevue/card";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import { Inertia } from "@inertiajs/inertia";
import Textarea from "primevue/textarea";
import Dropdown from "primevue/dropdown";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import MultiSelect from "primevue/multiselect";
import Toast from "primevue/toast";
import InputNumber from "primevue/inputnumber";
import InputSwitch from "primevue/inputswitch";
import AppLayout from "../../layouts/admin.vue";
import MediaManager from "../../UI/MediaManager.vue";
import OptionsEditor from "../../UI/OptionsEditor.vue";

export default {
  components: {
    AppLayout,
    PButton,
    Card,
    InputText,
    InputNumber,
    Textarea,
    Dropdown,
    MediaManager,
    MultiSelect,
    Toast,
    OptionsEditor,
    DataTable,
    Column,
    InputSwitch,
  },
  props: {
    measureUnits: {
      type: Array,
      default: () => [],
    },
    brands: {
      type: Array,
      default: () => [],
    },
    categories: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      name: "",
      description: "",
      price: 0,
      cost: 0,
      status: "active",
      brand: "",
      measureUnit: "",
      files: [],
      category: [],
      margin: "--",
      profit: "--",
      options: [],
      variants: [],
      hasVariants: false,
    };
  },
  watch: {
    price() {
      this.calculateProfit();
    },
    cost() {
      this.calculateProfit();
    },
  },
  methods: {
    removeVariant(hash) {
      this.variants = this.variants.filter((variant) => variant.hash !== hash);
    },
    updateOptions(options) {
      this.options = options;
    },
    calculateProfit() {
      if (this.price !== 0 || this.cost !== 0) {
        this.profit = `BOB ${(this.price - this.cost).toFixed(2)}`;
        this.margin = `${(((this.price - this.cost) / this.price) * 100).toFixed(2)} %`;
      } else {
        this.profit = "--";
        this.margin = "--";
      }
    },
    uploadFile(formData) {
      axios
        .post("products/media", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then((response) => {
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "File uploaded",
            life: 3000,
          });
          this.files.push(
            {
              id: response.data.data.id,
              url: response.data.data.url,
            },
          );
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error,
            life: 3000,
          });
        });
    },
    removeFile(id) {
      axios
        .delete(route("api.products.media.destroy-draft", id))
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "File removed",
            life: 3000,
          });
          this.files = this.files.filter((f) => f.id !== id);
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error,
            life: 3000,
          });
        });
    },
    generateVariants(allowedOptions = false) {
      const formattedOptions = [];
      // merge all the option values
      const options = this.options.filter((option) => option.saved === true);

      if (options.length === 0) {
        this.variants = [];
      }

      const values = this.options.map((option) => option.values);

      if (options.length === 1) {
        values[0].forEach((value) => {
          formattedOptions.push({ name: `${value}`, options: [value] });
        });
      } else if (options.length === 2) {
        values[0].forEach((value) => {
          values[1].forEach((v) => {
            formattedOptions.push({ name: `${value} / ${v}`, options: [value, v] });
          });
        });
      } else if (options.length === 3) {
        values[0].forEach((value) => {
          values[1].forEach((v) => {
            values[2].forEach((val) => {
              formattedOptions.push({ name: `${value} / ${v} / ${val}`, options: [value, v, val] });
            });
          });
        });
      }

      const variants = [];

      formattedOptions.forEach((variant) => {
        let formattedVariant;
        // hash the variant name, get all only the letters and numbers then sort them
        if (allowedOptions === false) {
          const hash = variant.name
            .replace(/[^a-zA-Z0-9]/g, "")
            .toLowerCase()
            .split("")
            .sort()
            .join("");

          formattedVariant = {
            hash,
            name: variant.name,
            options: variant.options,
            price: 0,
            identifier: null,
            media: [],
          };

          variants.push(formattedVariant);
        } else {
          allowedOptions.forEach((option) => {
            if (variant.options.includes(option)) {
              const hash = variant.name
                .replace(/[^a-zA-Z0-9]/g, "")
                .toLowerCase()
                .split("")
                .sort()
                .join("");

              formattedVariant = {
                hash,
                name: variant.name,
                options: variant.options,
                price: 0,
                identifier: null,
                media: [],
              };
              variants.push(formattedVariant);
            }
          });
        }
      });

      this.variants = variants;
    },
    removeItemVariantByOption(option) {
      let allowedOptions = [];

      this.variants.forEach((variant) => {
        option.values.forEach((value) => {
          // remove value from the variant options
          const index = variant.options.indexOf(value);

          if (index > -1) {
            variant.options.splice(index, 1);
            // merge the allowed options
            allowedOptions = allowedOptions.concat(variant.options);
          }
        });
      });

      // remove duplicates
      allowedOptions = [...new Set(allowedOptions)];

      this.$nextTick(() => {
        this.generateVariants(allowedOptions);
      });
    },
  },
};
</script>
