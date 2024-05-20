<template>
  <AppLayout class="md:col-10 md:col-offset-1 col-12">
    <Card>
      <template #header>
        <div class="grid">
          <div class="col-12 flex justify-content-end pt-5 pr-5">
            <PButton
              type="button"
              label="Discard"
              icon="fas fa-close"
              outlined
              severity="primary"
              @click="discardChanges"
            />
            <PButton
              class="ml-2"
              type="button"
              label="Save"
              icon="fas fa-save"
              severity="primary"
              @click="updateProduct"
            />
          </div>
        </div>
      </template>
      <template #content>
        <TabView>
          <TabPanel header="General Info">
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
                <Dropdown
                  id="brand"
                  v-model="brand"
                  filter
                  :options="brands"
                  option-label="name"
                  option-value="id"
                />
              </div>
              <div class="col-12 md:col-6 flex flex-column gap-2">
                <label for="category">Category</label>
                <Dropdown
                  id="category"
                  v-model="category"
                  filter
                  :options="categories"
                  option-label="name"
                  option-value="id"
                />
              </div>
              <div class="col-12 md:col-6 flex flex-column gap-2">
                <label for="measure_unit">Measure Unit</label>
                <Dropdown
                  id="measure_unit"
                  v-model="measureUnit"
                  :options="measureUnits"
                  option-label="name"
                  option-value="id"
                />
              </div>
              <div class="col-12 md:col-6 flex flex-column gap-2">
                <label for="status">Status</label>
                <Dropdown
                  id="status"
                  v-model="status"
                  :options="statusOptions"
                  option-label="label"
                  option-value="value"
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
      </template>
    </Card>
  </AppLayout>
</template>

<script>
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import InputNumber from "primevue/inputnumber";
import PButton from "primevue/button";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import Dropdown from "primevue/dropdown";
import { Inertia } from "@inertiajs/inertia";
import axios from "axios";
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
    Card,
    Dropdown,
  },
  props: {
    product: {
      type: Object,
      default: () => ({}),
    },
    measureUnits: {
      type: Array,
      default: () => [],
    },
    brands: {
      type: Array,
      default: () => [],
    },
    categories: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      identifier: "",
      name: "",
      description: "",
      price: 0,
      stock: 0,
      brand: "",
      category: "",
      measure_unit: "",
      media: [],
      status: "active",
      statusOptions: [
        { label: "ACTIVE", value: "active" },
        { label: "INACTIVE", value: "inactive" },
        { label: "ARCHIVED", value: "archived" },
      ],
    };
  },
  mounted() {
    const { product } = this;

    this.identifier = product.identifier;
    this.name = product.name;
    this.description = product.description;
    this.price = product.price;
    this.stock = product.stock;
    this.brand = product.brand_id;
    this.category = product.category_id;
    this.measureUnit = product.measure_unit_id;
    this.media = product.media;
    this.status = product.status;
  },
  methods: {
    discardChanges() {
      this.$inertia.visit(route("products"));
    },
    updateProduct() {
      const data = {
        identifier: this.identifier,
        name: this.name,
        description: this.description,
        price: this.price,
        stock: this.stock,
        brand_id: this.brand,
        category_id: this.category,
        measure_unit_id: this.measureUnit,
        status: this.status,
      };

      axios
        .put(route("api.products.update", { id: this.product.id }), data)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "Product updated",
            life: 3000,
          });
          setTimeout(() => {
            this.$inertia.visit(route("products"));
          }, 3000);
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
<style>
.p-card-body {
  padding-top: 0px !important;
}
</style>
