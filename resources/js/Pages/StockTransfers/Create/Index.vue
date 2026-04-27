<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { Button, Card, Select, InputNumber, Textarea, AutoComplete, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string, array } from "yup";
import { route } from "ziggy-js";
import { ref, computed } from "vue";
import axios from "axios";
import AppLayout from "@layouts/admin.vue";
import type { ProductResponse } from "@app-types/product-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  stores: Array<{ id: number; name: string; code: string }>;
}>();

const toast = useToast();
const { t } = useI18n();

const storeOptions = computed(() => props.stores.map((s) => ({ name: s.name, value: s.id })));

const schema = toTypedSchema(
  object({
    from_store_id: number().required().typeError(t("Source store is required")),
    to_store_id: number()
      .required()
      .typeError(t("Destination store is required"))
      .test("different-stores", t("Source store and destination store must be different"), (value, ctx) => {
        return value !== ctx.parent.from_store_id;
      }),
    notes: string().nullable().optional().max(1000),
    items: array()
      .of(
        object({
          product_variant_id: number().required(),
          quantity_requested: number().required().min(1),
        }),
      )
      .min(1, t("At least one item is required")),
  }),
);

const { handleSubmit, errors, setFieldValue, setErrors, values } = useForm({
  validationSchema: schema,
  initialValues: {
    from_store_id: undefined as unknown as number,
    to_store_id: undefined as unknown as number,
    notes: "",
    items: [] as Array<{ product_variant_id: number; quantity_requested: number }>,
  },
});

interface VariantOption {
  id: number;
  name: string;
  product: ProductResponse;
  identifier: string;
}

interface VariantOptionValue {
  id: number;
  value: string;
  option_name: string;
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
      values: v.values as VariantOptionValue[],
    }));
  } catch {
    variantSearchResults.value = [];
  } finally {
    variantSearchLoading.value = false;
  }
}

function addItem() {
  if (!selectedVariant.value) return;
  const variant = selectedVariant.value;
  const existing = (values.items ?? []).find((i) => i.product_variant_id === variant.id);
  if (existing) {
    toast.add({ severity: "warn", summary: t("Duplicate"), detail: t("Product already added"), life: 3000 });
    selectedVariant.value = null;
    return;
  }
  const items = [...(values.items ?? []), { product_variant_id: variant.id, quantity_requested: 1 }];
  setFieldValue("items", items);
  selectedVariant.value = null;
}

function removeItem(index: number) {
  const items = [...(values.items ?? [])];
  items.splice(index, 1);
  setFieldValue("items", items);
}

function updateQuantity(index: number, qty: number) {
  const items = [...(values.items ?? [])];
  items[index] = { ...items[index], quantity_requested: qty };
  setFieldValue("items", items);
}

function getVariantLabel(variantId: number): string {
  const found = variantSearchResults.value.find((v) => v.id === variantId);
  return found ? `${found.product?.name} (${found.name || found.identifier})` : `#${variantId}`;
}

const submit = handleSubmit((formValues) => {
  router.post(route("stock-transfers.store"), formValues, {
    onSuccess: () => {
      router.visit(route("stock-transfers"));
    },
    onError: (errs) => {
      setErrors(errs);
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t(Object.values(errs)[0] ?? "An error occurred"),
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
          @click="router.visit(route('stock-transfers'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ t("Create Transfer") }}
        </h4>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" class="uppercase" raised @click="submit()" />
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="md:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Transfer Details") }}</template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="from-store">{{ t("From Store") }}</label>
                  <Select
                    id="from-store"
                    :model-value="values.from_store_id"
                    :options="storeOptions"
                    option-label="name"
                    option-value="value"
                    :placeholder="t('Select source store')"
                    :class="{ 'p-invalid': errors.from_store_id }"
                    @update:model-value="setFieldValue('from_store_id', $event)"
                  />
                  <small v-if="errors.from_store_id" class="text-red-400 dark:text-red-300">
                    {{ errors.from_store_id }}
                  </small>
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="to-store">{{ t("To Store") }}</label>
                  <Select
                    id="to-store"
                    :model-value="values.to_store_id"
                    :options="storeOptions"
                    option-label="name"
                    option-value="value"
                    :placeholder="t('Select destination store')"
                    :class="{ 'p-invalid': errors.to_store_id }"
                    @update:model-value="setFieldValue('to_store_id', $event)"
                  />
                  <small v-if="errors.to_store_id" class="text-red-400 dark:text-red-300">
                    {{ errors.to_store_id }}
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

        <Card>
          <template #title>{{ t("Transfer Items") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <div class="flex gap-2 items-end">
                <div class="flex-1">
                  <label class="text-sm font-medium mb-1 block">{{ t("Search Product") }}</label>
                  <AutoComplete
                    v-model="selectedVariant"
                    :suggestions="variantSearchResults"
                    :loading="variantSearchLoading"
                    option-label="identifier"
                    :placeholder="t('Type to search products...')"
                    class="w-full"
                    input-class="w-full"
                    @complete="searchVariants"
                  >
                    <template #option="{ option }">
                      <div>
                        <span class="font-medium">{{ option.product?.name }}</span>
                        <span class="text-surface-500 ml-2">{{ option.name || option.identifier }}</span>
                      </div>
                    </template>
                  </AutoComplete>
                </div>
                <Button :label="t('Add Item')" icon="fa fa-plus" :disabled="!selectedVariant" @click="addItem" />
              </div>

              <small v-if="errors.items" class="text-red-400 dark:text-red-300">{{ errors.items }}</small>

              <div v-if="(values.items ?? []).length > 0" class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="border-b border-surface-200 dark:border-surface-700">
                      <th class="text-left p-2">{{ t("Product") }}</th>
                      <th class="text-center p-2" style="width: 140px">{{ t("Quantity") }}</th>
                      <th style="width: 60px" />
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(item, index) in values.items"
                      :key="item.product_variant_id"
                      class="border-b border-surface-100 dark:border-surface-800"
                    >
                      <td class="p-2">{{ getVariantLabel(item.product_variant_id) }}</td>
                      <td class="p-2">
                        <InputNumber
                          :model-value="item.quantity_requested"
                          :min="1"
                          class="w-full"
                          :input-style="{ width: '100%' }"
                          @update:model-value="updateQuantity(index, $event)"
                        />
                      </td>
                      <td class="p-2 text-center">
                        <Button icon="fa fa-trash" text severity="danger" size="small" @click="removeItem(index)" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-else class="text-center py-6 text-surface-400">
                {{ t("No items added yet. Search and add products above.") }}
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>
