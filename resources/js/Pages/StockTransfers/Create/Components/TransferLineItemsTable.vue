<script setup lang="ts">
import { DataTable, Column, Button, InputNumber, AutoComplete, Tag, useToast, useConfirm, ConfirmDialog } from "primevue";
import { useI18n } from "vue-i18n";
import { ref, computed } from "vue";
import axios from "axios";
import { route } from "ziggy-js";
import type { ProductResponse } from "@app-types/product-types";

export interface TransferLineItem {
  id: string;
  product_variant_id: number;
  product_name: string;
  variant_label: string;
  brand_name: string;
  measurement_unit_name: string;
  quantity_requested: number;
}

const props = defineProps<{
  modelValue: TransferLineItem[];
}>();

const emit = defineEmits<{
  (e: "update:modelValue", items: TransferLineItem[]): void;
}>();

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

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
const autocompleteKey = ref(0);

const items = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

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

function onVariantSelect(event: { value: VariantOption }) {
  const variant = event.value;
  const exists = items.value.some((i) => i.product_variant_id === variant.id);
  if (exists) {
    toast.add({ severity: "warn", summary: t("Duplicate"), detail: t("Product already added"), life: 3000 });
    selectedVariant.value = null;
    return;
  }

  const productName = variant.product?.name ?? "—";
  const variantLabel = variant.name || variant.identifier || productName;
  const brandName = variant.product?.brand?.name ?? "—";
  const measurementUnitName = variant.product?.measurement_unit?.name ?? "—";

  const newItem: TransferLineItem = {
    id: crypto.randomUUID(),
    product_variant_id: variant.id,
    product_name: productName,
    variant_label: variantLabel,
    brand_name: brandName,
    measurement_unit_name: measurementUnitName,
    quantity_requested: 1,
  };

  emit("update:modelValue", [...items.value, newItem]);
  selectedVariant.value = null;
  autocompleteKey.value++;
}

function updateQuantity(index: number, quantity: number) {
  const updated = [...items.value];
  updated[index] = { ...updated[index], quantity_requested: quantity };
  emit("update:modelValue", updated);
}

function removeItem(index: number) {
  const updated = items.value.filter((_, i) => i !== index);
  emit("update:modelValue", updated);
}

function confirmRemoveItem(index: number) {
  confirm.require({
    message: t("Are you sure you want to remove this item?"),
    header: t("Confirm"),
    icon: "fa fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    acceptClass: "p-button-primary",
    accept: () => {
      removeItem(index);
    },
  });
}
</script>

<template>
  <div>
    <div class="flex flex-col gap-2 mb-3">
      <label for="product-search">{{ t("Search Product") }}</label>
      <AutoComplete
            id="product-search"
            :key="autocompleteKey"
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
            @item-select="onVariantSelect"
          >
            <template #header>
              <div
                class="hidden lg:grid grid-cols-12 gap-2 px-3 py-2 text-sm font-semibold text-surface-500 uppercase tracking-wide border-b border-surface-200 dark:border-surface-700"
              >
                <span class="col-span-4">{{ t("Product") }}</span>
                <span class="col-span-2">{{ t("Brand") }}</span>
                <span class="col-span-2">{{ t("Unit") }}</span>
                <span class="col-span-2">{{ t("Stock") }}</span>
                <span class="col-span-2">{{ t("Identifier") }}</span>
              </div>
            </template>
            <template #option="{ option }">
              <!-- Desktop: grid row -->
              <div class="hidden lg:grid grid-cols-12 gap-2 items-center w-full py-1">
                <div class="col-span-4 flex flex-col gap-0.5 min-w-0">
                  <span class="font-medium text-sm truncate">{{ option.product?.name }}</span>
                  <span v-if="option.name" class="text-sm text-surface-500 truncate">{{ option.name }}</span>
                </div>
                <div class="col-span-2 text-sm text-surface-500 truncate">
                  {{ option.product?.brand?.name ?? "—" }}
                </div>
                <div class="col-span-2">
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
                      <span v-if="option.product?.brand?.name">{{ option.product.brand.name }} · </span>
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
    </div>

    <DataTable :value="items" data-key="id" striped-rows row-hover>
      <template #empty>
        <div class="flex flex-col items-center justify-center py-10 text-surface-400">
          <i class="fa fa-boxes-stacked text-4xl mb-3"></i>
          <span class="font-medium text-lg mb-1">{{ t("No items added yet") }}</span>
          <small>{{ t("Use the search above to add products") }}</small>
        </div>
      </template>

      <Column :header="t('Product')" style="min-width: 180px">
        <template #body="{ data }">
          <span class="font-medium">{{ data.product_name }}</span>
          <div class="text-sm text-surface-500">{{ data.variant_label }}</div>
        </template>
      </Column>

      <Column :header="t('Brand')" style="min-width: 100px">
        <template #body="{ data }">
          <span class="text-sm">{{ data.brand_name }}</span>
        </template>
      </Column>

      <Column :header="t('Unit')" style="min-width: 80px">
        <template #body="{ data }">
          <span class="text-sm">{{ data.measurement_unit_name }}</span>
        </template>
      </Column>

      <Column :header="t('Quantity')" style="min-width: 140px">
        <template #body="{ data, index }">
          <InputNumber
            :model-value="data.quantity_requested"
            :min="1"
            size="small"
            :show-buttons="true"
            input-class="w-full tabular-nums"
            @update:model-value="(val: number) => updateQuantity(index, val)"
          />
        </template>
      </Column>

      <Column style="width: 80px">
        <template #body="{ index }">
          <Button
            v-tooltip.top="t('Delete')"
            icon="fa fa-trash-can"
            text
            rounded
            :aria-label="t('Delete item')"
            @click="confirmRemoveItem(index)"
          />
        </template>
      </Column>
    </DataTable>

    <ConfirmDialog />
  </div>
</template>