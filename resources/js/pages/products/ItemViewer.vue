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
        class="grid flex-column"
      >
        <div
          class="
            flex flex-wrap justify-content-center align-content-center
            col
            "
        >
          <div
            v-if="product.media.length > 1"
            class="flex flex-wrap justify-content-center align-content-center"
          >
            <Carousel
              :value="product.media"
              :num-visible="1"
              :num-scroll="1"
              class="lg:w-8 md:w-10 w-12"
            >
              <template #item="image">
                <Image
                  :src="image.data.url"
                  class="border-round"
                  image-class="w-full"
                />
              </template>
            </Carousel>
          </div>
          <div v-else-if="product.media.length === 1">
            <Image
              :src="product.media[0].url"
              class="border-round"
              image-class="col"
            />
          </div>
          <div v-else>
            <!-- not found div -->
            <div
              class="flex flex-wrap justify-content-center align-content-center text-primary shadow-2 border-round mb-4"
              style="height: 250px; width: 250px;"
            >
              <p>
                <i class="fa fa-image" /> {{ $t('No Image') }}
              </p>
            </div>
          </div>
        </div>
        <div class="surface-100 border-round grid p-2 col">
          <div class="md:col-6 col-12 pt-0 pb-0">
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
          <div class="md:col-6 col-12 pt-0 pb-0">
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
          <div class="col-12 pt-0">
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
