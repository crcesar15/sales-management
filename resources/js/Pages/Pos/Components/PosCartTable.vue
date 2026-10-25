<script setup lang="ts">
import { useConfirm } from "primevue";
import { computed, nextTick, shallowRef } from "vue";
import { useI18n } from "vue-i18n";
import PosCartPriceDialog from "./PosCartPriceDialog.vue";
import PosCartRow from "./PosCartRow.vue";
import ProductInfoPopover from "./ProductInfoPopover.vue";
import type { PosCartItem, PosProductInfo } from "@/Types/pos";

const props = defineProps<{
  getRemainingBaseExcludingLine: (variantId: number, lineId: string) => number | null;
}>();

const items = defineModel<PosCartItem[]>({ required: true });

const { t } = useI18n();
const confirm = useConfirm();
const hasItems = computed(() => items.value.length > 0);
const infoPopover = shallowRef<{ toggle: (event: Event) => void }>();
const infoItemId = shallowRef<string | null>(null);
const priceItemId = shallowRef<string | null>(null);
const priceEditorVisible = shallowRef(false);
const priceEditTrigger = shallowRef<HTMLElement | null>(null);
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
const priceItem = computed(() => items.value.find((item) => item.id === priceItemId.value) ?? null);

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

function openPriceEditor(item: PosCartItem, trigger: HTMLElement): void {
  priceItemId.value = item.id;
  priceEditTrigger.value = trigger;
  priceEditorVisible.value = true;
}

function updateUnitPrice(itemId: string, price: number): void {
  const index = items.value.findIndex((item) => item.id === itemId);
  if (index === -1) return;

  const updated = [...items.value];
  const current = updated[index];
  updated[index] = { ...current, unit_price: price, line_total: price * current.quantity };
  items.value = updated;
}

function updatePriceEditorVisibility(visible: boolean): void {
  priceEditorVisible.value = visible;
  if (visible) return;

  void nextTick(() => priceEditTrigger.value?.focus());
}

function confirmRemove(index: number): void {
  confirm.require({
    message: t("Are you sure you want to remove this item?"),
    header: t("Confirm"),
    icon: "fa fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Remove"),
    acceptProps: { severity: "danger" },
    rejectProps: { severity: "secondary" },
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
      <div class="pos-cart-layout">
        <div
          class="pos-cart-column-header border-b border-surface-200 px-4 py-3 text-xs font-medium uppercase tracking-wide text-surface-500 dark:border-surface-700 dark:text-surface-400"
        >
          <span>{{ t("Product") }}</span>
          <span>{{ t("Quantity") }}</span>
          <span>{{ t("Unit Price") }}</span>
          <span class="text-right">{{ t("Line total") }}</span>
          <span class="text-center">{{ t("Actions") }}</span>
        </div>
        <ul class="divide-y divide-surface-300 dark:divide-surface-600">
          <PosCartRow
            v-for="(item, index) in items"
            :key="item.id"
            :class="{ 'bg-surface-50 dark:bg-surface-800/50': index % 2 === 1 }"
            :item="item"
            :maximum-quantity="maximumQuantity(item)"
            @update-quantity="updateQuantity(index, $event)"
            @show-details="toggleInfo($event, item)"
            @edit-price="openPriceEditor(item, $event)"
            @remove="confirmRemove(index)"
          />
        </ul>
      </div>
    </div>

    <ProductInfoPopover ref="infoPopover" :info="infoData" />
    <PosCartPriceDialog
      :visible="priceEditorVisible"
      :item="priceItem"
      @update:visible="updatePriceEditorVisibility"
      @update-price="updateUnitPrice"
    />
  </section>
</template>

<style scoped>
.pos-cart-layout {
  container-type: inline-size;
}

.pos-cart-column-header {
  display: none;
}

@container (min-width: 44rem) {
  .pos-cart-column-header {
    display: grid;
    grid-template-columns: minmax(9rem, 1fr) 10rem minmax(9rem, 1fr) 8.5rem 6rem;
    gap: 0.75rem;
  }
}
</style>
