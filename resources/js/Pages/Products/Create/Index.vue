<script setup lang="ts">
import {
  Button,
  Card,
  ConfirmDialog,
  InputText,
  InputNumber,
  MultiSelect,
  Select,
  SelectButton,
  Textarea,
  useConfirm,
  useToast,
} from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string, number, array } from "yup";
import { route } from "ziggy-js";
import { computed, ref, watch } from "vue";
import { configureYupLocale } from "@/validations/yupLocale";
import type { Brand } from "@app-types/brand-types";
import type { Category } from "@app-types/category-types";
import type { MeasurementUnit } from "@app-types/measurement-unit-types";
import type { CreateVariant } from "@app-types/product-types";
import AppLayout from "@layouts/admin.vue";
import ProductImages from "@pages/Products/Components/ProductImages.vue";
import OptionsEditor from "@pages/Products/Create/Components/OptionsEditor.vue";
import VariantsPanel from "@pages/Products/Create/Components/VariantsPanel.vue";

defineOptions({ layout: AppLayout });
const props = defineProps<{
  brands: Brand[];
  categories: Category[];
  measurementUnits: MeasurementUnit[];
}>();
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();
configureYupLocale(t);

// Product type selector
const productTypeOptions = [
  { label: t("Simple Product"), value: false },
  { label: t("Product with Variants"), value: true },
];

const hasVariants = ref(false);

// Options state (local for create)
const localOptions = ref<Array<{ name: string; values: string[]; saved: boolean }>>([]);
const optionsConfirmed = ref(false);

// Variants state
const variants = ref<CreateVariant[]>([]);

const confirmedOptions = computed(() => localOptions.value.map((o) => ({ name: o.name, values: o.values })));

const onOptionsUnlocked = () => {
  variants.value = [];
};

const onVariantsUpdate = (updated: CreateVariant[]) => {
  variants.value = updated;
};

// Confirm dialog when toggling from variants to simple
watch(hasVariants, (newVal, oldVal) => {
  if (oldVal === true && newVal === false && (optionsConfirmed.value || variants.value.length > 0)) {
    confirm.require({
      group: "headless",
      message: t("Switching to Simple Product will remove all configured variants."),
      header: t("Remove Variants?"),
      icon: "fa fa-triangle-exclamation",
      rejectProps: { label: t("Cancel"), severity: "secondary", outlined: true },
      acceptProps: { label: t("Switch"), severity: "danger" },
      accept: () => {
        optionsConfirmed.value = false;
        variants.value = [];
        localOptions.value = [];
      },
      reject: () => {
        hasVariants.value = true;
      },
    });
  }
});

