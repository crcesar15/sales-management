<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <Button
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="router.visit(route('products'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ $t('Add Product') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <Button
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
                  @change="clearProductSettings(hasVariants)"
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
                <Button
                  :label="$t('Add Variant')"
                  class="mr-2 uppercase"
                  raised
                  @click="addVariant()"
                />
                <Button
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
                  <Button
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
                :options="props.categories"
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
                :options="props.brands"
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
                :options="props.measureUnits"
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
          <Button
            :label="$t('Cancel')"
            outlined
            severity="primary"
            @click="toggleVariantEditor"
          />
          <Button
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
          <Button
            :label="$t('Cancel')"
            outlined
            severity="primary"
            @click="toggleVariantImages"
          />
          <Button
            :label="$t('Save')"
            severity="primary"
            @click="saveSelectedVariantImages"
          />
        </template>
      </Dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import{
  Card,
  Button,
  InputText,
  Textarea,
  Select,
  DataTable,
  Column,
  MultiSelect,
  InputNumber,
  InputSwitch,
  Checkbox,
  Dialog,
  useToast,
} from "primevue";

import {
  helpers,
  required,
  minLength,
  minValue,
  requiredIf,
  createI18nMessage,
} from "@vuelidate/validators";

import { useVuelidate } from "@vuelidate/core";
import AppLayout from "@layouts/admin.vue";
import MediaManager from "@components/MediaManager.vue";
import OptionsEditor from "@components/OptionsEditor.vue";
import { useI18n } from "vue-i18n";
import { computed, ref } from "vue";
import { MeasurementUnit } from "@app-types/measurement-unit-types";
import { Brand } from "@app-types/brand-types";
import { Category } from "@app-types/category-types";
import { ProductPayload } from "@app-types/product-types";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useProductClient } from "@composables/useProductClient";
import { useMediaClient } from "@composables/useMediaClient";

// Set composables
const toast = useToast();
const { t } = useI18n();

// Set translations for validations
const withI18nMessage = createI18nMessage({ t });

// Layout
defineOptions({
  layout: AppLayout,
});

// Define props
const props = defineProps<{
  measureUnits: MeasurementUnit[],
  brands: Brand[],
  categories: Category[],
}>();

// Form fields
const name = ref("");
const description = ref("");
const price = ref(0);
const identifier = ref("");
const status = ref("active");
const brand = ref("");
const measureUnit = ref("");
const files = ref<Array<{ id: number; url: string }>>([]);
const category = ref([]);
const options = ref<Array<{ name: string; values: string[]; saved: boolean }>>([]);
const variants = ref<Array<{hash: string, name: string, options: string[], price: number, identifier: string | null, media: any[]}>>([]);
const hasVariants = ref(false);
const showVariantImages = ref(false);
const selectedVariantImages = ref<Array<number>>([]);
const selectedVariantId = ref<number | null>(null);
const showVariantEditor = ref(false);
const selectedOptions = ref<Array<string>>([]);

// Rules
const rules = computed(() => {
  let formattedRules = {
    name: {
      required: withI18nMessage(required),
      minLength: withI18nMessage(minLength(10)),
    },
    category: { required: withI18nMessage(required) },
    brand: { required: withI18nMessage(required) },
    identifier: {
      minLength: withI18nMessage(minLength(5)),
    },
    price: {},
    variants: {
      required: withI18nMessage(requiredIf(() => hasVariants.value)),
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
    }
  };

  if (!hasVariants.value) {
    formattedRules = {
      ...formattedRules,
      price: {
        required: withI18nMessage(required),
        minValue: withI18nMessage(minValue(0.5)),
      },
    };
  }

  return formattedRules;
});

// Validator
const v$ = useVuelidate(
  rules,
  {
    name,
    description,
    price,
    identifier,
    status,
    brand,
    measureUnit,
    category,
    options,
    variants,
    showVariantImages,
    selectedVariantImages,
    selectedVariantId,
    showVariantEditor,
    selectedOptions,
  }
);

const validateRow = (rowIndex:number) => {
  v$.value.variants[rowIndex].$touch();
}

// Upload temp images from media manager
const { uploadDraftFile, loading } = useMediaClient();

const uploadFile = async (formData: FormData) => {
  toast.add({ severity: "info", summary: t("Uploading..."), life: 3000 });
  const data = formData;

  try {
    const response = await uploadDraftFile(data);

    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("File uploaded"),
      life: 3000,
    });
    files.value.push(
      {
        id: response.data.id,
        url: response.data.url,
      },
    );
  } catch (error:any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error?.response?.data?.message || error),
      life: 3000,
    });
  }
}

const removeFile = async (id:number) => {
  const {destroyDraftFile} = useMediaClient();

  try {
    await destroyDraftFile(id);

    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("File removed"),
      life: 3000,
    });

    files.value = files.value.filter((f) => f.id !== id);

    // variants.forEach((variant, index) => {
    //   variants[index].media = variant.media.filter((media) => media.id !== id);
    // });
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error?.response?.data?.message || error),
      life: 3000,
    });
  }
}

// Options and variants methods
const clearProductSettings = (hasVariants: boolean) => {
  if (hasVariants) {
    price.value = 0;
    identifier.value = '';
  } else {
    options.value = [];
    variants.value = [];
  }
}

