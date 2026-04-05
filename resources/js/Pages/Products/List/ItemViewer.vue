<template>
  <Dialog
    v-model:visible="showDialog"
    modal
    :header="product?.name"
    :style="{ width: '60vw' }"
    :breakpoints="{ '1100px': '70vw', '850px': '85vw', '500px': '95vw' }"
    @hide="closeModal"
  >
    <div class="grid grid-cols-12">
      <div
        class="lg:col-span-4 md:col-span-6 col-span-12 flex flex-wrap justify-center content-center w-full mb-4"
      >
        <Carousel
          v-if="productMedia.length > 1"
          :value="productMedia"
          :num-visible="1"
          :num-scroll="1"
          style="width: 300px;"
          class="lg:w-4/6 md:w-5/6 w-full"
        >
          <template #item="image">
            <Image
              :src="image.data.full_url"
              image-class="w-full rounded-3xl shadow-xl border-2 border-slate-300 dark:border-slate-700"
            />
          </template>
        </Carousel>
        <div
          v-else-if="productMedia.length === 1"
          style="width: 250px;"
        >
          <Image
            :src="productMedia[0].full_url"
            image-class="w-full rounded-3xl shadow-xl border-2 border-slate-300 dark:border-slate-700"
          />
        </div>
        <div v-else>
          <div
            class="bg-surface-50 dark:bg-surface-950 flex flex-wrap justify-center content-center text-primary shadow-lg rounded"
            style="height: 250px; width: 250px;"
          >
            <p>
              <i class="fa fa-image" /> {{ t('No Image') }}
            </p>
          </div>
        </div>
      </div>
      <div
        class="lg:col-span-8 md:col-span-6 col-span-12 bg-surface-50 dark:bg-surface-950 rounded-border grid grid-cols-12 p-8 w-full"
      >
        <div class="md:col-span-6 col-span-12 pt-0 pb-0">
          <p>
            <strong>{{ t('Name') }}:</strong><br />
            {{ product?.name }}
          </p>
          <p>
            <strong>{{ t('Price') }}:</strong><br />
            <span v-if="product && product.price_min !== product.price_max">
              {{ formatCurrency(String(product?.price_min)) }} - {{ formatCurrency(String(product?.price_max)) }}
            </span>
            <span v-else>
              {{ formatCurrency(String(product?.price_min ?? 0)) }}
            </span>
          </p>
          <p>
            <strong>{{ t('Brand') }}:</strong><br />
            {{ product?.brand?.name ?? '—' }}
          </p>
        </div>
        <div class="md:col-span-6 col-span-12 pt-0 pb-0">
          <p>
            <strong>{{ t('Stock') }}:</strong><br />
            <span v-if="product && product.variants.length > 1">
              {{ t('variants stock', { stock: product.stock, counter: product.variants.length }) }}
            </span>
            <span v-else>
              {{ t('variant stock', { stock: product?.stock ?? 0 }) }}
            </span>
          </p>
          <p>
            <strong>{{ t('Category') }}:</strong><br />
            {{ product?.categories.map(c => c.name).join(', ') || '—' }}
          </p>
        </div>
        <div
          v-if="product?.description"
          class="col-span-12 pt-4"
        >
          <p>
            <strong>{{ t('Description') }}:</strong><br />
            {{ product.description }}
          </p>
        </div>
      </div>
    </div>
    <template #footer>
      <Button
        :label="t('Edit')"
        icon="fa fa-edit"
        @click="goToEdit"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import {
  Dialog,
  Carousel,
  Image,
  Button,
} from "primevue"
import { computed } from "vue"
import { type ProductListResponse } from "@/Types/product-types"
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter"
import { useI18n } from "vue-i18n"
import { router } from "@inertiajs/vue3"
import { route } from "ziggy-js"

const props = defineProps<{
  product: ProductListResponse | null
}>()

const showDialog = defineModel<boolean>("showDialog", {
  default: false,
})

const { t } = useI18n()
const { formatCurrency } = useCurrencyFormatter()

const productMedia = computed(() => props.product?.media ?? [])

const goToEdit = () => {
  if (props.product) {
    router.visit(route("products.edit", { product: props.product.id }))
  }
}

const closeModal = () => {
  showDialog.value = false
}
</script>
