<template>
  <!-- Modal -->
  <div>
    <Dialog
      v-model:visible="showModal"
      header="Product Viewer"
      modal
      @hide="clearSelection"
    >
      <div
        class="grid"
      >
        <div
          class="md:col-6 col-12"
        >
          <div v-if="product.image">
            <Image
              src="product.image"
              alt="Image"
              height="250px"
            />
          </div>
          <div v-else>
            <Skeleton
              width="100%"
              height="250px"
            />
          </div>
        </div>
        <div
          class="md:col-6 col-12"
        >
          <p>
            <strong>
              # Serie:
            </strong>
            {{ product?.identifier }}
          </p>
          <p>
            <strong>
              Description:
            </strong>
            {{ product?.description }}
          </p>
          <p>
            <strong>
              Price:
            </strong>
            {{ product?.price }}
          </p>
          <p>
            <strong>
              Brand:
            </strong>
            {{ product?.brand }}
          </p>
          <p>
            <strong>
              Stock:
            </strong>
            {{ product?.stock }}
          </p>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script>
import Dialog from "primevue/dialog";
import Skeleton from "primevue/skeleton";
import Image from "primevue/image";

export default {
  components: {
    Dialog,
    Skeleton,
    Image,
  },
  props: {
    product: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      showModal: false,
    };
  },
  watch: {
    product() {
      if (this.product.id) {
        this.showModal = true;
      }
    },
  },
  methods: {
    clearSelection() {
      this.$emit("clearSelection");
    },
  },
};
</script>