// Schema
const schema = toTypedSchema(
  object({
    name: string().required().max(255),
    description: string().nullable().optional().max(350),
    status: string().required().oneOf(["active", "inactive", "archived"]),
    brand_id: number().nullable().optional(),
    measurement_unit_id: number().nullable().optional(),
    categories_ids: array().of(number().required()).required().min(1, t("At least one category is required")),
    price: number().nullable().optional().min(0),
    stock: number().nullable().optional().integer().min(0),
    barcode: string().nullable().optional().max(100),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors } = useForm({
  validationSchema: schema,
  initialValues: {
    name: "",
    description: "",
    status: "active",
    brand_id: null as number | null,
    measurement_unit_id: null as number | null,
    categories_ids: [] as number[],
    price: 0,
    stock: 0,
    barcode: "" as string | null,
  },
});

const [name, nameAttrs] = defineField("name");
const [description, descriptionAttrs] = defineField("description");
const [status, statusAttrs] = defineField("status");
const [brandId, brandIdAttrs] = defineField("brand_id");
const [measurementUnitId, measurementUnitIdAttrs] = defineField("measurement_unit_id");
const [categoriesIds, categoriesIdsAttrs] = defineField("categories_ids");
const [price, priceAttrs] = defineField("price");
const [stock, stockAttrs] = defineField("stock");
const [barcode, barcodeAttrs] = defineField("barcode");

// Description char counter
const descriptionCharCount = computed(() => (description.value ?? "").length);
const onDescriptionInput = () => {};

// Pending media tracking
const pendingMedia = ref<Array<{ id: number; thumb_url: string; full_url: string }>>([]);

// Submit
const onSubmit = handleSubmit((values) => {
  const payload: Record<string, unknown> = {
    ...values,
    pending_media_ids: pendingMedia.value.map((m) => m.id),
  };

  if (hasVariants.value) {
    if (!optionsConfirmed.value || variants.value.length === 0) {
      toast.add({
        severity: "warn",
        summary: t("Warning"),
        detail: t("Confirm options and add at least one variant."),
        life: 3000,
      });
      return;
    }

    payload.has_variants = true;
    payload.price = 0;
    payload.stock = 0;
    payload.barcode = null;
    payload.options = localOptions.value.map((o) => ({
      name: o.name,
      values: o.values,
    }));
    payload.variants = variants.value.map((v) => ({
      option_values: v.option_values,
      price: v.price,
      stock: v.stock,
      barcode: v.barcode,
      pending_media_ids: v.pending_media_ids ?? [],
    }));
  } else {
    payload.has_variants = false;
  }

  router.post(route("products.store"), payload, {
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("Product created successfully"),
        life: 3000,
      });
      router.visit(route("products"));
    },
    onError: (errs) => {
      setErrors(errs);
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t(Object.values(errs)[0] ?? "An error occurred"),
        life: 3000,
      });
    },
  });
});
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <Button icon="fa fa-arrow-left" text severity="secondary" class="hover:shadow-md mr-2" @click="router.visit(route('products'))" />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ t("Add Product") }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <Button icon="fa fa-save" :label="t('Save')" class="uppercase" raised :loading="isSubmitting" @click="onSubmit()" />
      </div>
    </div>

    <div class="grid grid-cols-12 gap-4">
      <!-- Left Column -->
      <div class="md:col-span-8 col-span-12">
        <!-- Name & Description -->
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="name">
                {{ t("Name") }}
                <span class="text-red-400">*</span>
              </label>
              <InputText id="name" v-model="name" v-bind="nameAttrs" autocomplete="off" :class="{ 'p-invalid': errors.name }" />
              <small v-if="errors.name" class="text-red-400 dark:text-red-300">
                {{ errors.name }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="description">{{ t("Description") }}</label>
              <Textarea
                id="description"
                v-model="description"
                v-bind="descriptionAttrs"
                :auto-resize="true"
                :class="{ 'p-invalid': errors.description }"
                @input="onDescriptionInput"
              />
              <div class="flex justify-end">
                <small class="text-gray-500">{{ descriptionCharCount }} / 350</small>
              </div>
              <small v-if="errors.description" class="text-red-400 dark:text-red-300">
                {{ errors.description }}
              </small>
            </div>
          </template>
        </Card>

        <!-- Images -->
        <Card class="mb-4">
          <template #title>
            {{ t("Images") }}
          </template>
          <template #content>
            <ProductImages v-model:pending-media="pendingMedia" :remove-media-ids="[]" @update:remove-media-ids="() => {}" />
          </template>
        </Card>

        <!-- Details Card (Simple Product mode) -->
        <Card v-if="!hasVariants" class="mb-4">
          <template #title>
            {{ t("Details") }}
          </template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="flex flex-col lg:col-span-4 md:col-span-6 col-span-12 gap-2 mb-3">
                <label for="price">
                  {{ t("Base Price") }}
                  <span class="text-red-400">*</span>
                </label>
                <InputNumber
                  id="price"
                  v-model="price"
                  v-bind="priceAttrs"
                  mode="currency"
                  currency="BOB"
                  :class="{ 'p-invalid': errors.price }"
                />
                <small v-if="errors.price" class="text-red-400 dark:text-red-300">
                  {{ errors.price }}
                </small>
              </div>
              <div class="flex flex-col lg:col-span-4 md:col-span-6 col-span-12 gap-2 mb-3">
                <label for="stock">
                  {{ t("Stock") }}
                  <span class="text-red-400">*</span>
                </label>
                <InputNumber id="stock" v-model="stock" v-bind="stockAttrs" :class="{ 'p-invalid': errors.stock }" />
                <small v-if="errors.stock" class="text-red-400 dark:text-red-300">
                  {{ errors.stock }}
                </small>
              </div>
              <div class="flex flex-col lg:col-span-4 md:col-span-12 col-span-12 gap-2 mb-3">
                <label for="barcode">{{ t("Barcode") }}</label>
                <InputText
                  id="barcode"
                  v-model="barcode"
                  v-bind="barcodeAttrs"
                  autocomplete="off"
                  :class="{ 'p-invalid': errors.barcode }"
                />
                <small v-if="errors.barcode" class="text-red-400 dark:text-red-300">
                  {{ errors.barcode }}
                </small>
              </div>
            </div>
          </template>
        </Card>

        <!-- Options Card (Variant mode) -->
        <Card v-if="hasVariants" class="mb-4">
          <template #title>
            {{ t("Options") }}
          </template>
          <template #content>
            <OptionsEditor v-model="localOptions" v-model:confirmed="optionsConfirmed" @options-unlocked="onOptionsUnlocked" />
          </template>
        </Card>

        <!-- Variants Card (shown after options are confirmed) -->
        <VariantsPanel
          v-if="hasVariants && optionsConfirmed"
          :options="confirmedOptions"
          :variants="variants"
          :pending-media="pendingMedia"
          class="mb-4"
          @update:variants="onVariantsUpdate"
        />
      </div>

      <!-- Right Column -->
      <div class="md:col-span-4 col-span-12">
        <!-- Product Type Selector -->
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-col gap-2">
              <label>{{ t("Product Type") }}</label>
              <SelectButton
                v-model="hasVariants"
                :options="productTypeOptions"
                :allow-empty="false"
                option-label="label"
                option-value="value"
                fluid
              />
            </div>
          </template>
        </Card>

        <!-- Status -->
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="status">{{ t("Status") }}</label>
              <Select
                id="status"
                v-model="status"
                v-bind="statusAttrs"
                :options="[
                  { name: t('Active'), value: 'active' },
                  { name: t('Inactive'), value: 'inactive' },
                  { name: t('Archived'), value: 'archived' },
                ]"
                option-label="name"
                option-value="value"
              />
            </div>
          </template>
        </Card>

        <!-- Product Organization -->
        <Card class="mb-4">
          <template #title>
            {{ t("Product Organization") }}
          </template>
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="categories">
                {{ t("Categories") }}
                <span class="text-red-400">*</span>
              </label>
              <MultiSelect
                id="categories"
                v-model="categoriesIds"
                v-bind="categoriesIdsAttrs"
                display="chip"
                filter
                :options="props.categories"
                option-label="name"
                option-value="id"
                :class="{ 'p-invalid': errors.categories_ids }"
              />
              <small v-if="errors.categories_ids" class="text-red-400 dark:text-red-300">
                {{ errors.categories_ids }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="brand">{{ t("Brand") }}</label>
              <Select
                id="brand"
                v-model="brandId"
                v-bind="brandIdAttrs"
                filter
                show-clear
                :options="props.brands"
                option-label="name"
                option-value="id"
                :class="{ 'p-invalid': errors.brand_id }"
              />
              <small v-if="errors.brand_id" class="text-red-400 dark:text-red-300">
                {{ errors.brand_id }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="measurement-unit">{{ t("Measurement Unit") }}</label>
              <Select
                id="measurement-unit"
                v-model="measurementUnitId"
                v-bind="measurementUnitIdAttrs"
                show-clear
                :options="props.measurementUnits"
                option-label="name"
                option-value="id"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Toggle variant confirmation -->
    <ConfirmDialog group="headless" />
  </div>
</template>
