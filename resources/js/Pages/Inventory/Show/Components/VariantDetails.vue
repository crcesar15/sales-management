<script setup lang="ts">
import { Button, InputNumber, InputText, Select, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { toTypedSchema } from "@vee-validate/yup";
import { useForm } from "vee-validate";
import { number, object, string } from "yup";
import { route } from "ziggy-js";
import type { InventoryVariantDetail, InventoryProductDetail } from "@/Types/inventory-variant-types";
import { useAuth } from "@/Composables/useAuth";

const props = defineProps<{
  product: InventoryProductDetail;
  variant: InventoryVariantDetail;
  canEdit?: boolean;
}>();
const toast = useToast();
const { t } = useI18n();
const { getSetting } = useAuth();
const currency = getSetting("finance", "currency") ?? "USD";

const schema = toTypedSchema(
  object({
    identifier: string().nullable().optional().max(50),
    barcode: string().nullable().optional().max(100),
    price: number().required().min(0),
    status: string().required().oneOf(["active", "inactive", "archived"]),
  }),
);

const { handleSubmit, errors, defineField, isSubmitting, setErrors, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    identifier: props.variant.identifier ?? "",
    barcode: props.variant.barcode ?? "",
    price: props.variant.price,
    status: props.variant.status,
  },
});

const [identifier, identifierAttrs] = defineField("identifier");
const [barcode, barcodeAttrs] = defineField("barcode");
const [price, priceAttrs] = defineField("price");
const [status, statusAttrs] = defineField("status");

const statusOptions = [
  { name: t("Active"), value: "active" },
  { name: t("Inactive"), value: "inactive" },
  { name: t("Archived"), value: "archived" },
];

const onSubmit = handleSubmit((values) => {
  router.put(route("variant.update", { product: props.product.id, variant: props.variant.id }), values, {
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Variant updated successfully"), life: 3000 });
    },
    onError: (errs) => {
      const fieldErrors = errs as Record<string, string>;
      setErrors(fieldErrors);
      toast.add({ severity: "error", summary: t("Error"), detail: t(Object.values(fieldErrors)[0] ?? "An error occurred"), life: 3000 });
    },
  });
});
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Variant identity -->
    <form @submit.prevent="onSubmit">
      <!-- Identification -->
      <div class="mb-5">
        <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
          {{ t("Identification") }}
        </h4>
        <div class="grid grid-cols-12 gap-4">
          <div class="md:col-span-6 col-span-12 flex flex-col gap-2">
            <label for="identifier">{{ t("Identifier") }}</label>
            <InputText
              id="identifier"
              v-model="identifier"
              v-bind="identifierAttrs"
              autocomplete="off"
              :disabled="!props.canEdit"
              :class="{ 'p-invalid': submitCount > 0 && !!errors.identifier }"
            />
            <small v-if="submitCount > 0 && errors.identifier" class="text-red-400 dark:text-red-300">{{ errors.identifier }}</small>
          </div>

          <div class="md:col-span-6 col-span-12 flex flex-col gap-2">
            <label for="barcode">{{ t("Barcode") }}</label>
            <InputText id="barcode" v-model="barcode" v-bind="barcodeAttrs" autocomplete="off" :disabled="!props.canEdit" :class="{ 'p-invalid': submitCount > 0 && !!errors.barcode }" />
            <small v-if="submitCount > 0 && errors.barcode" class="text-red-400 dark:text-red-300">{{ errors.barcode }}</small>
          </div>
        </div>
      </div>

      <!-- Commerce -->
      <div>
        <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
          {{ t("Commerce") }}
        </h4>
        <div class="grid grid-cols-12 gap-4">
          <div class="lg:col-span-4 md:col-span-6 col-span-12 flex flex-col gap-2">
            <label for="price">
              {{ t("Price") }}
              <span class="text-red-400">*</span>
            </label>
            <InputNumber
              id="price"
              v-model="price"
              v-bind="priceAttrs"
              mode="currency"
              :currency="currency"
              :min="0"
              :disabled="!props.canEdit"
              :class="{ 'p-invalid': submitCount > 0 && !!errors.price }"
            />
            <small v-if="submitCount > 0 && errors.price" class="text-red-400 dark:text-red-300">{{ errors.price }}</small>
          </div>

          <div class="lg:col-span-4 md:col-span-6 col-span-12 flex flex-col gap-2">
            <label>{{ t("Stock") }}</label>
            <div class="p-3 bg-surface-50 dark:bg-surface-950 rounded-border text-lg font-semibold">
              {{ props.variant.stock }}
            </div>
            <small class="text-gray-500">{{ t("Stock is managed via batches and reception orders") }}</small>
          </div>

          <div class="lg:col-span-4 md:col-span-6 col-span-12 flex flex-col gap-2">
            <label for="status">{{ t("Status") }}</label>
            <Select
              id="status"
              v-model="status"
              v-bind="statusAttrs"
              :options="statusOptions"
              option-label="name"
              option-value="value"
              :disabled="!props.canEdit"
              :class="{ 'p-invalid': submitCount > 0 && !!errors.status }"
            />
            <small v-if="submitCount > 0 && errors.status" class="text-red-400 dark:text-red-300">{{ errors.status }}</small>
          </div>
        </div>
      </div>

      <div v-if="props.canEdit" class="flex justify-end mt-6">
        <Button :label="t('Save')" icon="fa fa-save" raised :loading="isSubmitting" type="submit" class="uppercase" />
      </div>
    </form>
  </div>
</template>
