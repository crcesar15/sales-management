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
        <Card
          v-show="hasVariants === false"
          class="mb-4"
        >
          <template #title>
            Details
          </template>
          <template #content>
            <div class="grid">
              <div class="flex flex-column lg:col-6 md:col-6 col-12 gap-2 mb-3">
                <label for="price">Price</label>
                <InputNumber
                  id="price"
                  v-model="price"
                  mode="currency"
                  currency="BOB"
                />
              </div>
              <div class="flex flex-column lg:col-6 md:col-6 col-12 gap-2 mb-3">
                <label for="profit">Bar Code or Identifier</label>
                <InputText
                  id="profit"
                  :value="identifier"
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
          v-show="options.length > 0 && hasVariants === true"
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
                  @click="addVariant()"
                />
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
                  <div
                    class="cursor-pointer"
                    @click="addImagesToVariant(slotProps.index, slotProps.data.media)"
                  >
                    <img
                      v-if="slotProps.data.media.length > 0"
                      :src="slotProps.data.media[0].url"
                      alt="product"
                      class="border-round border-1 h-5rem w-5rem"
                    >
                    <div
                      v-else
                      class="border-dashed h-5rem w-5rem flex justify-content-center align-items-center"
                    >
                      <i class="fa fa-file-circle-plus" />
                    </div>
                  </div>
                </template>
              </Column>
              <Column
                style="font-weight: 500;"
                field="name"
                header="Variant"
              />
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
                field="actions"
                header="Actions"
              >
                <template #body="slotProps">
                  <PButton
                    icon="fa fa-trash"
                    severity="primary"
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
      <Dialog
        v-model:visible="showVariantEditor"
        modal
        header="Add Variant"
      >
        <div
          v-for="(option, index) in options"
          :key="index"
          class="flex flex-column gap-2 mb-3"
        >
          <label>{{ option.name }}</label>
          <Dropdown
            v-model="selectedOptions[index]"
            :options="option.values"
            class="w-full md:w-14rem"
          />
        </div>
        <template #footer>
          <PButton
            label="Cancel"
            outlined
            severity="primary"
            @click="toggleVariantEditor"
          />
          <PButton
            label="Save"
            severity="primary"
            @click="saveVariant"
          />
        </template>
      </Dialog>
      <Dialog
        v-model:visible="showVariantImages"
        modal
        header="Add Images to Variant"
        :style="{ width: '50vw' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
      >
        <div class="grid">
          <div
            v-for="file in files"
            :key="file.id"
            class="col-3"
          >
            <div class="flex flex-column gap-2">
              <img
                :src="file.url"
                alt="product"
                style="border: solid 1px var(--surface-400)"
                class="border-round"
              >
              <div
                class="flex justify-content-center"
              >
                <Checkbox
                  v-model="selectedVariantImages"
                  :value="file.id"
                />
              </div>
            </div>
          </div>
        </div>
        <template #footer>
          <PButton
            label="Cancel"
            outlined
            severity="primary"
            @click="toggleVariantImages"
          />
          <PButton
            label="Save"
            severity="primary"
            @click="saveSelectedVariantImages"
          />
        </template>
      </Dialog>
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
import Checkbox from "primevue/checkbox";
import Dialog from "primevue/dialog";
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
    Dialog,
    Checkbox,
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
      identifier: "",
      status: "active",
      brand: "",
      measureUnit: "",
      files: [],
      category: [],
      options: [],
      variants: [],
      hasVariants: false,
      showVariantImages: false,
      selectedVariantImages: [],
      selectedVariantId: null,
      showVariantEditor: false,
      selectedOptions: [],
    };
  },
  methods: {
    removeVariant(hash) {
      this.variants = this.variants.filter((variant) => variant.hash !== hash);
    },
    updateOptions(options) {
      this.options = options;
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
    addImagesToVariant(index, media) {
      this.selectedVariantImages = media.map((file) => file.id);
      this.selectedVariantId = index;
      this.toggleVariantImages();
    },
    toggleVariantImages() {
      this.showVariantImages = !this.showVariantImages;
    },
    saveSelectedVariantImages() {
      const variant = this.variants[this.selectedVariantId];
      const media = this.selectedVariantImages.map((id) => this.files.find((file) => file.id === id));

      variant.media = media;
      this.variants[this.selectedVariantId] = variant;
      this.selectedVariantId = null;
      this.selectedVariantImages = [];
      this.toggleVariantImages();
    },
    toggleVariantEditor() {
      this.showVariantEditor = !this.showVariantEditor;
    },
    addVariant() {
      this.toggleVariantEditor();
      this.selectedOptions = [];
    },
    saveVariant() {
      console.log(this.selectedOptions);

      switch (this.selectedOptions.length) {
        case 1:
          this.variants.push({
            hash: this.selectedOptions[0]
              .replace(/[^a-zA-Z0-9]/g, "")
              .toLowerCase()
              .split("")
              .sort()
              .join(""),
            name: this.selectedOptions[0],
            options: [this.selectedOptions[0]],
            price: 0,
            identifier: null,
            media: [],
          });
          break;
        case 2:
          this.variants.push({
            hash: this.selectedOptions.join(" / ")
              .replace(/[^a-zA-Z0-9]/g, "")
              .toLowerCase()
              .split("")
              .sort()
              .join(""),
            name: this.selectedOptions.join(" / "),
            options: this.selectedOptions,
            price: 0,
            identifier: null,
            media: [],
          });
          break;
        case 3:
          this.variants.push({
            hash: this.selectedOptions.join(" / ")
              .replace(/[^a-zA-Z0-9]/g, "")
              .toLowerCase()
              .split("")
              .sort()
              .join(""),
            name: this.selectedOptions.join(" / "),
            options: this.selectedOptions,
            price: 0,
            identifier: null,
            media: [],
          });
          break;
        default:
          break;
      }

      this.selectedOptions = [];
      this.toggleVariantEditor();
    },
  },
};
</script>
