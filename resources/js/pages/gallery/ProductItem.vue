<template>
  <div
    class="surface-card border-round shadow-2 flex flex-wrap justify-content-center align-items-center p-2 flex-column mb-4 sm:mb-1"
    style="height: 500px;"
  >
    <h4>{{ product.name }}</h4>
    <div>
      <Image
        v-if="media.length > 0"
        :src="media[0].src"
        :alt="media[0].alt"
        :title="media[0].title"
        style="max-height: 200px; max-width: 200px;"
      />
      <div
        v-else
        class="flex flex-wrap justify-content-center align-items-center border-round shadow-2"
        style="height: 200px; width: 200px;"
      >
        <p><i class="fa fa-image" /> No images yet</p>
      </div>
    </div>
    <div class="w-full flex flex-wrap justify-content-start pl-3">
      <span class="text-primary font-bold mt-1">Price: {{ product.price }} BOB</span>
    </div>
    <p
      class="text-sm pr-3 pl-3"
      style="min-height: 100px;"
    >
      {{ product.description }}
    </p>
    <div class="flex w-full">
      <PButton
        label="View"
        icon="fa fa-eye"
        class="w-full m-2"
        text
        raised
      />
      <PButton
        label="Edit"
        icon="fa fa-edit"
        class="w-full m-2"
        text
        raised
        severity="secondary"
      />
    </div>
  </div>
</template>

<script>
import Image from "primevue/image";
import PButton from "primevue/button";

export default {
  components: {
    Image,
    PButton,
  },
  props: {
    product: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      media: [],
    };
  },
  watch: {
    product: {
      immediate: true,
      handler() {
        this.media = this.product.media.map((media) => ({
          src: `${window.location.origin}${media.url}`,
          alt: this.product.name,
          title: this.product.name,
        }));
      },
    },
  },
};
</script>
