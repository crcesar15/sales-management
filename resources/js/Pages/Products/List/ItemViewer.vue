<template>
  <!-- Modal -->
  <div>
    <Dialog
      v-model:visible="showDialog"
      modal
      :style="{ width: '60vw' }"
      :breakpoints="{ '1100px': '70vw', '850px': '85vw', '500px': '95vw' }"
      @hide="closeModal"
    >
      <div
        class="grid grid-cols-12"
      >
        <div
          class="
            lg:col-span-4
            md:col-span-6
            col-span-12
            flex
            flex-wrap
            justify-center
            content-center
            w-full
            mb-4
          "
        >
          <div
            v-if="productMedia.length > 1"
            class="flex flex-wrap justify-center content-center"
          >
            <Carousel
              :value="productMedia"
              :num-visible="1"
              :num-scroll="1"
              style="width: 300px;"
              class="lg:w-4/6 md:w-5/6 w-full"
            >
              <template #item="image">
                <Image
                  :src="image.data.url"
                  image-class="
                    w-full
                    rounded-3xl
                    shadow-xl
                    border-2
                    border-slate-300
                    dark:border-slate-700
                  "
                />
              </template>
            </Carousel>
          </div>
          <div
            v-else-if="productMedia.length === 1"
            style="width: 250px;"
          >
            <Image
              :src="productMedia[0].url"
              image-class="
                w-full
                rounded-3xl
                shadow-xl
                border-2
                border-slate-300
                dark:border-slate-700
              "
            />
          </div>
          <div v-else>
            <!-- not found div -->
            <div
              class="
                bg-surface-50
                dark:bg-surface-950
                flex
                flex-wrap
                justify-center
                content-center
                text-primary
                shadow-lg
                rounded
              "
              style="height: 250px; width: 250px;"
            >
              <p>
                <i class="fa fa-image" /> {{ $t('No Image') }}
              </p>
            </div>
          </div>
        </div>
        <div
          class="
            lg:col-span-8
            md:col-span-6
            col-span-12
            bg-surface-50
            dark:bg-surface-950
            rounded-border
            grid
            grid-cols-12
            p-8
            w-full
          ">
          <div class="md:col-span-6 col-span-12 pt-0 pb-0">
            <p>
              <strong>
                {{ $t('Name') }}:
              </strong><br/>
              {{ product?.name }}
            </p>
            <p>
              <strong>
                {{ $t('Price') }}:
              </strong><br/>
              <div v-if="product?.variants && product?.variants.length > 1">
                <!--Get min value-->
                {{ formatCurrency(product?.variants.reduce((min, variant) => variant.price < min ? variant.price : min, product.variants[0].price).toString()) }}
                <!--Get max value-->
                -
                {{ formatCurrency(product?.variants.reduce((max, variant) => variant.price > max ? variant.price : max, product.variants[0].price).toString()) }}
              </div>
              <div v-else>
                {{ formatCurrency(product?.variants ? product?.variants[0]?.price.toString() : '0') }}
              </div>
            </p>
            <p>
              <strong>
                {{ $t('Brand') }}:
              </strong><br/>
              {{ product?.brand?.name }}
            </p>
          </div>
          <div class="md:col-span-6 col-span-12 pt-0 pb-0">
            <p>
              <strong>
                {{ $t('Measurement Unit') }}:
              </strong><br/>
              {{ product?.measurement_unit?.name }}
            </p>
            <p>
              <strong>
                {{ $t('Stock') }}:
              </strong><br/>
              <div v-if="product?.variants && product?.variants?.length > 1">
                {{ $t('variants stock', {stock: product?.variants ? product?.variants.reduce((acc, variant) => acc + variant.stock, 0) : 0, counter: product?.variants ? product?.variants.length : 0}) }}
              </div>
              <div v-else>
                {{ $t('variant stock', {stock: product?.variants ? product?.variants[0].stock : 0}) }}
              </div>
            </p>
            <p>
              <strong>
                {{ $t('Category') }}:
              </strong><br/>
              {{ product?.categories?.reduce((acc, category) => acc + category.name + ", ", "").slice(0, -2) }}
            </p>
          </div>
          <div class="col-span-12 pt-4">
            <p>
              <strong>
                {{ $t('Description') }}:
              </strong><br/>
              {{ product?.description }}
            </p>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import {
  Dialog,
  Carousel,
  Image,
} from "primevue"
import { computed, defineProps } from "vue";
import { Product } from "@app-types/product-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

const props = defineProps<{
  product: Product,
}>();

const showDialog = defineModel<boolean>("showDialog", {
  default: false,
});

const { formatCurrency } = useCurrencyFormatter();

const productMedia = computed(() => {
  if (!props.product?.variants) {
    return [];
  }
  return props.product?.variants.map(variant => variant.media).flat();
})

const closeModal = () => {
  showDialog.value = false;
};
</script>
