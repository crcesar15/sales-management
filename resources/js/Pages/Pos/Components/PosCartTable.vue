<script setup lang="ts">
import { useConfirm, useToast } from "primevue";
import { computed, nextTick, shallowRef } from "vue";
import { useI18n } from "vue-i18n";
import PosCartPriceDialog from "./PosCartPriceDialog.vue";
import PosCartRow from "./PosCartRow.vue";
import ProductInfoPopover from "./ProductInfoPopover.vue";
import type { PosCartItem, PosProductInfo } from "@/Types/pos";

const props = defineProps<{
  getRemainingBaseExcludingLine: (variantId: number, lineId: string) => number | null;
  toastGroup?: string;
  salesOrder?: boolean;
}>();

const items = defineModel<PosCartItem[]>({ required: true });

const { t } = useI18n();
const confirm = useConfirm();
const toast = useToast();
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

function maximumQuantity(item: PosCartItem): number | null {
  const remainingBase = props.getRemainingBaseExcludingLine(item.product_variant_id, item.id);
  if (remainingBase === null) return null;

  return Math.max(0, Math.floor(remainingBase / (item.conversion_factor > 0 ? item.conversion_factor : 1)));
}

function updateQuantity(itemId: string, quantity: number | null): void {
  if (quantity === null || !Number.isFinite(quantity)) return;

  const updated = [...items.value];
  const index = updated.findIndex((item) => item.id === itemId);
  if (index === -1) return;
  const current = updated[index];
  const maximum = maximumQuantity(current);
  if (maximum !== null && maximum < 1) return;
  const nextQuantity = Math.min(Math.max(Math.floor(quantity), 1), maximum ?? 99999);
  if (Math.round(nextQuantity * current.unit_price * 100) < Math.round((current.line_discount ?? 0) * 100)) {
    toast.add({ severity: "warn", summary: t("Cannot update item"), detail: t("Reduce the line discount before lowering the quantity or price"), life: 4000, group: props.toastGroup });
    return;
  }
  updated[index] = { ...current, quantity: nextQuantity, line_total: (Math.round(nextQuantity * current.unit_price * 100) - Math.round((current.line_discount ?? 0) * 100)) / 100 };
  items.value = updated;
}

function openPriceEditor(item: PosCartItem, trigger: HTMLElement): void {
  priceItemId.value = item.id;
  priceEditTrigger.value = trigger;
  priceEditorVisible.value = true;
}

function updateItem(itemId: string, quantity: number, price: number, discount: number): void {
  const index = items.value.findIndex((item) => item.id === itemId);
  if (index === -1) return;

  const updated = [...items.value];
  const current = updated[index];
  const maximum = maximumQuantity(current);
  if ((maximum !== null && quantity > maximum) || quantity < 1 || !Number.isInteger(quantity)) {
    toast.add({ severity: "warn", summary: t("Cannot update item"), detail: t("Quantity cannot exceed available stock"), life: 4000, group: props.toastGroup });
    return;
  }
  updated[index] = { ...current, quantity, unit_price: price, line_discount: discount, line_total: (Math.round(price * quantity * 100) - Math.round(discount * 100)) / 100 };
  items.value = updated;
}

function updatePriceEditorVisibility(visible: boolean): void {
  priceEditorVisible.value = visible;
  if (visible) return;

  void nextTick(() => priceEditTrigger.value?.focus());
}

function confirmRemove(itemId: string): void {
  confirm.require({
    message: t("Are you sure you want to remove this item?"),
    header: t("Confirm"),
    icon: "fa fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Remove"),
    acceptProps: { severity: "danger" },
    rejectProps: { severity: "secondary" },
    accept: () => {
      items.value = items.value.filter((item) => item.id !== itemId);
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
      <p class="m-0 font-medium">{{ t(salesOrder ? "No items added yet" : "Your sale is empty") }}</p>
      <small>{{ t(salesOrder ? "Use Add products to add products" : "Search for products to add them to this sale") }}</small>
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
            :toast-group="toastGroup"
            @update-quantity="updateQuantity(item.id, $event)"
            @show-details="toggleInfo($event, item)"
            @edit-price="openPriceEditor(item, $event)"
            @remove="confirmRemove(item.id)"
          />
        </ul>
      </div>
    </div>

    <ProductInfoPopover ref="infoPopover" :info="infoData" />
    <PosCartPriceDialog
      :visible="priceEditorVisible"
      :item="priceItem"
      :maximum-quantity="priceItem ? maximumQuantity(priceItem) : null"
      @update:visible="updatePriceEditorVisibility"
      @update-item="updateItem"
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
