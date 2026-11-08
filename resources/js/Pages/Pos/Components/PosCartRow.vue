<script setup lang="ts">
import { Button, InputNumber, useToast } from "primevue";
import { computed, shallowRef } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import type { PosCartItem } from "@/Types/pos";

const props = defineProps<{
  item: PosCartItem;
  maximumQuantity: number;
}>();

const emit = defineEmits<{
  (e: "updateQuantity", quantity: number | null): void;
  (e: "showDetails", event: Event): void;
  (e: "editPrice", trigger: HTMLElement): void;
  (e: "remove"): void;
}>();

const { t } = useI18n();
const toast = useToast();
const quantityInputKey = shallowRef(0);
const { formatCurrency } = useCurrencyFormatter();
const priceModified = computed(() => Number(props.item.unit_price.toFixed(2)) !== Number(props.item.original_unit_price.toFixed(2)));

function editPrice(event: MouseEvent): void {
  emit("editPrice", event.currentTarget as HTMLElement);
}

function updateQuantity(quantity: number | null): void {
  if (quantity === null) return;
  if (Math.round(quantity * props.item.unit_price * 100) < Math.round((props.item.line_discount ?? 0) * 100)) {
    quantityInputKey.value++;
    toast.add({ severity: "warn", summary: t("Cannot update item"), detail: t("Reduce the line discount before lowering the quantity or price"), life: 4000, group: "pos" });
    return;
  }
  emit("updateQuantity", quantity);
}
</script>

<template>
  <li class="pos-cart-row">
    <div class="pos-cart-row__product min-w-0">
      <h3 class="m-0 text-lg font-semibold leading-6">
        <button
          v-tooltip.top="t('Product Details')"
          type="button"
          class="pos-cart-row__product-name"
          :aria-label="`${t('Product Details')}: ${props.item.product_name}`"
          @click="emit('showDetails', $event)"
        >
          {{ props.item.product_name }}
        </button>
      </h3>
      <p
        v-if="props.item.variant_identity || props.item.sale_unit?.name"
        class="pos-cart-row__identity mt-1 text-sm text-surface-500 dark:text-surface-400"
      >
        {{ props.item.variant_identity }}
        <template v-if="props.item.variant_identity && props.item.sale_unit?.name">·</template>
        {{ props.item.sale_unit?.name }}
      </p>
    </div>

    <div class="pos-cart-row__total shrink-0 text-right">
      <span class="pos-cart-row__field-label block text-xs font-medium text-surface-500 dark:text-surface-400">{{ t("Line total") }}</span>
      <span class="pos-cart-row__total-value block text-lg font-bold tabular-nums">
        {{ formatCurrency(String(props.item.line_total)) }}
      </span>
    </div>

    <div class="pos-cart-row__quantity min-w-0">
      <label :for="`pos-quantity-${props.item.id}`" class="pos-cart-row__field-label mb-1 block text-sm font-medium">
        {{ t("Quantity") }}
      </label>
      <InputNumber
        :key="quantityInputKey"
        :input-id="`pos-quantity-${props.item.id}`"
        :model-value="props.item.quantity"
        :min="1"
        :max="props.maximumQuantity"
        :min-fraction-digits="0"
        :max-fraction-digits="0"
        :aria-label="`${t('Quantity')}: ${props.item.product_name}`"
        show-buttons
        button-layout="horizontal"
        increment-button-icon="fa fa-plus"
        decrement-button-icon="fa fa-minus"
        fluid
        input-class="min-h-11 w-full tabular-nums"
        decrement-button-class="!min-h-11 !min-w-11"
        increment-button-class="!min-h-11 !min-w-11"
        @update:model-value="updateQuantity"
      />
      <small class="mt-1 block text-surface-500 dark:text-surface-400">{{ t("Max") }}: {{ props.maximumQuantity }}</small>
    </div>

    <div class="pos-cart-row__price min-w-0">
      <span class="pos-cart-row__field-label mb-1 block text-sm font-medium">{{ t("Unit Price") }}</span>
      <div class="pos-cart-row__price-control">
        <span class="pos-cart-row__price-value tabular-nums">{{ formatCurrency(String(props.item.unit_price)) }}</span>
      </div>
      <small v-if="priceModified" class="pos-cart-row__price-change mt-1 block text-surface-500 dark:text-surface-400">
        {{ t("Changed from") }} {{ formatCurrency(String(props.item.original_unit_price)) }}
      </small>
      <small v-if="(props.item.line_discount ?? 0) > 0" class="mt-1 block text-surface-500 dark:text-surface-400">
        {{ t("Line discount") }}: −{{ formatCurrency(String(props.item.line_discount)) }}
      </small>
    </div>

    <div class="pos-cart-row__actions flex items-center justify-center gap-2">
      <Button
        v-tooltip.top="t('Edit item')"
        :label="t('Edit')"
        icon="fa fa-pen"
        severity="primary"
        outlined
        class="min-h-11 w-full"
        :aria-label="`${t('Edit item')}: ${props.item.product_name}`"
        @click="editPrice"
      />
      <Button
        v-tooltip.top="t('Remove')"
        :label="t('Remove')"
        icon="fa fa-trash-can"
        severity="danger"
        outlined
        class="min-h-11 w-full"
        :aria-label="`${t('Remove')}: ${props.item.product_name}`"
        @click="emit('remove')"
      />
    </div>
  </li>
</template>

<style scoped>
.pos-cart-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 1rem 0.75rem;
  padding: 1rem;
}

.pos-cart-row__product {
  grid-column: 1;
}

.pos-cart-row__product-name {
  padding: 0;
  color: inherit;
  font: inherit;
  text-align: left;
  background: transparent;
  border: 0;
  cursor: pointer;
}

.pos-cart-row__product-name:hover,
.pos-cart-row__product-name:focus-visible {
  color: var(--p-primary-color);
  text-decoration: underline;
}

.pos-cart-row__product-name:focus-visible {
  outline: 2px solid var(--p-primary-color);
  outline-offset: 2px;
}

.pos-cart-row__total {
  grid-column: 2;
}

.pos-cart-row__price-control {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.25rem;
  min-height: 2.75rem;
}

.pos-cart-row__price-value {
  color: inherit;
}

.pos-cart-row__quantity {
  grid-column: 1;
}

.pos-cart-row__price {
  grid-column: 2;
}

.pos-cart-row__actions {
  grid-column: 1 / -1;
}

@container (min-width: 44rem) {
  .pos-cart-row {
    grid-template-columns: minmax(9rem, 1fr) 10rem minmax(9rem, 1fr) 8.5rem 6rem;
    align-items: start;
    padding-block: 1.25rem;
  }

  .pos-cart-row__product {
    grid-column: 1;
    grid-row: 1;
  }

  .pos-cart-row__quantity {
    grid-column: 2;
    grid-row: 1;
  }

  .pos-cart-row__price {
    grid-column: 3;
    grid-row: 1;
  }

  .pos-cart-row__total {
    grid-column: 4;
    grid-row: 1;
  }

  .pos-cart-row__actions {
    grid-column: 5;
    grid-row: 1;
  }

  .pos-cart-row__product-name {
    font-size: 1rem;
  }

  .pos-cart-row__field-label {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  .pos-cart-row__actions :deep(.p-button) {
    width: 2.75rem;
    padding-inline: 0;
    border-color: transparent;
    padding-top: 8px;
  }

  .pos-cart-row__actions :deep(.p-button-label) {
    display: none;
  }
}
</style>
