<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Settings") }}
      </h2>
      <PButton
        icon="fa fa-save"
        :label="$t('Save')"
        class="uppercase"
        raised
        @click="submit()"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <div class="grid grid-cols-12 gap-4">
          <div
            v-for="setting in settings"
            :key="setting.id"
            class="col-span-12 md:col-span-6"
          >
            <div class="flex flex-col gap-2 mb-3">
              <label :for="setting.id">{{ setting.name }}</label>
              <InputText
                :id="setting.id"
                v-model="setting.value"
                autocomplete="off"
              />
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script>
import Card from "primevue/card";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import ConfirmDialog from "primevue/confirmdialog";
import AppLayout from "../../layouts/admin.vue";

export default {
  components: {
    PButton,
    InputText,
    ConfirmDialog,
    Card,
  },
  layout: AppLayout,
  data() {
    return {
      settings: [],
    };
  },
  mounted() {
    this.getSettings();
  },
  methods: {
    getSettings() {
      axios.get(route("api.settings")).then((response) => {
        this.settings = response.data.data;
      });
    },
    submit() {
      this.$confirm.require({
        message: this.$t("Are you sure you want to save these settings?"),
        header: this.$t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: this.$t("Cancel"),
        acceptLabel: this.$t("Save"),
        rejectClass: "p-button-secondary",
        accept: () => {
          axios
            .put(route("api.settings.update"), { settings: this.settings })
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: this.$t("Success"),
                detail: this.$t("Settings updated successfully."),
                life: 3000,
              });
              // reload the page to apply changes
              window.location.reload();
            })
            .catch((error) => {
              this.$toast.add({
                severity: "error",
                summary: this.$t("Error"),
                detail: error.response.data.message,
                life: 3000,
              });
            });
        },
      });
    },
  },
};
</script>

<style>

</style>
