<script setup lang="ts">
import { Button, Column, ConfirmDialog, DataTable, InputNumber, Tag, useConfirm, useToast } from "primevue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import StockTransferProductPicker from "./StockTransferProductPicker.vue";
import type { StockTransferVariantSearchResult } from "@app-types/stock-transfer-types";

export interface TransferLineItem {
  id: string;
  product_variant_id: number;
  product_name: string;
  variant_label: string;
  brand_name: string;
  measurement_unit_name: string;
  available_stock: number;
  quantity_requested: number;
}

const props = defineProps<{
  modelValue: TransferLineItem[];
  storeId: number | null;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", items: TransferLineItem[]): void;
}>();

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

const items = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
});
const addedVariantIds = computed(() => new Set(items.value.map((item) => item.product_variant_id)));

function variantLabel(variant: StockTransferVariantSearchResult): string {
  return variant.option_values ?? variant.identifier ?? variant.product?.name ?? "—";
}

function addVariant(variant: StockTransferVariantSearchResult) {
  if (addedVariantIds.value.has(variant.id)) {
    toast.add({ severity: "warn", summary: t("Duplicate"), detail: t("Product already added"), life: 3000 });
    return;
  }

  if (variant.stock <= 0) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Product is out of stock in the source store"), life: 3000 });
    return;
  }

  const item: TransferLineItem = {
    id: crypto.randomUUID(),
    product_variant_id: variant.id,
    product_name: variant.product?.name ?? "—",
    variant_label: variantLabel(variant),
    brand_name: variant.product?.brand?.name ?? "—",
    measurement_unit_name: variant.product?.measurement_unit?.name ?? t("Unit"),
    available_stock: variant.stock,
    quantity_requested: 1,
  };

  emit("update:modelValue", [...items.value, item]);
}

function updateQuantity(index: number, quantity: number | null) {
  const item = items.value[index];
  if (!item || quantity === null) return;

  const updated = [...items.value];
  updated[index] = { ...item, quantity_requested: Math.min(Math.max(1, quantity), item.available_stock) };
  emit("update:modelValue", updated);
}

function removeItem(index: number) {
  emit(
    "update:modelValue",
    items.value.filter((_, itemIndex) => itemIndex !== index),
  );
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
    accept: () => removeItem(index),
  });
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <div class="flex flex-col gap-2">
      <label for="stock-transfer-product-search">{{ t("Search Product") }}</label>
      <StockTransferProductPicker :store-id="storeId" :added-variant-ids="addedVariantIds" @select="addVariant" />
      <small class="text-surface-500 dark:text-surface-400">{{ t("Quantities are entered in each product's base unit.") }}</small>
    </div>

    <DataTable :value="items" data-key="id" striped-rows row-hover>
      <template #empty>
        <div class="flex flex-col items-center justify-center gap-2 py-10 text-surface-400">
          <i class="fa fa-boxes-stacked text-4xl" />
          <span class="font-medium text-lg">{{ t("No items added yet") }}</span>
          <small>{{ t("Select a source store and search for products above") }}</small>
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

      <Column :header="t('Base unit')" style="min-width: 100px">
        <template #body="{ data }">
          <span class="text-sm">{{ data.measurement_unit_name }}</span>
        </template>
      </Column>

      <Column :header="t('Available')" style="min-width: 140px">
        <template #body="{ data }">
          <Tag :value="`${data.available_stock} ${data.measurement_unit_name}`" severity="success" rounded />
        </template>
      </Column>

      <Column :header="t('Quantity')" style="min-width: 170px">
        <template #body="{ data, index }">
          <InputNumber
            :model-value="data.quantity_requested"
            :min="1"
            :max="data.available_stock"
            :show-buttons="true"
            size="small"
            input-class="w-full tabular-nums"
            @update:model-value="updateQuantity(index, $event)"
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
