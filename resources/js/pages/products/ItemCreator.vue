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
            <h3>Media</h3>
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
        <Card>
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
import Toast from "primevue/toast";
import AppLayout from "../../layouts/admin.vue";
import MediaManager from "./MediaManager.vue";

export default {
  components: {
    AppLayout,
    PButton,
    Card,
    InputText,
    Textarea,
    Dropdown,
    MediaManager,
    Toast,
  },
  data() {
    return {
      name: "",
      description: "",
      status: "active",
      files: [
        {
          id: 14,
          url: "https://picsum.photos/200",
        },
      ],
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
