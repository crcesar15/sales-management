<template>
  <div>
    <Dialog
      v-model:visible="showProduct"
      modal
      :header="$t('Suppliers')"
      :style="{ width: '50rem' }"
      :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
      <template #header>
        <h3>{{ $t("Product Editor") }}</h3>
      </template>
      <template #content>
        <div>{{ product.id }}</div>
        <Card>
          <template #content>
            <div>{{ product.id }}</div>
          </template>
        </Card>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <PButton
            :label="$t('Cancel')"
            icon="fa fa-times"
            outlined
            @click="hide"
          />
          <PButton
            :label="$t('Save')"
            icon="fa fa-save"
            @click="save"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script>
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Dialog from "primevue/dialog";
import PButton from "primevue/button";
import i18n from "../../../app";

export default {
  components: {
    Card,
    InputText,
    Select,
    PButton,
    Dialog,
  },
  props: {
    product: {
      type: Object,
      required: true,
      default: () => ({
        id: null,
        price: 0,
        payment_terms: null,
        details: null,
      }),
    },
  },
  data() {
    return {
      showProduct: false,
    };
  },
  watch: {
    product: {
      handler() {
        this.showProduct = true;
      },
      deep: true,
    },
  },
  mounted() {
    console.log("editor");
  },
};
</script>
