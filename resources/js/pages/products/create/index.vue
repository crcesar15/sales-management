<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="$inertia.visit(route('products'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ $t('Add Product') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <PButton
          icon="fa fa-save"
          :label="$t('Save')"
          class="uppercase"
          raised
          @click="submit()"
        />
      </div>
    </div>
    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="name">{{ $t('Name') }}</label>
              <InputText
                id="name"
                v-model="name"
                autocomplete="off"
                :class="{'p-invalid': v$.name.$invalid && v$.name.$dirty}"
                @blur="v$.name.$touch"
              />
              <small
                v-if="v$.name.$invalid && v$.name.$dirty"
                class="text-red-400 dark:text-red-300"
              >
                {{ v$.name.$errors[0].$message }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="description">{{ $t('Description') }}</label>
              <Textarea
                id="description"
                v-model="description"
              />
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            {{ $t('Images') }}
          </template>
          <template #content>
            <div class="flex flex-col">
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
            {{ $t('Details') }}
          </template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="flex flex-col lg:col-span-6 md:col-span-6 col-span-12 gap-2 mb-3">
                <label for="price">{{ $t('Price') }}</label>
                <InputNumber
                  id="price"
                  v-model="price"
                  mode="currency"
                  currency="BOB"
                  :class="{'p-invalid': v$.price.$invalid && v$.price.$dirty}"
                  @blur="v$.price.$touch"
                />
                <small
                  v-if="v$.price.$invalid && v$.price.$dirty"
                  class="text-red-400 dark:text-red-300"
                >
                  {{ v$.price.$errors[0].$message }}
                </small>
              </div>
              <div class="flex flex-col lg:col-span-6 md:col-span-6 col-span-12 gap-2 mb-3">
                <label for="profit">{{ $t('Bar Code or Identifier') }}</label>
                <InputText
                  id="profit"
                  v-model="identifier"
                  autocomplete="off"
                  :class="{'p-invalid': v$.identifier.$invalid && v$.identifier.$dirty}"
                  @blur="v$.identifier.$touch"
                />
                <small
                  v-if="v$.identifier.$invalid && v$.identifier.$dirty"
                  class="text-red-400 dark:text-red-300"
                >
                  {{ v$.identifier.$errors[0].$message }}
                </small>
              </div>
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            <div class="flex justify-between flex-wrap">
              <div>
                {{ $t('Options') }}
              </div>
              <div class="flex items-center">
                <label
                  for="hasVariants"
                  class="mr-3 text-primary"
                  style="font-size: 14px;"
                >
                  {{ $t('This product has variants?') }}
                </label>
                <InputSwitch
                  v-model="hasVariants"
                  @change="price = null; identifier = null;"
                />
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
            <div class="flex justify-between flex-wrap">
              <div>
                {{ $t('Variants') }}
              </div>
              <div>
                <PButton
                  :label="$t('Add Variant')"
                  class="mr-2 uppercase"
                  raised
                  @click="addVariant()"
                />
                <PButton
                  outlined
                  raised
                  class="uppercase"
                  :label="$t('Generate Variants')"
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
                :header="$t('Images')"
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
                      class="
                        rounded-lg
                        border-2
                        h-20
                        w-20
                        border-slate-300
                        dark:border-slate-700
                      "
                    >
                    <div
                      v-else
                      class="
                        border-dashed
                        rounded-lg
                        border-4
                        h-20
                        w-20
                        flex
                        justify-center
                        items-center
                        border-slate-300
                        dark:border-slate-700"
                    >
                      <i class="fa fa-file-circle-plus" />
                    </div>
                  </div>
                </template>
              </Column>
              <Column
                style="font-weight: 500;"
                field="name"
                :header="$t('Variant')"
              />
              <Column
                field="identifier"
                :header="$t('Bar Code or Identifier')"
              >
                <template #body="slotProps">
                  <div class="flex flex-col">
                    <InputText
                      v-model="slotProps.data.identifier"
                      autocomplete="off"
                      :class="{'p-invalid': v$.variants.$each.$response.$errors[slotProps.index].identifier.length > 0}"
                      @blur="v$.variants.$each.$response.$errors[slotProps.index].identifier.$touch"
                    />
                    <small
                      v-if="v$.variants.$each.$response.$errors[slotProps.index].identifier.length > 0"
                      class="text-red-400 dark:text-red-300"
                    >
                      {{ v$.variants.$each.$response.$errors[slotProps.index].identifier[0].$message }}
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
                      mode="currency"
                      currency="BOB"
                      :class="{'p-invalid': v$.variants.$each.$response.$errors[slotProps.index].price.length > 0}"
                      @blur="v$.variants.$each.$response.$errors[slotProps.index].price.$touch"
                    />
                    <small
                      v-if="v$.variants.$each.$response.$errors[slotProps.index].price.length > 0"
                      class="text-red-400 dark:text-red-300"
                    >
                      {{ v$.variants.$each.$response.$errors[slotProps.index].price[0].$message }}
                    </small>
                  </div>
                </template>
              </Column>
              <Column
                field="actions"
                :header="$t('Actions')"
              >
                <template #body="slotProps">
                  <PButton
                    icon="fa fa-trash"
                    severity="primary"
                    rounded
                    raised
                    @click="removeVariant(slotProps.data.hash)"
                  />
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>
      <div class="md:col-span-4 col-span-12">
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="status">{{ $t('Status') }}</label>
              <Select
                v-model="status"
                :options="[
                  { name: $t('Active'), value: 'active' },
                  { name: $t('Inactive'), value: 'inactive' },
                  { name: $t('Archived'), value: 'archived' }
                ]"
                option-label="name"
                option-value="value"
              />
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            {{ $t('Product Organization') }}
          </template>
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="category">{{ $t('Category') }}</label>
              <MultiSelect
                id="category"
                v-model="category"
                display="chip"
                filter
                :options="categories"
                option-label="name"
                option-value="id"
                :class="{'p-invalid': v$.category.$invalid && v$.category.$dirty}"
                @blur="v$.category.$touch"
              />
              <small
                v-if="v$.category.$invalid && v$.category.$dirty"
                class="text-red-400 dark:text-red-300"
              >
                {{ v$.category.$errors[0].$message }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="brand">{{ $t('Brand') }}</label>
              <Select
                id="brand"
                v-model="brand"
                filter
                :options="brands"
                option-label="name"
                option-value="id"
                :class="{'p-invalid': v$.brand.$invalid && v$.brand.$dirty}"
                @blur="v$.brand.$touch"
              />
              <small
                v-if="v$.brand.$invalid && v$.brand.$dirty"
                class="text-red-400 dark:text-red-300"
              >
                {{ v$.brand.$errors[0].$message }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="measure_unit">{{ $t('Measurement Unit') }}</label>
              <Select
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
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        :header="$t('Add Variant')"
      >
        <div
          v-for="(option, index) in options"
          :key="index"
          class="flex flex-col gap-2 mb-3"
        >
          <label>{{ option.name }}</label>
          <Select
            v-model="selectedOptions[index]"
            :options="option.values"
            class="w-full md:w-[20rem]"
          />
        </div>
        <template #footer>
          <PButton
            :label="$t('Cancel')"
            outlined
            severity="primary"
            @click="toggleVariantEditor"
          />
          <PButton
            :label="$t('Save')"
            severity="primary"
            @click="saveVariant"
          />
        </template>
      </Dialog>
      <Dialog
        v-model:visible="showVariantImages"
        modal
        :header="$t('Add Images to Variant')"
        :style="{ width: '50vw' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
      >
        <div class="grid grid-cols-12 gap-4">
          <div
            v-for="file in files"
            :key="file.id"
            class="col-span-3"
          >
            <div class="flex flex-col gap-2">
              <img
                :src="file.url"
                alt="product"
                class="
                  rounded-lg
                  border-2
                  w-full
                  border-slate-300
                  dark:border-slate-700
                "
              >
              <div
                class="flex justify-center"
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
            :label="$t('Cancel')"
            outlined
            severity="primary"
            @click="toggleVariantImages"
          />
          <PButton
            :label="$t('Save')"
            severity="primary"
            @click="saveSelectedVariantImages"
          />
        </template>
      </Dialog>
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
import MultiSelect from "primevue/multiselect";
import InputNumber from "primevue/inputnumber";
import InputSwitch from "primevue/inputswitch";
import Checkbox from "primevue/checkbox";
import Dialog from "primevue/dialog";
import { useVuelidate } from "@vuelidate/core";
import {
  helpers, required, minLength, minValue, requiredIf,
  createI18nMessage,
} from "@vuelidate/validators";
import AppLayout from "../../../layouts/admin.vue";
import MediaManager from "../../../UI/MediaManager.vue";
import OptionsEditor from "../../../UI/OptionsEditor.vue";
import i18n from "../../../app";

export default {
  components: {
    PButton,
    Card,
    InputText,
    InputNumber,
    Textarea,
    Select,
    MediaManager,
    MultiSelect,
    OptionsEditor,
    DataTable,
    Column,
    InputSwitch,
    Dialog,
    Checkbox,
  },
  layout: AppLayout,
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
  setup() {
    return {
      v$: useVuelidate(),
    };
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
  validations() {
    const { t } = i18n.global;

    const withI18nMessage = createI18nMessage({
      t,
      messagesPath: "validations",
    });

    return {
      name: {
        required: withI18nMessage(required),
        minLength: withI18nMessage(minLength(10)),
      },
      // category: { required: withI18nMessage(required) },
      // brand: { required: withI18nMessage(required) },
      category: { },
      brand: { },
      price: {
        required: withI18nMessage(requiredIf(() => !this.hasVariants)),
        minValue: withI18nMessage(minValue(0.5)),
      },
      identifier: {
        minLength: withI18nMessage(minLength(5)),
      },
      variants: {
        required: withI18nMessage(requiredIf(() => this.hasVariants)),
        $each: helpers.forEach({
          identifier: {
            required: withI18nMessage(required, { messagePath: () => ("validations.required") }),
            minLength: withI18nMessage(minLength(5), { messagePath: () => ("validations.minLength") }),
          },
          price: {
            required: withI18nMessage(required, { messagePath: () => ("validations.required") }),
            minValue: withI18nMessage(minValue(0.5), { messagePath: () => ("validations.minValue") }),
          },
        }),
      },
    };
  },
  methods: {
    validateRow(rowIndex) {
      this.$v.variants[rowIndex].$touch();
    },
    submit() {
      this.v$.$touch();

      if (!this.v$.$invalid) {
        const body = {
          name: this.name,
          description: this.description,
          status: this.status,
          brand_id: this.brand,
          measurement_unit_id: this.measureUnit,
          categories: this.category,
          options: this.options,
          media: this.files,
        };

        if (this.hasVariants) {
          body.variants = this.variants.map((variant) => ({
            name: variant.name,
            identifier: variant.identifier,
            price: variant.price,
            status: this.status,
            media: variant.media.map((file) => ({ id: file.id })),
          }));
        } else {
          body.variants = [{
            name: this.name,
            identifier: this.identifier,
            price: this.price,
            status: this.status,
            media: this.files.map((file) => ({ id: file.id })),
          }];
        }

        axios
          .post(route("api.products.store"), body)
          .then(() => {
            this.$toast.add({
              severity: "success",
              summary: i18n.global.t("Success"),
              detail: i18n.global.t("Product created successfully"),
              life: 3000,
            });
            Inertia.visit(route("products"));
          })
          .catch((error) => {
            this.$toast.add({
              severity: "error",
              summary: i18n.global.t("Error"),
              detail: error,
              life: 3000,
            });
          });
      } else {
        let message = "";

        if (typeof this.v$.$errors[0].$message === "object") {
          message = `${i18n.global.t(this.capitalize(this.v$.$errors[0].$property))}: ${this.v$.$errors[0].$message[0]}`;
        } else {
          message = `${i18n.global.t(this.capitalize(this.v$.$errors[0].$property))}: ${this.v$.$errors[0].$message}`;
        }

        this.$toast.add({
          severity: "error",
          summary: i18n.global.t("Please review the errors in the form"),
          detail: message,
          life: 3000,
        });
      }
    },
    removeVariant(hash) {
      this.variants = this.variants.filter((variant) => variant.hash !== hash);
    },
    updateOptions(options) {
      this.options = options;
    },
    uploadFile(formData) {
      const data = formData;

      axios
        .post(route("api.media.draft.store"), data, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then((response) => {
          this.$toast.add({
            severity: "success",
            summary: i18n.global.t("Success"),
            detail: i18n.global.t("File uploaded"),
            life: 3000,
          });
          this.files.push(
            {
              id: response.data.id,
              url: response.data.url,
            },
          );
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: i18n.global.t("Error"),
            detail: error,
            life: 3000,
          });
        });
    },
    removeFile(id) {
      axios
        .delete(route("api.media.draft.destroy", id))
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: i18n.global.t("Success"),
            detail: i18n.global.t("File removed"),
            life: 3000,
          });
          this.files = this.files.filter((f) => f.id !== id);
          this.variants.forEach((variant, index) => {
            this.variants[index].media = variant.media.filter((media) => media.id !== id);
          });
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: i18n.global.t("Error"),
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
    capitalize(words) {
      // separate words by capitalized letter
      let formatted = words.replace(/([A-Z])/g, " $1")
        // capitalize the first letter
        .replace(/^./, (str) => str.toUpperCase());
      // capitalize the first letter of each word
      formatted = formatted.replace(/\b\w/g, (l) => l.toUpperCase());

      return formatted;
    },
  },
};
</script>
