<template>
  <!-- Modal -->
  <div>
    <Dialog
      v-model:visible="visible"
      header="Product Viewer"
      modal
      @hide="clearSelection"
    >
      <div
        class="grid"
      >
        <div
          class="md:col-4 col-12 flex flex-wrap justify-content-center"
        >
          <div v-if="product.media[0]?.url">
            <Image
              :src="product.media[0].url"
              alt="Image"
              height="250px"
            />
          </div>
          <div v-else>
            <!-- not found div -->
            <div
              class="flex flex-wrap justify-content-center align-content-center text-primary shadow-2 border-round"
              style="height: 250px; width: 250px;"
            >
              <p>
                <i class="fa fa-image" /> No image yet
              </p>
            </div>
          </div>
        </div>
        <div
          class="md:col-8 col-12"
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
import Image from "primevue/image";

export default {
  components: {
    Dialog,
    Image,
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