const generateVariants = (allowedOptions: string[] | false = false) => {
  console.log('test');
  const formattedOptions:Array<{ name: string; options: string[] }> = [];

  // merge all the option values
  const savedOptions:Array<{ name: string; values: string[]; saved: boolean }> =
    options.value.filter((option) => option.saved === true);

  if (savedOptions.length === 0) {
    variants.value = [];
  }

  const values = savedOptions.map((option) => option.values);

  if (savedOptions.length === 1) {
    values[0].forEach((value) => {
      formattedOptions.push({ name: `${value}`, options: [value] });
    });
  } else if (savedOptions.length === 2) {
    values[0].forEach((value) => {
      values[1].forEach((v) => {
        formattedOptions.push({ name: `${value} / ${v}`, options: [value, v] });
      });
    });
  } else if (savedOptions.length === 3) {
    values[0].forEach((value) => {
      values[1].forEach((v) => {
        values[2].forEach((val) => {
          formattedOptions.push({ name: `${value} / ${v} / ${val}`, options: [value, v, val] });
        });
      });
    });
  }

  const formattedVariants:Array<{hash: string, name: string, options: string[], price: number, identifier: string | null, media: any[]}> = [];

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

      formattedVariants.push(formattedVariant);
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
          formattedVariants.push(formattedVariant);
        }
      });
    }
  });

  variants.value = formattedVariants;
}

const removeItemVariantByOption = (option: { name: string; values: string[]; saved: boolean }) => {
  let allowedOptions: string[] = [];

  variants.value.forEach((variant) => {
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

  generateVariants(allowedOptions);
}

const addVariant = () => {
  toggleVariantEditor();

  selectedOptions.value = [];
}

const toggleVariantEditor = () => {
  showVariantEditor.value = !showVariantEditor.value;
}

const saveVariant = () => {
  switch (selectedOptions.value.length) {
    case 1:
      variants.value.push({
        hash: selectedOptions.value[0]
          .replace(/[^a-zA-Z0-9]/g, "")
          .toLowerCase()
          .split("")
          .sort()
          .join(""),
        name: selectedOptions.value[0],
        options: [selectedOptions.value[0]],
        price: 0,
        identifier: null,
        media: [],
      });
      break;
    case 2:
      variants.value.push({
        hash: selectedOptions.value.join(" / ")
          .replace(/[^a-zA-Z0-9]/g, "")
          .toLowerCase()
          .split("")
          .sort()
          .join(""),
        name: selectedOptions.value.join(" / "),
        options: selectedOptions.value,
        price: 0,
        identifier: null,
        media: [],
      });
      break;
    case 3:
      variants.value.push({
        hash: selectedOptions.value.join(" / ")
          .replace(/[^a-zA-Z0-9]/g, "")
          .toLowerCase()
          .split("")
          .sort()
          .join(""),
        name: selectedOptions.value.join(" / "),
        options: selectedOptions.value,
        price: 0,
        identifier: null,
        media: [],
      });
      break;
    default:
      break;
  }

  selectedOptions.value = [];
  toggleVariantEditor();
}

const removeVariant = (hash: string) => {
  variants.value = variants.value.filter((variant) => variant.hash !== hash);
}

const toggleVariantImages = () => {
  showVariantImages.value = !showVariantImages.value;
}

const addImagesToVariant = (index: number, media: {id: number}[]) => {
  selectedVariantImages.value = media.map((file) => file.id);
  selectedVariantId.value = index;
  toggleVariantImages();
}

const saveSelectedVariantImages = () => {
  if (selectedVariantId.value === null) {
    return;
  }

  const formattedVariant = variants.value[selectedVariantId.value];
  const formattedMedia = selectedVariantImages.value.map((id) => files.value.find((file) => file.id === id));

  formattedVariant.media = formattedMedia;
  variants.value[selectedVariantId.value] = formattedVariant;
  selectedVariantId.value = null;
  selectedVariantImages.value = [];
  toggleVariantImages();
}

const submit = async () => {
  v$.value.$touch();

  if (!v$.value.$invalid) {
    const body: ProductPayload = {
      name: name.value,
      description: description.value,
      status: status.value,
      brand_id: parseInt(brand.value),
      measurement_unit_id: parseInt(measureUnit.value),
      categories: category.value,
      options: options.value,
      media: files.value,
    };

    if (hasVariants) {
      body.variants = variants.value.map((variant) => ({
        name: variant.name,
        identifier: variant.identifier,
        price: variant.price,
        status: status.value,
        media: variant.media.map((file) => ({ id: file.id })),
      }));
    } else {
      body.variants = [{
        name: name.value,
        identifier: identifier.value,
        price: price.value,
        status: status.value,
        media: files.value.map((file) => ({ id: file.id })),
      }];
    }

    const {storeProductApi} = useProductClient();

    try {
      await storeProductApi(body);
      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("Product created successfully"),
        life: 3000,
      });
      router.visit(route("products"));
    } catch (error: any) {
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t(error?.response?.data?.message || error),
        life: 3000,
      });
    }
  } else {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t("Please review the errors in the form"),
      life: 3000,
    });
  }
}


</script>
