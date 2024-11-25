<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          size="small"
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="$inertia.visit(route('purchase-orders'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ $t('Create Purchase Order') }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <PButton
          icon="fa fa-save"
          :label="$t('Save')"
          style="text-transform: uppercase"
          size="small"
          raised
          @click="submit()"
        />
      </div>
    </div>
    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="first_name">{{ $t('Supplier') }}</label>
                  <Select
                    v-model="supplier"
                    class="w-full mt-2"
                    :options="suppliers"
                    :placeholder="$t('Supplier')"
                    option-label="fullname"
                    option-value="id"
                    filter
                    show-clear
                    :loading="suppliersLoading"
                    :invalid="v$.supplier.$invalid && v$.supplier.$dirty"
                    @filter="searchSuppliers"
                  />
                  <small
                    v-if="v$.supplier.$invalid && v$.supplier.$dirty"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ v$.supplier.$errors[0].$message }}
                  </small>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
      <div class="md:col-span-4 col-span-12">
        <Card>
          <template #content>
            <h1>test</h1>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script>
import PButton from "primevue/button";
import Card from "primevue/card";
import Select from "primevue/select";

import { useVuelidate } from "@vuelidate/core";
import {
  createI18nMessage,
  required,
} from "@vuelidate/validators";
import AppLayout from "../../../layouts/admin.vue";
import i18n from "../../../app";

export default {
  components: {
    PButton,
    Card,
    Select,
  },
  layout: AppLayout,
  setup() {
    return {
      v$: useVuelidate(),
    };
  },
  data() {
    return {
      supplier: "",
      suppliersLoading: false,
    };
  },
  mounted() {
    this.searchSuppliers();
  },
  methods: {
    searchSuppliers(event = null) {
      this.suppliersLoading = true;

      const body = {
        params: {
          per_page: 10,
          page: 1,
          order_by: "fullname",
          order_direction: "asc",
        },
      };

      if (event) {
        body.params.filter = event.value.toLowerCase();
      }

      axios
        .get(route("api.suppliers"), body)
        .then((response) => {
          this.suppliers = response.data.data;
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: this.$t("Error"),
            detail: error.response.data.message,
            life: 3000,
          });
        })
        .finally(() => {
          this.suppliersLoading = false;
        });
    },
  },
  validations() {
    const { t } = i18n.global;

    const withI18nMessage = createI18nMessage({
      t,
      messagesPath: "validations",
    });

    return {
      supplier: {
        required: withI18nMessage(required),
      },
    };
  },
};
</script>
