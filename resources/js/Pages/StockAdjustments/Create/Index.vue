<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { Button, Card, Select, InputNumber, InputText, Textarea, DatePicker, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string } from "yup";
import { route } from "ziggy-js";
import { ref, computed, watch } from "vue";
import AppLayout from "@layouts/admin.vue";
import { useBatchClient } from "@/Composables/useBatchClient";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import StockAdjustmentProductPicker from "./Components/StockAdjustmentProductPicker.vue";
import type { AvailableBatch, StockAdjustmentVariantSearchResult } from "@/Types/stock-adjustment-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  stores: Array<{ id: number; name: string; code: string }>;
  reasons: Array<{ value: string; label: string }>;
}>();

const toast = useToast();
const { t } = useI18n();
const { fetchAvailableBatchesApi } = useBatchClient();
const { parseDateOnly, toDateOnly, datePickerFormat } = useDatetimeFormatter();

const storeOptions = computed(() => props.stores.map((s) => ({ name: s.name, value: s.id })));
const reasonOptions = computed(() => props.reasons.map((r) => ({ name: t(r.label), value: r.value })));

const NEW_BATCH_VALUE = 0;

const schema = toTypedSchema(
  object({
    product_variant_id: number().required(t("Product variant is required")).typeError(t("Product variant is required")),
    store_id: number().required(t("Store is required")).typeError(t("Store is required")),
    batch_id: number()
      .nullable()
      .when("reason", {
        is: (reason: string) => reason !== "initial_stock",
        then: (schema) => schema.required(t("Batch is required")),
        otherwise: (schema) => schema.optional().nullable(),
      }),
    expiry_date: string().nullable().optional(),
    batch_identifier: string().nullable().optional().max(255),
    quantity_change: number().required().typeError(t("Quantity Change is required")).notOneOf([0], t("Quantity change cannot be zero")),
    reason: string().required(t("Reason is required")),
    notes: string().nullable().optional().max(1000),
  }),
);

const { handleSubmit, errors, setFieldValue, setErrors, values, submitCount } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    product_variant_id: undefined as unknown as number,
    store_id: undefined as unknown as number,
    batch_id: null as number | null,
    expiry_date: null as string | null,
    batch_identifier: null as string | null,
    quantity_change: undefined as unknown as number,
    reason: "",
    notes: "",
  },
});

const selectedVariant = ref<StockAdjustmentVariantSearchResult | null>(null);

const availableBatches = ref<AvailableBatch[]>([]);
const batchesLoading = ref(false);
const selectedBatchId = ref<number | null>(null);
let currentBatchRequestId = 0;

const isInitialStock = computed(() => values.reason === "initial_stock");
const isNewBatch = computed(() => isInitialStock.value || selectedBatchId.value === NEW_BATCH_VALUE);
const showBatchSelector = computed(() => !isInitialStock.value && !!values.store_id && !!values.product_variant_id);
const showNewBatchFields = computed(() => isNewBatch.value);
const requiresExpiration = computed(() => selectedVariant.value?.has_expiration === true);

function handleVariantSelection(variant: StockAdjustmentVariantSearchResult | null) {
  selectedVariant.value = variant;
  setFieldValue("product_variant_id", variant?.id ?? (undefined as unknown as number));
}

async function fetchAvailableBatches() {
  const storeId = values.store_id;
  const variantId = values.product_variant_id;
  if (!storeId || !variantId) {
    availableBatches.value = [];
    return;
  }

  batchesLoading.value = true;
  const requestId = ++currentBatchRequestId;
  try {
    const response = await fetchAvailableBatchesApi(variantId, storeId);
    if (requestId !== currentBatchRequestId || values.store_id !== storeId || values.product_variant_id !== variantId) return;

    availableBatches.value = response.data?.data || [];
  } catch {
    if (requestId !== currentBatchRequestId) return;

    availableBatches.value = [];
  } finally {
    if (requestId === currentBatchRequestId) batchesLoading.value = false;
  }
}

function handleBatchSelect(id: number | null) {
  selectedBatchId.value = id;
  if (id === NEW_BATCH_VALUE) {
    setFieldValue("batch_id", null);
  } else {
    setFieldValue("batch_id", id);
    setFieldValue("expiry_date", null);
    setFieldValue("batch_identifier", null);
  }
}

function resetBatchSelection() {
  currentBatchRequestId++;
  batchesLoading.value = false;
  selectedBatchId.value = null;
  setFieldValue("batch_id", null);
  setFieldValue("expiry_date", null);
  setFieldValue("batch_identifier", null);
}

