<script setup lang="ts">
import { Button, InputNumber, InputText, Select, ToggleSwitch, useToast } from "primevue";

import { nextTick, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { toTypedSchema } from "@vee-validate/yup";
import { useForm } from "vee-validate";
import { boolean, number, object, string } from "yup";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import type { InventoryVariantDetail, VariantOverviewPayload } from "@app-types/inventory-variant-types";
import PurchasePriceMargin from "./PurchasePriceMargin.vue";

const props = defineProps<{
  variant: InventoryVariantDetail;
  canEdit: boolean;
}>();
const emit = defineEmits<{
  (e: "update:dirty", value: boolean): void;
}>();
const { t } = useI18n();
const toast = useToast();

const schema = toTypedSchema(
  object({
    identifier: string().nullable().optional().max(50),
    barcode: string().nullable().optional().max(100),
    minimum_stock_level: number().nullable().optional().min(0),
    has_expiration: boolean().required(),
    status: string().required().oneOf(["active", "inactive", "archived"]),
    purchase_price: number().nullable().optional().min(0),
    margin_type: string().required().oneOf(["percent", "amount"]),
    margin_value: number().nullable().optional().min(0),
    price: number().required().min(0),
  }),
);

const initialValues = (): VariantOverviewPayload => ({
  identifier: props.variant.identifier,
  barcode: props.variant.barcode,
  minimum_stock_level: props.variant.minimum_stock_level,
  has_expiration: props.variant.has_expiration,
  status: props.variant.status,
  purchase_price: props.variant.purchase_price,
  margin_type: props.variant.margin_type,
  margin_value: props.variant.margin_value,
  price: props.variant.price,
});

const { handleSubmit, errors, defineField, setErrors, resetForm, meta, submitCount, isSubmitting } = useForm<VariantOverviewPayload>({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: initialValues(),
});

const [identifier, identifierAttrs] = defineField("identifier");
const [barcode, barcodeAttrs] = defineField("barcode");
const [minimumStockLevel, minimumStockLevelAttrs] = defineField("minimum_stock_level");
const [hasExpiration, hasExpirationAttrs] = defineField("has_expiration");
const [status, statusAttrs] = defineField("status");
const [purchasePrice] = defineField("purchase_price");
const [marginType] = defineField("margin_type");
const [marginValue] = defineField("margin_value");
const [price] = defineField("price");

const statusOptions = [
  { name: t("Active"), value: "active" },
  { name: t("Inactive"), value: "inactive" },
  { name: t("Archived"), value: "archived" },
];

watch(
  () => meta.value.dirty,
  (isDirty) => emit("update:dirty", isDirty),
  { immediate: true },
);

watch(
  () => props.variant,
  () => resetForm({ values: initialValues() }),
);

const onSubmit = handleSubmit((values) => {
  router.put(route("inventory.variant.update", { variant: props.variant.id }), { ...values }, {
    preserveScroll: true,
    onSuccess: () => {
      resetForm({ values });
      toast.add({ severity: "success", summary: t("Success"), detail: t("Variant updated successfully"), life: 3000 });
    },
    onError: (serverErrors) => {
      setErrors(serverErrors);
      toast.add({ severity: "error", summary: t("Error"), detail: t("Please review the errors in the form"), life: 3000 });
      nextTick(() => document.querySelector<HTMLInputElement>(".p-invalid")?.focus());
    },
  });
});
</script>

<template>
  <form class="flex flex-col gap-8" @submit.prevent="onSubmit">
    <section class="flex flex-col gap-4" aria-labelledby="variant-identification-heading">
      <div>
        <h2 id="variant-identification-heading" class="m-0 text-lg font-semibold">{{ t("Identification") }}</h2>
      </div>
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-6 flex flex-col gap-2">
          <label for="identifier">{{ t("Identifier") }}</label>
          <InputText
            id="identifier"
            v-model="identifier"
            v-bind="identifierAttrs"
            autocomplete="off"
            :disabled="!canEdit"
            :invalid="submitCount > 0 && !!errors.identifier"
          />
          <small v-if="submitCount > 0 && errors.identifier" class="text-red-500 dark:text-red-300">{{ errors.identifier }}</small>
        </div>
        <div class="col-span-12 md:col-span-6 flex flex-col gap-2">
          <label for="barcode">{{ t("Barcode") }}</label>
          <InputText
            id="barcode"
            v-model="barcode"
            v-bind="barcodeAttrs"
            autocomplete="off"
            :disabled="!canEdit"
            :invalid="submitCount > 0 && !!errors.barcode"
          />
          <small v-if="submitCount > 0 && errors.barcode" class="text-red-500 dark:text-red-300">{{ errors.barcode }}</small>
        </div>
      </div>
    </section>

    <section class="flex flex-col gap-4" aria-labelledby="variant-policy-heading">
      <div>
        <h2 id="variant-policy-heading" class="m-0 text-lg font-semibold">{{ t("Inventory Policy") }}</h2>
      </div>
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-4 flex flex-col gap-2">
          <label for="variant-status">{{ t("Status") }}</label>
          <Select
            input-id="variant-status"
            v-model="status"
            v-bind="statusAttrs"
            :options="statusOptions"
            option-label="name"
            option-value="value"
            :disabled="!canEdit"
            :invalid="submitCount > 0 && !!errors.status"
          />
          <small v-if="submitCount > 0 && errors.status" class="text-red-500 dark:text-red-300">{{ errors.status }}</small>
        </div>
        <div class="col-span-12 md:col-span-4 flex flex-col gap-2">
          <label for="minimum-stock-level">{{ t("Minimum Stock Level") }}</label>
          <InputNumber
            input-id="minimum-stock-level"
            v-model="minimumStockLevel"
            v-bind="minimumStockLevelAttrs"
            :min="0"
            :disabled="!canEdit"
            :invalid="submitCount > 0 && !!errors.minimum_stock_level"
            placeholder="—"
            autocomplete="off"
          />
          <small v-if="submitCount > 0 && errors.minimum_stock_level" class="text-red-500 dark:text-red-300">
            {{ errors.minimum_stock_level }}
          </small>
        </div>
        <div class="col-span-12 md:col-span-4 flex flex-col gap-2">
          <div class="flex items-center gap-2">
            <label for="has-expiration">{{ t("Requires Expiration Date") }}</label>
            <i
              v-tooltip.top="t('When enabled, expiry date will be required when receiving or editing batches')"
              class="fa fa-circle-question cursor-help text-sm text-surface-500 dark:text-surface-400"
              :aria-label="t('When enabled, expiry date will be required when receiving or editing batches')"
              role="img"
              tabindex="0"
            />
          </div>
          <div class="flex min-h-10 items-center gap-3">
            <ToggleSwitch input-id="has-expiration" v-model="hasExpiration" v-bind="hasExpirationAttrs" :disabled="!canEdit" />
            <span class="text-sm text-surface-600 dark:text-surface-300">{{ hasExpiration ? t("Yes") : t("No") }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="flex flex-col gap-4" aria-labelledby="variant-pricing-heading">
      <div>
        <h2 id="variant-pricing-heading" class="m-0 text-lg font-semibold">{{ t("Pricing") }}</h2>
      </div>
      <PurchasePriceMargin
        v-model:purchase-price="purchasePrice"
        v-model:margin-type="marginType"
        v-model:margin-value="marginValue"
        v-model:price="price"
        :variant-id="variant.id"
        :can-edit="canEdit"
      />
      <small v-if="submitCount > 0 && (errors.purchase_price || errors.margin_type || errors.margin_value || errors.price)" class="text-red-500 dark:text-red-300">
        {{ errors.purchase_price ?? errors.margin_type ?? errors.margin_value ?? errors.price }}
      </small>
    </section>

    <div v-if="canEdit" class="flex justify-end border-t border-surface-200 pt-4 dark:border-surface-700">
      <Button
        type="submit"
        :label="t('Save changes')"
        icon="fa fa-save"
        raised
        class="uppercase"
        :loading="isSubmitting"
        :disabled="!meta.dirty"
      />
    </div>
  </form>
</template>
