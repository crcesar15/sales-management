<script setup lang="ts">
import { Badge, Button, InputNumber, useConfirm } from "primevue";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import ProductInfoPopover from "./ProductInfoPopover.vue";
import type { PosCartItem, PosProductInfo } from "@/Types/pos";

const props = defineProps<{
  getRemainingBaseExcludingLine: (variantId: number, lineId: string) => number | null;
}>();

const items = defineModel<PosCartItem[]>({ required: true });

const { t } = useI18n();
const { currencyCode, formatCurrency } = useCurrencyFormatter();
const confirm = useConfirm();

const hasItems = computed(() => items.value.length > 0);

const infoPopover = ref();
const infoItemId = ref<string | null>(null);
const infoData = computed<PosProductInfo | null>(() => {
  const item = items.value.find((entry) => entry.id === infoItemId.value);
  if (item === undefined) return null;

  return {
    product_name: item.product_name,
    brand_name: item.brand_name,
    unit_price: item.unit_price,
    base_unit_name: item.base_unit_name ?? null,
    sale_unit_name: item.sale_unit?.name ?? null,
    conversion_factor: item.conversion_factor,
  };
});

function toggleInfo(event: Event, item: PosCartItem): void {
  infoItemId.value = item.id;
  infoPopover.value?.toggle(event);
}

function maximumQuantity(item: PosCartItem): number {
  const remainingBase = props.getRemainingBaseExcludingLine(item.product_variant_id, item.id);
  if (remainingBase === null) return 99999;

  return Math.max(1, Math.floor(remainingBase / Math.max(item.conversion_factor, 1)));
}

function updateQuantity(index: number, quantity: number | null): void {
  if (quantity === null) return;

  const updated = [...items.value];
  const current = updated[index];
  const nextQuantity = Math.min(Math.max(Math.floor(quantity), 1), maximumQuantity(current));
  updated[index] = { ...current, quantity: nextQuantity, line_total: nextQuantity * current.unit_price };
  items.value = updated;
}

function priceModified(item: PosCartItem): boolean {
  return Number(item.unit_price.toFixed(2)) !== Number(item.original_unit_price.toFixed(2));
}

function updateUnitPrice(index: number, price: number | null): void {
  if (price === null) return;

  const updated = [...items.value];
  const current = updated[index];
  const nextPrice = Math.max(Number(price.toFixed(2)), 0);
  updated[index] = { ...current, unit_price: nextPrice, line_total: nextPrice * current.quantity };
  items.value = updated;
}

function confirmRemove(index: number): void {
  confirm.require({
    message: t("Are you sure you want to remove this item?"),
    header: t("Confirm"),
    icon: "fa fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Remove"),
    accept: () => {
      items.value = items.value.filter((_, itemIndex) => itemIndex !== index);
    },
  });
}
</script>

