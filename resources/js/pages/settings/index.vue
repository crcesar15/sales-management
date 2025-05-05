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
    async getSettings() {
      const response = await axios.get(route("api.settings"));
      this.settings = response.data.data;
    },
  },
};
</script>

<style>

</style>
