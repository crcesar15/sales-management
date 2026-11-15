<script setup lang="ts">
import { Button, Message, ToggleSwitch, useToast } from "primevue";
import { computed, nextTick, shallowRef, useTemplateRef, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { toTypedSchema } from "@vee-validate/yup";
import { useForm } from "vee-validate";
import { number, object, string } from "yup";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import type { InventoryVariantDetail, VariantPricingPayload, VariantPricingUpdatePayload } from "@app-types/inventory-variant-types";
import ReplacementPricing from "./ReplacementPricing.vue";

const props = defineProps<{ variant: InventoryVariantDetail; canEdit: boolean; siblingCount: number }>();
const emit = defineEmits<{ "update:dirty": [value: boolean] }>();
const { t } = useI18n();
const toast = useToast();
const formElement = useTemplateRef<HTMLFormElement>("pricingForm");
const replicatePricing = shallowRef(false);
const isSaving = shallowRef(false);
const schema = toTypedSchema(
  object({
    pricing_catalog_id: number().nullable().optional(),
    reference_id: number().nullable().optional(),
    margin_type: string().required().oneOf(["percent", "amount"]),
    margin_value: number()
      .nullable()
      .optional()
      .min(0)
      .when("margin_type", { is: "percent", then: (value) => value.lessThan(100) }),
    price: number().required().min(0),
  }),
);

function initialValues(): VariantPricingPayload {
  const eligibleOffers = props.variant.pricing.offers.filter((offer) => offer.eligible);
  return {
    pricing_catalog_id: props.variant.pricing.catalog_id ?? (eligibleOffers.length === 1 ? eligibleOffers[0].id : null),
    reference_id: props.variant.pricing.reference_id,
    margin_type: props.variant.margin_type,
    margin_value: props.variant.margin_value,
    price: props.variant.price,
  };
}

const { handleSubmit, errors, defineField, setErrors, resetForm, meta, submitCount, isSubmitting } = useForm<VariantPricingPayload>({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: initialValues(),
});
const [catalogId] = defineField("pricing_catalog_id");
const [marginType] = defineField("margin_type");
const [marginValue] = defineField("margin_value");
const [price] = defineField("price");
const isDirty = computed(() => meta.value.dirty || replicatePricing.value);
watch(isDirty, (value) => emit("update:dirty", value), { immediate: true });
watch(
  () => props.variant,
  (variant, previous) => {
    if (variant.id === previous?.id && isDirty.value) return;
    resetForm({ values: initialValues() });
    replicatePricing.value = false;
  },
);

const onSubmit = handleSubmit((values) => {
  if (isSaving.value) return;
  isSaving.value = true;
  const replicated = replicatePricing.value;
  const payload: VariantPricingUpdatePayload = { ...values, section: "pricing", replicate_pricing: replicated };
  router.put(
    route("inventory.variant.update", props.variant.id),
    { ...payload },
    {
      preserveScroll: true,
      onSuccess: () => {
        resetForm({ values: initialValues() });
        replicatePricing.value = false;
        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: replicated ? t("Variant configuration applied to all variants") : t("Variant updated successfully"),
          life: 3000,
        });
      },
      onError: (serverErrors) => {
        setErrors(serverErrors);
        toast.add({ severity: "error", summary: t("Error"), detail: t("Please review the errors in the form"), life: 3000 });
        nextTick(() => formElement.value?.querySelector<HTMLInputElement>(".p-invalid")?.focus());
      },
      onFinish: () => {
        isSaving.value = false;
      },
    },
  );
});
</script>

<template>
  <form ref="pricingForm" class="flex flex-col gap-5" @submit.prevent="onSubmit">
    <h2 class="m-0 text-lg font-semibold">{{ t("Pricing") }}</h2>
    <ReplacementPricing
      v-model:catalog-id="catalogId"
      v-model:margin-type="marginType"
      v-model:margin-value="marginValue"
      v-model:price="price"
      :variant="variant"
      :invalid-target="submitCount > 0 && !!errors.margin_value"
      :invalid-price="submitCount > 0 && !!errors.price"
      :invalid-supplier="submitCount > 0 && !!errors.pricing_catalog_id"
      :form-dirty="isDirty"
      :can-edit="canEdit && !isSaving"
    >
      <template v-if="siblingCount > 0 && canEdit" #before-history>
        <div class="flex flex-wrap items-center gap-3 pt-4">
          <div class="flex items-center gap-3">
            <ToggleSwitch input-id="replicate-pricing" v-model="replicatePricing" :disabled="isSaving" />
            <label for="replicate-pricing" class="font-medium">{{ t("Apply to all variants") }}</label>
          </div>
          <Message v-if="replicatePricing" severity="warn" icon="fa fa-triangle-exclamation" :closable="false" class="m-0 flex-1">
            {{ t("Copies pricing to {count} variants.", { count: siblingCount }) }}
          </Message>
        </div>
      </template>
    </ReplacementPricing>
    <small
      v-if="submitCount > 0 && (errors.pricing_catalog_id || errors.margin_type || errors.margin_value || errors.price)"
      class="text-red-500 dark:text-red-300"
    >
      {{ errors.pricing_catalog_id ?? errors.margin_type ?? errors.margin_value ?? errors.price }}
    </small>
    <div v-if="canEdit" class="flex justify-end border-t border-surface-200 pt-4 dark:border-surface-700">
      <Button
        type="submit"
        :label="t('Save changes')"
        icon="fa fa-save"
        raised
        class="uppercase"
        :loading="isSaving || isSubmitting"
        :disabled="!isDirty || isSaving"
      />
    </div>
  </form>
</template>
