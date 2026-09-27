<script setup lang="ts">
import { Card, InputNumber, Textarea, Select, ToggleSwitch, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string, number } from "yup";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import CatalogProductPicker from "./CatalogProductPicker.vue";
import type { CatalogPayload, CatalogProductUnitOption, CatalogResponse } from "@/Types/catalog-types";
import type { VendorResponse } from "@/Types/vendor-types";

const props = defineProps<{
  vendor: VendorResponse;
  initialValues?: Partial<CatalogResponse>;
  isEditing?: boolean;
  existingCatalogKeys?: string[];
}>();

const emit = defineEmits<{
  (e: "submit", payload: CatalogPayload): void;
}>();

const toast = useToast();
const { t } = useI18n();

const BASE_UNIT_ID = 0;

const statusOptions = [
  { name: t("Active"), value: "active" },
  { name: t("Inactive"), value: "inactive" },
];

const paymentTermOptions = [
  { name: t("Cash"), value: "debit" },
  { name: t("Credit"), value: "credit" },
  { name: t("Both"), value: "both" },
];

const schema = toTypedSchema(
  object({
    product_variant_id: number().required(t("Product is required")),
    unit_id: number().required(t("Purchase unit is required")),
    price: number().required(t("Price is required")).min(0, t("Price must be at least 0")),
    payment_terms: string().nullable().optional(),
    details: string().nullable().optional().max(300, t("Details must not exceed 300 characters")),
    status: string().required(t("Status is required")).oneOf(["active", "inactive"]),
    minimum_order_quantity: number().nullable().optional().min(0, t("Must be at least 0")),
    lead_time_days: number().nullable().optional().min(0, t("Must be at least 0")),
  }),
);

const { handleSubmit, errors, defineField, setFieldValue, setErrors, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    product_variant_id: props.initialValues?.product_variant_id ?? undefined,
    unit_id: props.initialValues?.unit_id ?? BASE_UNIT_ID,
    price: props.initialValues?.price ?? 0,
    payment_terms: props.initialValues?.payment_terms ?? null,
    details: props.initialValues?.details ?? "",
    status: props.initialValues?.status ?? "active",
    minimum_order_quantity: props.initialValues?.minimum_order_quantity ?? null,
    lead_time_days: props.initialValues?.lead_time_days ?? null,
  },
});

const [price, priceAttrs] = defineField("price");
const [paymentTerms, paymentTermsAttrs] = defineField("payment_terms");
const [details, detailsAttrs] = defineField("details");
const [status, statusAttrs] = defineField("status");
const [minimumOrderQuantity, minimumOrderQuantityAttrs] = defineField("minimum_order_quantity");
const [leadTimeDays, leadTimeDaysAttrs] = defineField("lead_time_days");

function initialProductUnitOption(): CatalogProductUnitOption | null {
  const variant = props.initialValues?.product_variant;
  if (!variant?.product) return null;

  const purchaseUnit = props.initialValues?.purchase_unit;
  const baseUnitName = variant.product.measurement_unit?.name ?? null;
  const variantIdentity = variant.values?.length
    ? variant.values.map((value) => `${value.option_name}: ${value.value}`).join(", ")
    : variant.identifier || variant.name;

  return {
    key: `${variant.id}:${purchaseUnit?.id ?? "base"}`,
    product_variant_id: variant.id,
    unit_id: purchaseUnit?.id ?? null,
    product_name: variant.product.name,
    brand_name: variant.product.brand?.name ?? null,
    base_unit_name: baseUnitName,
    variant_identity: variantIdentity,
    unit_name: purchaseUnit?.name ?? baseUnitName ?? t("Base unit"),
    conversion_factor: purchaseUnit?.conversion_factor ?? 1,
  };
}

const selectedProductUnit = ref<CatalogProductUnitOption | null>(initialProductUnitOption());
const addedKeys = computed(() => new Set(props.existingCatalogKeys ?? []));

function onProductUnitSelect(selection: CatalogProductUnitOption | null) {
  selectedProductUnit.value = selection;
  setFieldValue("product_variant_id", selection?.product_variant_id);
  setFieldValue("unit_id", selection?.unit_id ?? BASE_UNIT_ID);
}

const conversionFactorLabel = computed(() => {
  const selection = selectedProductUnit.value;
  if (!selection) return "";
  if (selection.unit_id === null) return `1 ${selection.unit_name} (${t("base unit")})`;

  return `1 ${selection.unit_name} = ${selection.conversion_factor} ${selection.base_unit_name ?? t("unit")}`;
});

// Advanced terms toggle
const showAdvancedTerms = ref(!!props.initialValues?.minimum_order_quantity || !!props.initialValues?.lead_time_days);

const submit = handleSubmit((formValues) => {
  const payload: CatalogPayload = {
    vendor_id: props.vendor.id,
    product_variant_id: formValues.product_variant_id as number,
    unit_id: formValues.unit_id === BASE_UNIT_ID ? null : (formValues.unit_id ?? null),
    price: formValues.price as number,
    payment_terms: formValues.payment_terms || null,
    details: formValues.details || null,
    status: formValues.status as "active" | "inactive",
    minimum_order_quantity: formValues.minimum_order_quantity ?? null,
    lead_time_days: formValues.lead_time_days ?? null,
  };
  emit("submit", payload);
});

