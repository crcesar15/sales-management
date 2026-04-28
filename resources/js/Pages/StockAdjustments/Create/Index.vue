<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { Button, Card, Select, InputNumber, Textarea, AutoComplete, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string } from "yup";
import { route } from "ziggy-js";
import { ref, computed } from "vue";
import axios from "axios";
import AppLayout from "@layouts/admin.vue";
import type { ProductResponse } from "@app-types/product-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  stores: Array<{ id: number; name: string; code: string }>;
  reasons: Array<{ value: string; label: string }>;
}>();

const toast = useToast();
const { t } = useI18n();

const storeOptions = computed(() => props.stores.map((s) => ({ name: s.name, value: s.id })));
const reasonOptions = computed(() => props.reasons.map((r) => ({ name: t(r.label), value: r.value })));

const schema = toTypedSchema(
  object({
    product_variant_id: number().required(t("Product variant is required")).typeError(t("Product variant is required")),
    store_id: number().required(t("Store is required")).typeError(t("Store is required")),
    batch_id: number().nullable().optional(),
    quantity_change: number()
      .required()
      .typeError(t("Quantity Change is required"))
      .notOneOf([0], t("Quantity change cannot be zero")),
    reason: string().required(t("Reason is required")),
    notes: string().nullable().optional().max(1000),
  }),
);

const { handleSubmit, errors, setFieldValue, setErrors, values } = useForm({
  validationSchema: schema,
  initialValues: {
    product_variant_id: undefined as unknown as number,
    store_id: undefined as unknown as number,
    batch_id: null as number | null,
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
}

const variantSearchResults = ref<VariantOption[]>([]);
const variantSearchLoading = ref(false);
const selectedVariant = ref<VariantOption | null>(null);

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
    variantSearchResults.value = (response.data.data || []).map((v: Record<string, unknown>) => ({
      id: v.id as number,
      name: v.name as string,
      product: v.product as ProductResponse,
      identifier: v.identifier as string,
    }));
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

const submit = handleSubmit((formValues) => {
  router.post(route("stock-adjustments.store"), formValues, {
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
      <div class="flex">
        <Button
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2"
          @click="router.visit(route('stock-adjustments'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ t("Create Adjustment") }}
        </h4>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" class="uppercase" raised @click="submit()" />
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
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
                    :class="{ 'p-invalid': errors.store_id }"
                    @update:model-value="setFieldValue('store_id', $event)"
                  />
                  <small v-if="errors.store_id" class="text-red-400 dark:text-red-300">{{ errors.store_id }}</small>
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
                    :class="{ 'p-invalid': errors.reason }"
                    @update:model-value="setFieldValue('reason', $event)"
                  />
                  <small v-if="errors.reason" class="text-red-400 dark:text-red-300">{{ errors.reason }}</small>
                </div>
              </div>

              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label>{{ t("Product") }}</label>
                  <AutoComplete
                    v-model="selectedVariant"
                    :suggestions="variantSearchResults"
                    :loading="variantSearchLoading"
                    option-label="identifier"
                    :placeholder="t('Type to search products...')"
                    class="w-full"
                    input-class="w-full"
                    @complete="searchVariants"
                    @item-select="selectVariant"
                  >
                    <template #option="{ option }">
                      <div>
                        <span class="font-medium">{{ option.product?.name }}</span>
                        <span class="text-surface-500 ml-2">{{ option.name || option.identifier }}</span>
                      </div>
                    </template>
                  </AutoComplete>
                  <small v-if="errors.product_variant_id" class="text-red-400 dark:text-red-300">
                    {{ errors.product_variant_id }}
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
                    :class="{ 'p-invalid': errors.quantity_change }"
                    @update:model-value="setFieldValue('quantity_change', $event)"
                  />
                  <small class="text-surface-500">{{ t("Use negative for deductions, positive for additions") }}</small>
                  <small v-if="errors.quantity_change" class="text-red-400 dark:text-red-300">
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
    </div>
  </div>
</template>
