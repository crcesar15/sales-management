<template>
  <!-- Modal -->
  <div>
    <Dialog
      v-model:visible="visible"
      modal
      :style="{ width: '30vw' }"
      :breakpoints="{ '1199px': '60vw', '575px': '90vw' }"
      @hide="clearSelection"
    >
      <div
        class="flex flex-col"
      >
        <div
          class="
            flex
            flex-wrap
            justify-center
            content-center
            w-full
            mb-4
          "
        >
          <div
            v-if="product.media.length > 1"
            class="flex flex-wrap justify-center content-center"
          >
            <Carousel
              :value="product.media"
              :num-visible="1"
              :num-scroll="1"
              class="lg:w-4/6 md:w-5/6 w-full"
            >
              <template #item="image">
                <Image
                  :src="image.data.url"
                  image-class="w-full rounded-border shadow-lg border border-surface"
                />
              </template>
            </Carousel>
          </div>
          <div
            v-else-if="product.media.length === 1"
            style="width: 250px;"
          >
            <Image
              :src="product.media[0].url"
              image-class="w-full rounded-border shadow-lg border border-surface"
            />
          </div>
          <div v-else>
            <!-- not found div -->
            <div
              class="bg-surface-50 dark:bg-surface-950 flex flex-wrap justify-center content-center text-primary shadow-lg rounded-border"
              style="height: 250px; width: 250px;"
            >
              <p>
                <i class="fa fa-image" /> {{ $t('No Image') }}
              </p>
            </div>
          </div>
        </div>
        <div class="bg-surface-50 dark:bg-surface-950 rounded-border grid grid-cols-12 p-8 w-full">
          <div class="md:col-span-6 col-span-12 pt-0 pb-0">
            <p>
              <strong>
                {{ $t('Name') }}:
              </strong>
              {{ product?.name }}
            </p>
            <p>
              <strong>
                {{ $t('Price') }}:
              </strong>
              Bs. {{ product?.price }}
            </p>
            <p>
              <strong>
                {{ $t('Brand') }}:
              </strong>
              {{ product?.brand?.name }}
            </p>
          </div>
          <div class="md:col-span-6 col-span-12 pt-0 pb-0">
            <p>
              <strong>
                {{ $t('Measure Unit') }}:
              </strong>
              {{ product?.measure_unit?.name }}
            </p>
            <p>
              <strong>
                {{ $t('Stock') }}:
              </strong>
              {{ product?.stock }}
            </p>
            <p>
              <strong>
                {{ $t('Category') }}:
              </strong>
              {{ product?.category?.name }}
            </p>
          </div>
          <div class="col-span-12 pt-4">
            <p>
              <strong>
                {{ $t('Description') }}:
              </strong>
              {{ product?.description }}
            </p>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script>
import Dialog from "primevue/dialog";
import Carousel from "primevue/carousel";
import Image from "primevue/image";

export default {
  components: {
    Dialog,
    Image,
    Carousel,
  },
  props: {
    product: {
      type: Object,
      required: true,
    },
    showDialog: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      visible: false,
    };
  },
  watch: {
    showDialog(value) {
      this.visible = value;
    },
  },
  methods: {
    clearSelection() {
      this.$emit("clearSelection");
    },
  },
};
</script>
