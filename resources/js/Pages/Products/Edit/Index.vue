<script setup lang="ts">
import { Button, Card, InputText, InputNumber, MultiSelect, Select, SelectButton, Textarea, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string, number, array } from "yup";
import { route } from "ziggy-js";
import { computed, ref } from "vue";
import { configureYupLocale } from "@/validations/yupLocale";
import type { Brand } from "@app-types/brand-types";
import type { Category } from "@app-types/category-types";
import type { MeasurementUnit } from "@app-types/measurement-unit-types";
import type { ProductOption, ProductVariantInline } from "@app-types/product-types";
import AppLayout from "@layouts/admin.vue";
import ProductImages from "@pages/Products/Components/ProductImages.vue";
import OptionsEditor from "@pages/Products/Edit/Components/OptionsEditor.vue";
import VariantsPanel from "@pages/Products/Edit/Components/VariantsPanel.vue";
import { useAuth } from "@/Composables/useAuth";

interface MediaItem {
  id: number;
  thumb_url: string;
  full_url: string;
}

interface ProductData {
  id: number;
  name: string;
  description: string | null;
  status: string;
  brand_id: number | null;
  measurement_unit_id: number | null;
  categories: { id: number; name: string }[];
  media: MediaItem[];
  variants: ProductVariantInline[];
  options: ProductOption[];
  has_variants: boolean;
}

defineOptions({ layout: AppLayout });
const props = defineProps<{
  product: ProductData;
  brands: Brand[];
  categories: Category[];
  measurementUnits: MeasurementUnit[];
}>();
const toast = useToast();
const { t } = useI18n();
const { getSetting } = useAuth();
const currency = getSetting("finance", "currency") ?? "USD";
configureYupLocale(t);

// Product type selector
const productTypeOptions = [
  { label: t("Simple Product"), value: false },
  { label: t("Product with Variants"), value: true },
];

const hasVariants = ref(props.product.has_variants ?? false);

const variantSelectorDisabled = computed(() => {
  return (props.product.options?.length ?? 0) > 0 || !optionsLocked.value;
});

// Options state (server-driven for edit)
const localOptions = ref<Array<{ name: string; values: string[]; saved: boolean }>>([]);
const serverOptions = computed<ProductOption[]>(() => props.product.options ?? []);
const optionsLocked = ref(true);

const onOptionsUpdate = (options: Array<{ name: string; values: string[]; saved: boolean }>) => {
  localOptions.value = options;
};

const onOptionsUnlocked = () => {
  // Variants panel will be disabled via optionsLocked state
};

// Product variants
const productVariants = computed<ProductVariantInline[]>(() => props.product.variants ?? []);

// Extract existing media (excluding removed ones)
const existingMedia = computed(() => {
  return props.product.media.filter((m) => !removeMediaIds.value.includes(m.id));
});

// Media state
const pendingMedia = ref<MediaItem[]>([]);
const removeMediaIds = ref<number[]>([]);

const onRemoveMediaIdsUpdate = (ids: number[]) => {
  removeMediaIds.value = ids;
};

// Default variant data
const defaultVariant = computed(() => props.product.variants?.[0]);

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
    name: props.product.name,
    description: props.product.description ?? "",
    status: props.product.status,
    brand_id: props.product.brand_id ?? null,
    measurement_unit_id: props.product.measurement_unit_id ?? null,
    categories_ids: props.product.categories?.map((c) => c.id) ?? [],
    price: defaultVariant.value?.price ?? 0,
    stock: defaultVariant.value?.stock ?? 0,
    barcode: defaultVariant.value?.barcode ?? "",
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

// Submit
const onSubmit = handleSubmit((values) => {
  const payload: Record<string, unknown> = {
    ...values,
    pending_media_ids: pendingMedia.value.map((m) => m.id),
    remove_media_ids: removeMediaIds.value,
  };

  if (hasVariants.value) {
    payload.has_variants = true;
    delete payload.price;
    delete payload.stock;
    delete payload.barcode;
  } else {
    payload.has_variants = false;
  }

  router.put(route("products.update", props.product.id), payload, {
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("Product updated successfully"),
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
          {{ t("Edit Product") }}
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
            <ProductImages
              v-model:pending-media="pendingMedia"
              :existing-media="existingMedia"
              :remove-media-ids="removeMediaIds"
              @update:remove-media-ids="onRemoveMediaIdsUpdate"
            />
          </template>
        </Card>

        <!-- Details Card (Simple Product mode) -->
        <Card v-if="!hasVariants" class="mb-4">
          <template #title>
            <div class="flex justify-between">
              {{ t("Details") }}
              <Button
                :label="t('Manage Inventory')"
                icon="fa fa-warehouse"
                text
                size="small"
                @click="router.visit(route('inventory.variants.show', { product: props.product.id, variant: defaultVariant?.id }))"
              />
            </div>
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
                  :currency="currency"
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
            <OptionsEditor
              v-model="localOptions"
              v-model:locked="optionsLocked"
              :product-id="props.product.id"
              :server-options="serverOptions"
              @update:model-value="onOptionsUpdate"
              @options-unlocked="onOptionsUnlocked"
            />
          </template>
        </Card>

        <!-- Variants Panel -->
        <VariantsPanel
          v-if="hasVariants"
          :product-id="props.product.id"
          :variants="productVariants"
          :options="serverOptions"
          :disabled="!optionsLocked"
          :product-media="existingMedia"
          class="mb-4"
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
                :disabled="variantSelectorDisabled"
                option-label="label"
                option-value="value"
                fluid
              />
              <small v-if="variantSelectorDisabled" class="text-gray-500">
                {{ t("Delete all options to switch back to simple product") }}
              </small>
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
  </div>
</template>
