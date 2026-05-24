<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import {
  Button,
  Card,
  Select,
  InputNumber,
  InputText,
  Textarea,
  AutoComplete,
  DatePicker,
  Tag,
  useToast,
} from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string } from "yup";
import { route } from "ziggy-js";
import { ref, computed, watch } from "vue";
import axios from "axios";
import AppLayout from "@layouts/admin.vue";
import { useBatchClient } from "@/Composables/useBatchClient";
import type { ProductResponse } from "@app-types/product-types";
import type { AvailableBatch } from "@/Types/stock-adjustment-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  stores: Array<{ id: number; name: string; code: string }>;
  reasons: Array<{ value: string; label: string }>;
}>();

const toast = useToast();
const { t } = useI18n();
const { fetchAvailableBatchesApi } = useBatchClient();

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
    quantity_change: number()
      .required()
      .typeError(t("Quantity Change is required"))
      .notOneOf([0], t("Quantity change cannot be zero")),
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

interface VariantOption {
  id: number;
  name: string;
  product: ProductResponse;
  identifier: string;
  stock: number;
  label: string;
}

const variantSearchResults = ref<VariantOption[]>([]);
const variantSearchLoading = ref(false);
const selectedVariant = ref<VariantOption | null>(null);

const availableBatches = ref<AvailableBatch[]>([]);
const batchesLoading = ref(false);
const selectedBatchId = ref<number | null>(null);

const isInitialStock = computed(() => values.reason === "initial_stock");
const isNewBatch = computed(() => isInitialStock.value || selectedBatchId.value === NEW_BATCH_VALUE);
const showBatchSelector = computed(
  () => !isInitialStock.value && !!values.store_id && !!values.product_variant_id,
);
const showNewBatchFields = computed(() => isNewBatch.value);

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
      const id = v.id as number;
      const name = v.name as string;
      const product = v.product as ProductResponse;
      const identifier = v.identifier as string;
      const stock = (v.stock as number) ?? 0;
      const label = name ? `${product?.name ?? ""} - ${name}` : `${product?.name ?? ""}`;
      return { id, name, product, identifier, stock, label };
    });
  } catch {
    variantSearchResults.value = [];
  } finally {
    variantSearchLoading.value = false;
  }
}

function selectVariant() {
  if (!selectedVariant.value) return;
  setFieldValue("product_variant_id", selectedVariant.value.id);
}

async function fetchAvailableBatches() {
  if (!values.store_id || !values.product_variant_id) {
    availableBatches.value = [];
    return;
  }
  batchesLoading.value = true;
  try {
    const response = await fetchAvailableBatchesApi(values.product_variant_id, values.store_id);
    availableBatches.value = response.data?.data || [];
  } catch {
    availableBatches.value = [];
  } finally {
    batchesLoading.value = false;
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
    const label = b.batch_identifier
      ? `${b.batch_identifier} (${t("Remaining")}: ${b.remaining_quantity})`
      : `#${b.id} — ${t("Remaining")}: ${b.remaining_quantity}`;
    return { name: label, value: b.id };
  });
  if (values.quantity_change && values.quantity_change > 0) {
    options.push({ name: t("Create new batch"), value: NEW_BATCH_VALUE });
  }
  return options;
});

const selectedStoreName = computed(
  () => props.stores.find((s) => s.id === values.store_id)?.name ?? null,
);

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
                  <label>{{ t("Product") }}</label>
                  <AutoComplete
                    v-model="selectedVariant"
                    :suggestions="variantSearchResults"
                    :loading="variantSearchLoading"
                    option-label="label"
                    :placeholder="t('Type to search products...')"
                    :empty-search-message="t('No results found')"
                    dropdown
                    force-selection
                    class="w-full"
                    input-class="w-full"
                    @complete="searchVariants"
                    @item-select="selectVariant"
                  >
                    <template #header>
                      <div
                        class="hidden lg:grid grid-cols-12 gap-2 px-3 py-2 text-sm font-semibold text-surface-500 uppercase tracking-wide border-b border-surface-200 dark:border-surface-700"
                      >
                        <span class="col-span-5">{{ t("Product") }}</span>
                        <span class="col-span-3">{{ t("Unit") }}</span>
                        <span class="col-span-2">{{ t("Stock") }}</span>
                        <span class="col-span-2">{{ t("Identifier") }}</span>
                      </div>
                    </template>
                    <template #option="{ option }">
                      <!-- Desktop: grid row -->
                      <div class="hidden lg:grid grid-cols-12 gap-2 items-center w-full py-1">
                        <div class="col-span-5 flex flex-col gap-0.5 min-w-0">
                          <span class="font-medium text-sm truncate">{{ option.product?.name }}</span>
                          <span v-if="option.name" class="text-sm text-surface-500 truncate">{{ option.name }}</span>
                        </div>
                        <div class="col-span-3">
                          <span v-if="option.product?.measurement_unit" class="text-sm">
                            {{ option.product.measurement_unit.name }}
                          </span>
                        </div>
                        <div class="col-span-2">
                          <Tag
                            :value="option.stock === 0 ? t('Out of stock') : `${t('In stock')}: ${option.stock}`"
                            :severity="option.stock === 0 ? 'danger' : 'success'"
                            class="text-sm"
                            rounded
                          />
                        </div>
                        <div class="col-span-2 text-sm text-surface-500 truncate">
                          {{ option.identifier }}
                        </div>
                      </div>
                      <!-- Mobile: card layout -->
                      <div class="lg:hidden flex flex-col gap-1.5 py-2 w-full">
                        <div class="flex items-center justify-between">
                          <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                            <span class="font-medium text-sm truncate">{{ option.product?.name }}</span>
                            <span class="text-xs text-surface-500 truncate">
                              <span v-if="option.name">{{ option.name }} </span>
                              <span v-if="option.product?.measurement_unit" class="ml-1">
                                ({{ option.product.measurement_unit.name }})
                              </span>
                            </span>
                          </div>
                          <Tag
                            :value="option.stock === 0 ? t('Out of stock') : `${t('In stock')}: ${option.stock}`"
                            :severity="option.stock === 0 ? 'danger' : 'success'"
                            class="text-xs"
                            rounded
                          />
                        </div>
                      </div>
                    </template>
                  </AutoComplete>
                  <small v-if="submitCount > 0 && errors.product_variant_id" class="text-red-400 dark:text-red-300">
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
                  <label for="expiry_date">{{ t("Expiry Date") }}</label>
                  <DatePicker
                    id="expiry_date"
                    :model-value="values.expiry_date ? new Date(values.expiry_date) : null"
                    date-format="yy-mm-dd"
                    show-icon
                    :placeholder="t('Select expiry date (optional)')"
                    @update:model-value="setFieldValue('expiry_date', $event ? $event.toISOString().split('T')[0] : null)"
                  />
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
                <span class="text-sm text-surface-500 block">{{ t("Product") }}</span>
                <span v-if="selectedVariant" class="font-medium">
                  {{ selectedVariant.product?.name }}
                  <span class="text-surface-500 ml-1">{{ selectedVariant.name || selectedVariant.identifier }}</span>
                </span>
                <span v-else class="text-surface-400">---</span>
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
                  :class="values.quantity_change != null && values.quantity_change < 0 ? 'text-red-500 font-bold' : 'text-green-600 font-bold'"
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