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
          {{ t('Add Product') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <Button
          icon="fa fa-save"
          :label="t('Save')"
          class="uppercase"
          raised
          :loading="isSubmitting"
          @click="onSubmit()"
        />
      </div>
    </div>
    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="name">{{ t('Name') }} <span class="text-red-400">*</span></label>
              <InputText
                id="name"
                v-model="name"
                v-bind="nameAttrs"
                autocomplete="off"
                :class="{ 'p-invalid': errors.name }"
              />
              <small v-if="errors.name" class="text-red-400 dark:text-red-300">
                {{ errors.name }}
              </small>
            </div>
            <div class="flex flex-col gap-2 mb-3">
              <label for="description">{{ t('Description') }}</label>
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
        <Card class="mb-4">
          <template #title>
            {{ t('Images') }}
          </template>
          <template #content>
            <ProductImages
              v-model:pending-media="pendingMedia"
              :remove-media-ids="[]"
              @update:remove-media-ids="() => {}"
            />
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            {{ t('Details') }}
          </template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="flex flex-col lg:col-span-4 md:col-span-6 col-span-12 gap-2 mb-3">
                <label for="price">{{ t('Base Price') }} <span class="text-red-400">*</span></label>
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
                <label for="stock">{{ t('Stock') }} <span class="text-red-400">*</span></label>
                <InputNumber
                  id="stock"
                  v-model="stock"
                  v-bind="stockAttrs"
                  :class="{ 'p-invalid': errors.stock }"
                />
                <small v-if="errors.stock" class="text-red-400 dark:text-red-300">
                  {{ errors.stock }}
                </small>
              </div>
              <div class="flex flex-col lg:col-span-4 md:col-span-12 col-span-12 gap-2 mb-3">
                <label for="barcode">{{ t('Barcode') }}</label>
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
      </div>
      <div class="md:col-span-4 col-span-12">
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="status">{{ t('Status') }}</label>
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
        <Card class="mb-4">
          <template #title>
            {{ t('Product Organization') }}
          </template>
          <template #content>
            <div class="flex flex-col gap-2 mb-3">
              <label for="categories">{{ t('Categories') }} <span class="text-red-400">*</span></label>
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
              <label for="brand">{{ t('Brand') }}</label>
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
              <label for="measurement-unit">{{ t('Measurement Unit') }}</label>
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

<script setup lang="ts">
import {
  Button,
  Card,
  InputText,
  InputNumber,
  MultiSelect,
  Select,
  Textarea,
  useToast,
} from "primevue"

import { router } from "@inertiajs/vue3"
import { useI18n } from "vue-i18n"
import { useForm } from "vee-validate"
import { toTypedSchema } from "@vee-validate/yup"
import { object, string, number, array } from "yup"
import { route } from "ziggy-js"
import { computed, ref } from "vue"
import { configureYupLocale } from "@/validations/yupLocale"
import type { Brand } from "@app-types/brand-types"
import type { Category } from "@app-types/category-types"
import type { MeasurementUnit } from "@app-types/measurement-unit-types"
import AppLayout from "@layouts/admin.vue"
import ProductImages from "@pages/Products/Components/ProductImages.vue"

const toast = useToast()
const { t } = useI18n()
configureYupLocale(t)

defineOptions({ layout: AppLayout })

const props = defineProps<{
  brands: Brand[]
  categories: Category[]
  measurementUnits: MeasurementUnit[]
}>()

// Schema
const schema = toTypedSchema(
  object({
    name: string().required().max(255),
    description: string().nullable().optional().max(350),
    status: string().required().oneOf(['active', 'inactive', 'archived']),
    brand_id: number().nullable().optional(),
    measurement_unit_id: number().nullable().optional(),
    categories_ids: array().of(number().required()).required().min(1, t('At least one category is required')),
    price: number().required().min(0),
    stock: number().required().integer().min(0),
    barcode: string().nullable().optional().max(100),
  }),
)

const { handleSubmit, errors, defineField, isSubmitting, setErrors } = useForm({
  validationSchema: schema,
  initialValues: {
    name: '',
    description: '',
    status: 'active',
    brand_id: null as number | null,
    measurement_unit_id: null as number | null,
    categories_ids: [] as number[],
    price: 0,
    stock: 0,
    barcode: '' as string | null,
  },
})

const [name, nameAttrs] = defineField('name')
const [description, descriptionAttrs] = defineField('description')
const [status, statusAttrs] = defineField('status')
const [brandId, brandIdAttrs] = defineField('brand_id')
const [measurementUnitId, measurementUnitIdAttrs] = defineField('measurement_unit_id')
const [categoriesIds, categoriesIdsAttrs] = defineField('categories_ids')
const [price, priceAttrs] = defineField('price')
const [stock, stockAttrs] = defineField('stock')
const [barcode, barcodeAttrs] = defineField('barcode')

// Description char counter
const descriptionCharCount = computed(() => (description.value ?? '').length)
const onDescriptionInput = () => {
  // VeeValidate tracks via v-model already; this just ensures reactivity for counter
}

// Pending media tracking
const pendingMedia = ref<Array<{ id: number; thumb_url: string; full_url: string }>>([])

// Submit
const onSubmit = handleSubmit((values) => {
  const payload = {
    ...values,
    pending_media_ids: pendingMedia.value.map((m) => m.id),
  }

  router.post(route('products.store'), payload, {
    onSuccess: () => {
      toast.add({
        severity: 'success',
        summary: t('Success'),
        detail: t('Product created successfully'),
        life: 3000,
      })
      router.visit(route('products'))
    },
    onError: (errs) => {
      setErrors(errs)
      toast.add({
        severity: 'error',
        summary: t('Error'),
        detail: t(Object.values(errs)[0] ?? 'An error occurred'),
        life: 3000,
      })
    },
  })
})
</script>