function handleError(errs: Record<string, string>) {
  setErrors(errs);
  toast.add({
    severity: "error",
    summary: t("Error"),
    detail: t("Please review the errors in the form"),
    life: 3000,
  });
}

defineExpose({
  submit,
  handleError,
});
</script>

<template>
  <form @submit.prevent="submit">
    <div class="grid grid-cols-12 gap-4">
      <!-- Left column -->
      <div class="lg:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Product & Pricing") }}</template>
          <template #content>
            <!-- Product and purchase unit -->
            <div class="flex flex-col gap-1 mb-4">
              <label for="catalog-product-search">
                {{ t("Product") }}
                <span class="text-red-500">*</span>
              </label>
              <CatalogProductPicker
                :model-value="selectedProductUnit"
                :added-keys="addedKeys"
                :disabled="isEditing"
                :invalid="submitCount > 0 && !!errors.product_variant_id"
                @update:model-value="onProductUnitSelect"
              />
              <small v-if="conversionFactorLabel" class="text-surface-500">{{ conversionFactorLabel }}</small>
              <small v-if="submitCount > 0 && errors.product_variant_id" class="text-red-400 dark:text-red-300">
                {{ errors.product_variant_id }}
              </small>
            </div>

            <!-- Price -->
            <div class="flex flex-col gap-1 mb-4">
              <label for="price">
                {{ t("Price") }}
                <span class="text-red-500">*</span>
              </label>
              <InputNumber
                id="price"
                v-model="price"
                v-bind="priceAttrs"
                mode="currency"
                currency="BOB"
                :min="0"
                :class="{ 'p-invalid': submitCount > 0 && !!errors.price }"
              />
              <small v-if="submitCount > 0 && errors.price" class="text-red-400 dark:text-red-300">{{ errors.price }}</small>
            </div>

            <!-- Payment Terms -->
            <div class="flex flex-col gap-1 mb-4">
              <label for="payment-terms">{{ t("Payment Terms") }}</label>
              <Select
                id="payment-terms"
                v-model="paymentTerms"
                v-bind="paymentTermsAttrs"
                :options="paymentTermOptions"
                option-label="name"
                option-value="value"
                :placeholder="t('Select payment term')"
                show-clear
              />
            </div>

            <!-- Details -->
            <div class="flex flex-col gap-1">
              <label for="details">{{ t("Details") }}</label>
              <Textarea
                id="details"
                v-model="details"
                v-bind="detailsAttrs"
                rows="3"
                :class="{ 'p-invalid': submitCount > 0 && !!errors.details }"
              />
              <small v-if="submitCount > 0 && errors.details" class="text-red-400 dark:text-red-300">{{ errors.details }}</small>
            </div>
          </template>
        </Card>

        <!-- Advanced Terms -->
        <Card class="mb-4">
          <template #title>
            <div class="flex items-center gap-2">
              <span>{{ t("Advanced Purchasing Terms") }}</span>
              <ToggleSwitch v-model="showAdvancedTerms" />
            </div>
          </template>
          <template #content>
            <div v-if="showAdvancedTerms" class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-1">
                  <label for="moq">{{ t("Minimum Order Quantity") }}</label>
                  <InputNumber
                    id="moq"
                    v-model="minimumOrderQuantity"
                    v-bind="minimumOrderQuantityAttrs"
                    :min="0"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.minimum_order_quantity }"
                  />
                  <small v-if="submitCount > 0 && errors.minimum_order_quantity" class="text-red-400 dark:text-red-300">
                    {{ errors.minimum_order_quantity }}
                  </small>
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-1">
                  <label for="lead-time">{{ t("Lead Time (Days)") }}</label>
                  <InputNumber
                    id="lead-time"
                    v-model="leadTimeDays"
                    v-bind="leadTimeDaysAttrs"
                    :min="0"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.lead_time_days }"
                  />
                  <small v-if="submitCount > 0 && errors.lead_time_days" class="text-red-400 dark:text-red-300">
                    {{ errors.lead_time_days }}
                  </small>
                </div>
              </div>
            </div>
            <div v-else class="text-surface-400 text-center py-4">
              {{ t("Toggle to configure minimum order quantity and lead time") }}
            </div>
          </template>
        </Card>
      </div>

      <!-- Right column -->
      <div class="lg:col-span-4 col-span-12">
        <Card>
          <template #title>{{ t("Configuration") }}</template>
          <template #content>
            <div class="flex flex-col gap-1">
              <label for="status">
                {{ t("Status") }}
                <span class="text-red-500">*</span>
              </label>
              <Select
                id="status"
                v-model="status"
                v-bind="statusAttrs"
                :options="statusOptions"
                option-label="name"
                option-value="value"
                :class="{ 'p-invalid': submitCount > 0 && !!errors.status }"
              />
              <small v-if="submitCount > 0 && errors.status" class="text-red-400 dark:text-red-300">{{ errors.status }}</small>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </form>
</template>