watch(
  () => [values.store_id, values.product_variant_id],
  () => {
    resetBatchSelection();
    if (values.store_id && values.product_variant_id && !isInitialStock.value) {
      fetchAvailableBatches();
    } else {
      availableBatches.value = [];
    }
  },
);

watch(
  () => values.store_id,
  (storeId, previousStoreId) => {
    if (storeId === previousStoreId) return;

    handleVariantSelection(null);
  },
);

watch(
  () => values.reason,
  (newReason) => {
    if (newReason === "initial_stock") {
      resetBatchSelection();
      availableBatches.value = [];
    } else if (values.store_id && values.product_variant_id) {
      resetBatchSelection();
      fetchAvailableBatches();
    }
  },
);

const batchSelectOptions = computed(() => {
  const options = availableBatches.value.map((b) => {
    const parts = [`#${b.id}`];
    if (b.batch_identifier) parts.push(b.batch_identifier);
    parts.push(`${t("Remaining")}: ${b.remaining_quantity}`);
    if (b.expiry_date) parts.push(`${t("Expires")}: ${b.expiry_date}`);
    const label = parts.join(" — ");
    return { name: label, value: b.id };
  });
  if (values.quantity_change && values.quantity_change > 0) {
    options.push({ name: t("Create new batch"), value: NEW_BATCH_VALUE });
  }
  return options;
});

const selectedStoreName = computed(() => props.stores.find((s) => s.id === values.store_id)?.name ?? null);

const selectedBatchLabel = computed(() => {
  if (isNewBatch.value) return t("New batch");
  const batch = availableBatches.value.find((b) => b.id === values.batch_id);
  if (!batch) return null;
  return batch.batch_identifier ? `${batch.batch_identifier}` : `#${batch.id}`;
});

const formattedQuantity = computed(() => {
  const val = values.quantity_change;
  if (!val) return null;
  return val > 0 ? `+${val}` : `${val}`;
});