<template>
  <section class="flex min-h-0 flex-1 flex-col" :aria-label="t('Current sale')">
    <div
      v-if="!hasItems"
      class="flex min-h-64 flex-1 flex-col items-center justify-center px-6 text-center text-surface-500 dark:text-surface-400"
    >
      <i class="fa fa-cart-shopping mb-3 text-3xl" aria-hidden="true" />
      <p class="m-0 font-medium">{{ t("Your sale is empty") }}</p>
      <small>{{ t("Search for products to add them to this sale") }}</small>
    </div>

    <div v-else class="min-h-0 flex-1 overflow-y-auto">
      <div
        class="hidden grid-cols-[minmax(0,1fr)_10rem_8rem_9rem_2.75rem] gap-3 border-b border-surface-200 bg-surface-50 px-4 py-3 text-sm font-medium dark:border-surface-700 dark:bg-surface-950 xl:grid"
      >
        <span>{{ t("Product") }}</span>
        <span>{{ t("Quantity") }}</span>
        <span>{{ t("Unit Price") }}</span>
        <span class="text-right">{{ t("Amount") }}</span>
        <span class="sr-only">{{ t("Actions") }}</span>
      </div>

      <ul class="divide-y divide-surface-200 dark:divide-surface-700">
        <li v-for="(item, index) in items" :key="item.id" class="p-4">
          <div class="grid grid-cols-2 gap-3 xl:grid-cols-[minmax(0,1fr)_10rem_8rem_9rem_2.75rem] xl:items-start">
            <div class="col-span-2 min-w-0 xl:col-span-1">
              <div class="flex items-center gap-2">
                <span class="font-semibold text-lg">{{ item.product_name }}</span>
                <i
                  class="fa fa-circle-info shrink-0 cursor-pointer text-surface-400 hover:text-primary dark:text-surface-500 dark:hover:text-primary"
                  role="button"
                  tabindex="0"
                  :aria-label="`${t('Product Details')}: ${item.product_name}`"
                  @click="toggleInfo($event, item)"
                  @keydown.enter.prevent="toggleInfo($event, item)"
                />
              </div>
              <div class="mt-1 flex flex-wrap gap-x-2 text-surface-500 dark:text-surface-400">
                <Badge v-if="item.variant_identity" :value="item.variant_identity" severity="primary" />
              </div>
            </div>

            <div class="min-w-0">
              <label :for="`pos-quantity-${item.id}`" class="mb-1 block text-sm font-medium xl:sr-only">{{ t("Quantity") }}</label>
              <InputNumber
                :input-id="`pos-quantity-${item.id}`"
                :model-value="item.quantity"
                :min="1"
                :max="maximumQuantity(item)"
                :min-fraction-digits="0"
                :max-fraction-digits="0"
                :aria-label="`${t('Quantity')}: ${item.product_name}`"
                show-buttons
                button-layout="horizontal"
                increment-button-icon="fa fa-plus"
                decrement-button-icon="fa fa-minus"
                fluid
                input-class="min-h-11 w-full tabular-nums"
                decrement-button-class="!min-h-11 !min-w-11"
                increment-button-class="!min-h-11 !min-w-11"
                @update:model-value="updateQuantity(index, $event)"
              />
              <small class="mt-1 block text-surface-500 dark:text-surface-400">{{ t("Max") }}: {{ maximumQuantity(item) }}</small>
            </div>

            <div class="min-w-0">
              <label :for="`pos-price-${item.id}`" class="mb-1 block text-sm font-medium xl:sr-only">{{ t("Unit Price") }}</label>
              <InputNumber
                :input-id="`pos-price-${item.id}`"
                :model-value="item.unit_price"
                :min="0"
                :min-fraction-digits="2"
                :max-fraction-digits="2"
                :currency="currencyCode"
                mode="currency"
                :aria-label="`${t('Unit Price')}: ${item.product_name}`"
                fluid
                input-class="min-h-11 w-full tabular-nums"
                @update:model-value="updateUnitPrice(index, $event)"
              />
              <small v-if="priceModified(item)" class="mt-1 block text-surface-500 dark:text-surface-400" :title="t('Original price')">
                {{ t("Orig.") }}:
                <span class="line-through">{{ formatCurrency(String(item.original_unit_price)) }}</span>
              </small>
            </div>

            <div class="flex items-end justify-end text-right xl:min-h-11 xl:items-center">
              <span class="font-semibold tabular-nums">{{ formatCurrency(String(item.line_total)) }}</span>
            </div>

            <div class="flex items-end justify-end xl:min-h-11 xl:items-center">
              <Button
                icon="fa fa-trash-can"
                severity="danger"
                text
                rounded
                :aria-label="`${t('Remove')}: ${item.product_name}`"
                class="min-h-11 min-w-11"
                @click="confirmRemove(index)"
              />
            </div>
          </div>
        </li>
      </ul>
    </div>

    <ProductInfoPopover ref="infoPopover" :info="infoData" />
  </section>
</template>
