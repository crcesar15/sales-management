<script setup lang="ts">
import { Button, ConfirmDialog, useToast } from "primevue";
import { computed, nextTick, shallowRef, useTemplateRef } from "vue";
import { useI18n } from "vue-i18n";
import PosCartTable from "@pages/Pos/Components/PosCartTable.vue";
import PosProductPicker from "@pages/Pos/Components/PosProductPicker.vue";
import type { PosAddItemOptions } from "@/Types/pos";
import type { SalesOrderLineItemForm } from "@/Types/sales-order-types";

export type LineItem = SalesOrderLineItemForm;

const props = defineProps<{
  modelValue: LineItem[];
  getRemainingBase?: (variantId: number) => number | null;
  getRemainingBaseExcludingLine?: (variantId: number, lineId: string) => number | null;
  storeId?: number | null;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", items: LineItem[]): void;
}>();

const { t } = useI18n();
const toast = useToast();
const productPickerVisible = shallowRef(false);
const barcodeMode = shallowRef(false);
const addProductsButton = useTemplateRef<{ $el: HTMLButtonElement }>("addProductsButton");
const items = computed({
  get: () => props.modelValue,
  set: (value: LineItem[]) => emit("update:modelValue", value),
});

function allocatedBase(variantId: number): number {
  return items.value.reduce((total, item) => item.product_variant_id === variantId
    ? total + item.quantity * (item.conversion_factor > 0 ? item.conversion_factor : 1)
    : total, 0);
}

function remainingBase(variantId: number): number | null {
  const live = props.getRemainingBase?.(variantId);
  if (live != null) return live;
  const snapshot = items.value.find((item) => item.product_variant_id === variantId && item.stock != null)?.stock;
  return snapshot == null ? null : snapshot - allocatedBase(variantId);
}

function remainingExcludingLine(variantId: number, lineId: string): number | null {
  const live = props.getRemainingBaseExcludingLine?.(variantId, lineId);
  if (live != null) return live;
  const snapshot = items.value.find((item) => item.product_variant_id === variantId && item.stock != null)?.stock;
  if (snapshot == null) return null;
  return snapshot - items.value.reduce((total, item) =>
    item.product_variant_id === variantId && item.id !== lineId
      ? total + item.quantity * (item.conversion_factor > 0 ? item.conversion_factor : 1)
      : total, 0);
}

function onPickerAdd(item: LineItem, options?: PosAddItemOptions): void {
  const existingIndex = items.value.findIndex((entry) =>
    entry.product_variant_id === item.product_variant_id && entry.sale_unit_id === item.sale_unit_id,
  );
  const snapshot = item.stock;
  const allocated = allocatedBase(item.product_variant_id);
  const remaining = remainingBase(item.product_variant_id) ?? (snapshot == null ? null : snapshot - allocated);
  const factor = item.conversion_factor > 0 ? item.conversion_factor : 1;
  if (remaining !== null && remaining < factor) {
    toast.add({ severity: "warn", summary: t("Stock limit reached"), detail: t("No more stock is available for this product"), life: 3000 });
    return;
  }

  if (existingIndex === -1) {
    items.value = [...items.value, item];
    finishAddition(options);
    return;
  }

  const updated = [...items.value];
  const existing = updated[existingIndex];
  const quantity = existing.quantity + 1;
  updated[existingIndex] = {
    ...existing,
    stock: existing.stock ?? item.stock,
    quantity,
    line_total: (Math.round(quantity * existing.unit_price * 100) - Math.round((existing.line_discount ?? 0) * 100)) / 100,
  };
  items.value = updated;
  finishAddition(options);
}

function finishAddition(options?: PosAddItemOptions): void {
  if (!options?.keepProductPickerOpen) updateProductPickerVisibility(false);
  toast.add({ severity: "success", summary: t("Added"), detail: t("Product added to the sale"), life: 2000 });
}

function updateProductPickerVisibility(visible: boolean): void {
  productPickerVisible.value = visible;
  if (!visible) void nextTick(() => addProductsButton.value?.$el.focus());
}
</script>

<template>
  <div>
    <ConfirmDialog />
    <div class="mb-3 flex items-center justify-between gap-3">
      <h3 class="m-0 text-xl font-semibold">{{ t("Products") }}</h3>
      <Button
        ref="addProductsButton"
        :label="t('Add products')"
        icon="fa fa-cart-plus"
        class="shrink-0"
        :disabled="storeId == null"
        @click="productPickerVisible = true"
      />
    </div>
    <PosProductPicker
      :visible="productPickerVisible"
      v-model:barcode-mode="barcodeMode"
      input-id="so-product-search"
      :toast-group="null"
      :cart-items="items"
      :store-id="storeId ?? null"
      :get-remaining-base="remainingBase"
      @update:visible="updateProductPickerVisibility"
      @add="onPickerAdd"
    />
    <PosCartTable v-model="items" sales-order :get-remaining-base-excluding-line="remainingExcludingLine" />
  </div>
</template>
