<template>
  <AppLayout>
    <Toast />
    <div class="col-12 flex">
      <PButton
        icon="fa fa-arrow-left"
        text
        severity="secondary"
        @click="$inertia.visit(route('products'))"
      />
      <h2 class="ml-2">
        Add Product
      </h2>
    </div>
    <div class="grid">
      <div class="md:col-8 col-12">
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-column gap-2 mb-3">
              <label for="name">Name</label>
              <InputText
                id="name"
                v-model="name"
              />
            </div>
            <div class="flex flex-column gap-2 mb-3">
              <label for="description">Description</label>
              <Textarea
                id="description"
                v-model="description"
              />
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            Media
          </template>
          <template #content>
            <div class="flex flex-column">
              <MediaManager
                :files="files"
                @upload-file="uploadFile"
                @remove-file="removeFile"
              />
            </div>
          </template>
        </Card>
      </div>
      <div class="md:col-4 col-12">
        <Card class="mb-4">
          <template #content>
            <div class="flex flex-column gap-2 mb-3">
              <label for="status">Status</label>
              <Dropdown
                v-model="status"
                :options="[
                  { name: 'Active', value: 'active' },
                  { name: 'Inactive', value: 'inactive' },
                  { name: 'Archived', value: 'archived' }
                ]"
                option-label="name"
                option-value="value"
              />
            </div>
          </template>
        </Card>
        <Card class="mb-4">
          <template #title>
            Product Organization
          </template>
          <template #content>
            <div class="flex flex-column gap-2 mb-3">
              <label for="category">Category</label>
              <MultiSelect
                id="category"
                v-model="category"
                display="chip"
                filter
                :options="categories"
                option-label="name"
                option-value="id"
              />
            </div>
            <div class="flex flex-column gap-2 mb-3">
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
            <div class="flex flex-column gap-2 mb-3">
              <label for="measure_unit">Measure Unit</label>
              <Dropdown
                id="measure_unit"
                v-model="measureUnit"
                :options="measureUnits"
                option-label="name"
                option-value="id"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import Card from "primevue/card";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import { Inertia } from "@inertiajs/inertia";
import Textarea from "primevue/textarea";
import Dropdown from "primevue/dropdown";
import MultiSelect from "primevue/multiselect";
import Toast from "primevue/toast";
import AppLayout from "../../layouts/admin.vue";
import MediaManager from "../../UI/MediaManager.vue";

export default {
  components: {
    AppLayout,
    PButton,
    Card,
    InputText,
    Textarea,
    Dropdown,
    MediaManager,
    MultiSelect,
    Toast,
  },
  props: {
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
      name: "",
      description: "",
      status: "active",
      brand: "",
      measureUnit: "",
      files: [],
      category: [],
    };
  },
  methods: {
    uploadFile(formData) {
      axios
        .post("products/media", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then((response) => {
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "File uploaded",
            life: 3000,
          });
          this.files.push(
            {
              id: response.data.data.id,
              url: response.data.data.url,
            },
          );
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error,
            life: 3000,
          });
        });
    },
    removeFile(id) {
      axios
        .delete(route("api.products.media.destroy-draft", id))
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "File removed",
            life: 3000,
          });
          this.files = this.files.filter((f) => f.id !== id);
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error,
            life: 3000,
          });
        });
    },
  },
};
</script>
