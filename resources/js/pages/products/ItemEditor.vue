<template>
  <AppLayout>
    <div class="p-card">
      <TabView>
        <TabPanel header="General Info">
          <div class="flex justify-content-between flex-wrap">
            <div class="flex align-items-center justify-content-center">
              <h3>Item Editor</h3>
            </div>
            <div class="flex align-items-center justify-content-center">
              <Link
                href="/products"
                class="p-button p-button-text"
              >
                <i class="fas fa-arrow-left" /> <span>Back</span>
              </Link>
            </div>
          </div>
          <div class="grid">
            <div class="col-12 md:col-6 flex flex-column gap-2">
              <label for="identifier">Identifier</label>
              <InputText
                id="identifier"
                v-model="identifier"
              />
            </div>
            <div class="col-12 md:col-6 flex flex-column gap-2">
              <label for="name">Name</label>
              <InputText
                id="name"
                v-model="name"
              />
            </div>
            <div class="col-12 flex flex-column gap-2">
              <label for="description">Description</label>
              <Textarea
                id="description"
                v-model="description"
              />
            </div>
            <div class="col-12 md:col-6 flex flex-column gap-2">
              <label for="price">Price (Bs.)</label>
              <InputNumber
                id="price"
                v-model="price"
                :min-fraction-digits="2"
                :max-fraction-digits="2"
              />
            </div>
            <div class="col-12 md:col-6 flex flex-column gap-2">
              <label for="stock">Stock</label>
              <InputNumber
                id="stock"
                v-model="stock"
              />
            </div>
            <div class="col-12 md:col-6 flex flex-column gap-2">
              <label for="brand">Brand</label>
              <InputText
                id="brand"
                v-model="brand"
              />
            </div>
            <div class="col-12 md:col-6 flex flex-column gap-2">
              <label for="category">Category</label>
              <InputText
                id="category"
                v-model="category"
              />
            </div>
            <div class="col-12 flex justify-content-center mt-2">
              <PButton
                label="Save"
                icon="fas fa-save"
              />
            </div>
          </div>
        </TabPanel>
        <TabPanel header="Media">
          <FilesManager
            :files="media"
            @upload-file="uploadFile"
            @delete-file="deleteFile"
          />
        </TabPanel>
      </TabView>
    </div>
  </AppLayout>
</template>

<script>
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import InputNumber from "primevue/inputnumber";
import PButton from "primevue/button";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import FilesManager from "./FilesManager.vue";
import AppLayout from "../../layouts/admin.vue";

export default {
  components: {
    InputText,
    Textarea,
    InputNumber,
    PButton,
    TabView,
    TabPanel,
    FilesManager,
    AppLayout,
  },
  data() {
    return {
      productId: null,
      identifier: "",
      name: "",
      description: "",
      price: 0,
      stock: 0,
      brand: "",
      category: "",
      media: [],
    };
  },
  mounted() {
    this.productId = 1;
    this.getProduct();
  },
  methods: {
    getProduct() {
      // Fetch product from API
      axios.get(`/products/${this.productId}?includes=media,category`)
        .then((response) => {
          const product = response.data.data;
          this.identifier = product.identifier;
          this.name = product.name;
          this.description = product.description;
          this.price = product.price;
          this.stock = product.stock;
          this.brand = product.brand;
          this.category = product.category.name;
          this.media = product.media;
        })
        .catch(() => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch product",
            life: 3000,
          });
        });
    },
    uploadFile(formData) {
      axios
        .post(`products/${this.productId}/media`, formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "File uploaded",
            life: 3000,
          });
          this.getProduct();
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response.data.message,
            life: 3000,
          });
        });
    },
    deleteFile(fileId) {
      // Delete file from API
      axios.delete(`/products/${this.productId}/media/${fileId}`)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "File deleted",
            life: 3000,
          });
          this.getProduct();
        })
        .catch(() => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to delete file",
            life: 3000,
          });
        });
    },
  },
};
</script>