const submit = handleSubmit((formValues) => {
  if (showNewBatchFields.value && requiresExpiration.value && !formValues.expiry_date) {
    setErrors({ expiry_date: t("Expiry date is required for this product") });
    return;
  }

  const payload = { ...formValues };
  if (isInitialStock.value) {
    payload.batch_id = null;
  } else if (selectedBatchId.value === NEW_BATCH_VALUE) {
    payload.batch_id = null;
  }
  router.post(route("stock-adjustments.store"), payload, {
    onSuccess: () => {
      router.visit(route("stock-adjustments"));
    },
    onError: (errs) => {
      setErrors(errs);
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t(Object.values(errs)[0] ?? "An unexpected error occurred."),
        life: 3000,
      });
    },
  });
});
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" @click="router.visit(route('stock-adjustments'))" />
        <h2 class="text-2xl font-bold m-0">{{ t("Create Adjustment") }}</h2>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" raised class="uppercase" @click="submit()" />
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="lg:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Adjustment Details") }}</template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="store">{{ t("Store") }}</label>
                  <Select
                    id="store"
                    :model-value="values.store_id"
                    :options="storeOptions"
                    option-label="name"
                    option-value="value"
                    :placeholder="t('Select store')"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.store_id }"
                    @update:model-value="setFieldValue('store_id', $event)"
                  />
                  <small v-if="submitCount > 0 && errors.store_id" class="text-red-400 dark:text-red-300">{{ errors.store_id }}</small>
                </div>
              </div>

              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="reason">{{ t("Reason") }}</label>
                  <Select
                    id="reason"
                    :model-value="values.reason"
                    :options="reasonOptions"
                    option-label="name"
                    option-value="value"
                    :placeholder="t('Select reason')"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.reason }"
                    @update:model-value="setFieldValue('reason', $event)"
                  />
                  <small v-if="submitCount > 0 && errors.reason" class="text-red-400 dark:text-red-300">{{ errors.reason }}</small>
                </div>
              </div>

              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label id="stock-adjustment-product-label" :for="selectedVariant ? undefined : 'stock-adjustment-product-search'">
                    {{ t("Product") }}
                  </label>
                  <StockAdjustmentProductPicker
                    :model-value="selectedVariant"
                    :store-id="values.store_id ?? null"
                    :invalid="submitCount > 0 && !!errors.product_variant_id"
                    labelledby="stock-adjustment-product-label"
                    error-message-id="stock-adjustment-product-error"
                    @update:model-value="handleVariantSelection"
                  />
                  <small id="stock-adjustment-product-error" v-if="submitCount > 0 && errors.product_variant_id" class="text-red-400 dark:text-red-300">
                    {{ errors.product_variant_id }}
                  </small>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="mb-4">
          <template #title>{{ t("Batch & Quantity") }}</template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div v-if="showBatchSelector" class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="batch">{{ t("Batch") }}</label>
                  <Select
                    id="batch"
                    :model-value="selectedBatchId"
                    :options="batchSelectOptions"
                    option-label="name"
                    option-value="value"
                    :placeholder="t('Select batch')"
                    :empty-message="t('No batches available for this product in this store')"
                    :loading="batchesLoading"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.batch_id }"
                    @update:model-value="handleBatchSelect"
                  />
                  <small
                    v-if="availableBatches.length === 0 && !batchesLoading && values.quantity_change != null && values.quantity_change < 0"
                    class="text-red-400 dark:text-red-300"
                  >
                    {{ t("No batches available for this product in this store") }}
                  </small>
                  <small v-if="submitCount > 0 && errors.batch_id" class="text-red-400 dark:text-red-300">{{ errors.batch_id }}</small>
                </div>
              </div>

              <div v-if="!showBatchSelector && !isInitialStock" class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label>{{ t("Batch") }}</label>
                  <Select disabled :placeholder="t('Select store and product first')" />
                </div>
              </div>

              <div v-if="showNewBatchFields" class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="batch_identifier">{{ t("Batch Identifier") }}</label>
                  <InputText
                    id="batch_identifier"
                    :model-value="values.batch_identifier"
                    :placeholder="t('Enter batch identifier (optional)')"
                    @update:model-value="setFieldValue('batch_identifier', $event)"
                  />
                </div>
              </div>

              <div v-if="showNewBatchFields" class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="expiry_date">
                    {{ t("Expiry Date") }}
                    <span v-if="requiresExpiration" class="text-red-400">*</span>
                  </label>
                  <DatePicker
                    id="expiry_date"
                    :model-value="parseDateOnly(values.expiry_date ?? null)"
                    :date-format="datePickerFormat"
                    show-icon
                    :placeholder="requiresExpiration ? t('Select expiry date') : t('Select expiry date (optional)')"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.expiry_date }"
                    @update:model-value="
                      setFieldValue('expiry_date', $event instanceof Date ? toDateOnly($event) : null)
                    "
                  />
                  <small v-if="submitCount > 0 && errors.expiry_date" class="text-red-400 dark:text-red-300">
                    {{ errors.expiry_date }}
                  </small>
                </div>
              </div>

              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="quantity_change">{{ t("Quantity Change") }}</label>
                  <InputNumber
                    id="quantity_change"
                    :model-value="values.quantity_change"
                    :allow-negative="true"
                    placeholder="0"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.quantity_change }"
                    @update:model-value="setFieldValue('quantity_change', $event)"
                  />
                  <small class="text-surface-500">{{ t("Use negative for deductions, positive for additions") }}</small>
                  <small v-if="submitCount > 0 && errors.quantity_change" class="text-red-400 dark:text-red-300">
                    {{ errors.quantity_change }}
                  </small>
                </div>
              </div>

              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="notes">{{ t("Notes") }}</label>
                  <Textarea
                    id="notes"
                    :model-value="values.notes"
                    :auto-resize="true"
                    rows="3"
                    @update:model-value="setFieldValue('notes', $event)"
                  />
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="lg:col-span-4 col-span-12">
        <Card>
          <template #title>{{ t("Adjustment Summary") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Store") }}</span>
                <span class="font-medium">{{ selectedStoreName ?? "---" }}</span>
              </div>

              <div>
                <span class="text-sm text-surface-500 block">{{ t("Batch") }}</span>
                <span v-if="isInitialStock" class="font-medium text-primary">{{ t("New batch") }}</span>
                <span v-else-if="selectedBatchLabel" class="font-medium">{{ selectedBatchLabel }}</span>
                <span v-else class="text-surface-400">---</span>
              </div>

              <div>
                <span class="text-sm text-surface-500 block">{{ t("Quantity Change") }}</span>
                <span
                  v-if="formattedQuantity"
                  :class="
                    values.quantity_change != null && values.quantity_change < 0 ? 'text-red-500 font-bold' : 'text-green-600 font-bold'
                  "
                >
                  {{ formattedQuantity }}
                </span>
                <span v-else class="text-surface-400">---</span>
              </div>

              <div>
                <span class="text-sm text-surface-500 block">{{ t("Reason") }}</span>
                <span v-if="values.reason" class="font-medium">{{ t(values.reason) }}</span>
                <span v-else class="text-surface-400">---</span>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>
