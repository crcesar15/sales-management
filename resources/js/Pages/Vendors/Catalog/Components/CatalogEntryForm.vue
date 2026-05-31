<script setup lang="ts">
import { Card, InputNumber, Textarea, Select, Button, AutoComplete, ToggleSwitch, useToast } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, string, number } from "yup";
import { route } from "ziggy-js";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";
import type { CatalogPayload, CatalogResponse } from "@/Types/catalog-types";
import type { VendorResponse } from "@/Types/vendor-types";
import type { PurchaseUnitResponse } from "@/Types/product-variant-types";
import type { ProductResponse } from "@app-types/product-types";

interface VariantOption {
  id: number;
  name: string;
  product: ProductResponse;
  identifier: string;
  variantLabel: string;
}

interface VariantOptionValue {
  id: number;
  value: string;
  option_name: string;
}

const props = defineProps<{
  vendor: VendorResponse;
  initialValues?: Partial<CatalogResponse>;
  isEditing?: boolean;
}>();

const emit = defineEmits<{
  (e: "submit", payload: CatalogPayload): void;
  (e: "cancel"): void;
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

const { handleSubmit, errors, defineField, setFieldValue, setErrors, values, submitCount } = useForm({
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

const [unitId, unitIdAttrs] = defineField("unit_id");
const [price, priceAttrs] = defineField("price");
const [paymentTerms, paymentTermsAttrs] = defineField("payment_terms");
const [details, detailsAttrs] = defineField("details");
const [status, statusAttrs] = defineField("status");
const [minimumOrderQuantity, minimumOrderQuantityAttrs] = defineField("minimum_order_quantity");
const [leadTimeDays, leadTimeDaysAttrs] = defineField("lead_time_days");

// Variant autocomplete
const variantSearchResults = ref<VariantOption[]>([]);
const variantSearchLoading = ref(false);
const selectedVariant = ref<VariantOption | null>(null);

// Pre-populate selected variant in edit mode
if (props.isEditing && props.initialValues?.product_variant_id) {
  const pv = props.initialValues.product_variant;
  if (pv) {
    const variantLabel = pv.values?.length > 0
      ? pv.values.map((v: { option_name: string; value: string }) => `${v.option_name}: ${v.value}`).join(", ")
      : pv.identifier || pv.name;
    const brand = pv.product?.brand?.name;
    const displayName = brand
      ? `${pv.product.name} — ${brand} (${variantLabel})`
      : `${pv.product.name} (${variantLabel})`;
    selectedVariant.value = {
      id: pv.id,
      name: displayName,
      identifier: pv.identifier,
      variantLabel,
      product: pv.product,
    };
  }
}

async function searchVariants(event: { query: string }) {
  if (!event.query || event.query.length < 2) {
    variantSearchResults.value = [];
    return;
  }
  variantSearchLoading.value = true;
  try {
    const response = await axios.get(route("api.v1.variants"), {
      params: { filter: event.query, per_page: 15 },
    });
    variantSearchResults.value = (response.data.data || []).map((v: Record<string, unknown>) => {
      const values = v.values as VariantOptionValue[];
      const variantLabel = values?.length > 0
        ? values.map((val: VariantOptionValue) => `${val.option_name}: ${val.value}`).join(", ")
        : (v.identifier as string) || (v.name as string);

      let item = {
        id: v.id as number,
        product: v.product as ProductResponse,
        values: values,
        identifier: v.identifier as string,
        variantLabel,
        name: "",
      };

      if (item.product?.name) {
        const brand = item.product.brand?.name;
        item.name = brand
          ? `${item.product.name} — ${brand} (${variantLabel})`
          : `${item.product.name} (${variantLabel})`;
      } else {
        item.name = variantLabel;
      }
      return item;
    });
  } catch {
    variantSearchLoading.value = false;
  } finally {
    variantSearchLoading.value = false;
  }
}

function onVariantSelect(event: { value: VariantOptionValue }) {
  setFieldValue("product_variant_id", event.value.id);
  setFieldValue("unit_id", BASE_UNIT_ID);
  purchaseUnits.value = [];
  selectedPurchaseUnit.value = null;
  loadPurchaseUnits(event.value.id);
}

// Purchase units
const purchaseUnits = ref<PurchaseUnitResponse[]>([]);
const selectedPurchaseUnit = ref<PurchaseUnitResponse | null>(null);
const unitsLoading = ref(false);

const purchaseUnitOptions = computed(() => {
  const baseUnit = selectedVariant.value?.product?.measurement_unit;
  const baseOption: PurchaseUnitResponse = {
    id: BASE_UNIT_ID,
    name: baseUnit ? `${baseUnit.name} (${t("base unit")})` : t("Base unit"),
    conversion_factor: 1,
  };
  return [baseOption, ...purchaseUnits.value];
});

// Pre-populate purchase unit in edit mode
if (props.isEditing && props.initialValues?.product_variant_id) {
  const pu = props.initialValues.purchase_unit;
  if (pu) {
    selectedPurchaseUnit.value = pu;
    purchaseUnits.value = [pu];
  } else {
    selectedPurchaseUnit.value = null;
  }
}

async function loadPurchaseUnits(variantId: number) {
  unitsLoading.value = true;
  try {
    const response = await axios.get(route("api.v1.variants.purchase-units", variantId));
    purchaseUnits.value = response.data.data || [];
  } catch {
    purchaseUnits.value = [];
  } finally {
    unitsLoading.value = false;
  }
}

function onUnitSelect(value: number) {
  if (value === BASE_UNIT_ID) {
    selectedPurchaseUnit.value = null;
  } else {
    const unit = purchaseUnits.value.find((u) => u.id === value);
    selectedPurchaseUnit.value = unit || null;
  }
}

const conversionFactorLabel = computed(() => {
  const baseUnit = selectedVariant.value?.product?.measurement_unit;
  const baseName = baseUnit?.name ?? t("unit");
  if (selectedPurchaseUnit.value) {
    return `1 ${selectedPurchaseUnit.value.name} = ${selectedPurchaseUnit.value.conversion_factor} ${baseName}`;
  }
  return `1 ${baseName} (${t("base unit")})`;
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

function handleCancel() {
  emit("cancel");
}

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
  handleError,
});
</script>

<template>
  <form @submit.prevent="submit">
    <div class="grid grid-cols-12 gap-4">
      <!-- Left column -->
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Product & Pricing") }}</template>
          <template #content>
            <!-- Variant -->
            <div class="flex flex-col gap-1 mb-4">
              <label for="variant">
                {{ t("Product") }}
                <span class="text-red-500">*</span>
              </label>
              <AutoComplete
                id="variant"
                v-model="selectedVariant"
                :suggestions="variantSearchResults"
                option-label="name"
                :placeholder="t('Type to search products...')"
                :loading="variantSearchLoading"
                :disabled="isEditing"
                :invalid="submitCount > 0 && !!errors.product_variant_id"
                dropdown
                force-selection
                @complete="searchVariants"
                @item-select="onVariantSelect"
              >
                <template #header>
                  <div
                    class="hidden lg:grid grid-cols-12 gap-2 px-3 py-2 text-sm font-semibold text-surface-500 uppercase tracking-wide border-b border-surface-200 dark:border-surface-700"
                  >
                    <span class="col-span-5">{{ t("Product") }}</span>
                    <span class="col-span-4">{{ t("Brand") }}</span>
                    <span class="col-span-3">{{ t("Unit") }}</span>
                  </div>
                </template>
                <template #option="{ option }">
                  <!-- Desktop: grid row -->
                  <div class="hidden lg:grid grid-cols-12 gap-2 items-center w-full py-1">
                    <div class="col-span-5 flex flex-col gap-0.5 min-w-0">
                      <span class="font-medium text-sm truncate">{{ option.product?.name ?? option.name }}</span>
                      <span class="text-sm text-surface-500 truncate">{{ option.variantLabel }}</span>
                    </div>
                    <div class="col-span-4 text-sm text-surface-500 truncate">
                      {{ option.product?.brand?.name ?? "—" }}
                    </div>
                    <div class="col-span-3">
                      <span v-if="option.product?.measurement_unit?.name">
                        {{ option.product.measurement_unit.name }}
                      </span>
                      <span v-else>—</span>
                    </div>
                  </div>
                  <!-- Mobile: card layout -->
                  <div class="lg:hidden flex flex-col gap-1.5 py-2 w-full">
                    <div class="flex items-center justify-between">
                      <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                        <span class="font-medium text-sm truncate">{{ option.product?.name ?? option.name }}</span>
                        <span class="text-xs text-surface-500 truncate">
                          <span v-if="option.product?.brand?.name">{{ option.product.brand.name }} · </span>
                          {{ option.variantLabel }}
                          <span v-if="option.product?.measurement_unit" class="ml-1">
                            ({{ option.product.measurement_unit.name }})
                          </span>
                        </span>
                      </div>
                    </div>
                  </div>
                </template>
              </AutoComplete>
              <small v-if="submitCount > 0 && errors.product_variant_id" class="text-red-400 dark:text-red-300">
                {{ errors.product_variant_id }}
              </small>
            </div>

            <!-- Purchase Unit -->
            <div class="flex flex-col gap-1 mb-4">
              <label for="purchase-unit">
                {{ t("Purchase Unit") }}
                <span class="text-red-500">*</span>
              </label>
              <Select
                id="purchase-unit"
                v-model="unitId"
                v-bind="unitIdAttrs"
                :options="purchaseUnitOptions"
                option-label="name"
                option-value="id"
                :placeholder="t('Select purchase unit')"
                :loading="unitsLoading"
                :disabled="!values.product_variant_id || isEditing"
                :class="{ 'p-invalid': submitCount > 0 && !!errors.unit_id }"
                @update:model-value="onUnitSelect"
              />
              <small class="text-surface-500">{{ conversionFactorLabel }}</small>
              <small v-if="submitCount > 0 && errors.unit_id" class="text-red-400 dark:text-red-300">
                {{ errors.unit_id }}
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
              <Textarea id="details" v-model="details" v-bind="detailsAttrs" rows="3" :class="{ 'p-invalid': submitCount > 0 && !!errors.details }" />
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
      <div class="md:col-span-4 col-span-12">
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

    <!-- Actions -->
    <div class="flex justify-end gap-2 mt-4">
      <Button :label="t('Cancel')" icon="fa fa-times" outlined @click="handleCancel" />
      <Button :label="t('Save')" icon="fa fa-save" raised class="uppercase" type="submit" />
    </div>
  </form>
</template>
