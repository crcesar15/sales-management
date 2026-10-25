<script setup lang="ts">
import { Button, InputNumber, SelectButton } from "primevue";
import { computed, shallowRef, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import type { PosDiscountType } from "@/Types/pos";

const props = defineProps<{
  subTotal: number;
  discountAmount: number;
  taxAmount: number;
  taxRate: number;
  total: number;
  discountType: PosDiscountType;
  discountValue: number;
  customerChoiceMade: boolean;
  canPay: boolean;
  processing: boolean;
  showPayAction: boolean;
}>();

const emit = defineEmits<{
  (e: "updateDiscount", type: PosDiscountType, value: number | null): void;
  (e: "pay"): void;
}>();

const { t } = useI18n();
const { currencyCode, formatCurrency } = useCurrencyFormatter();
const discountEditing = shallowRef(props.discountValue > 0);

const discountOptions = [
  { label: currencyCode, value: "flat" },
  { label: "%", value: "percentage" },
];
const maximumDiscount = computed(() => (props.discountType === "percentage" ? 100 : props.subTotal));

watch(
  () => props.discountValue,
  (value) => {
    if (value > 0) discountEditing.value = true;
  },
);

function updateDiscountType(type: PosDiscountType): void {
  emit("updateDiscount", type, 0);
}

function updateDiscountValue(value: number | null): void {
  emit("updateDiscount", props.discountType, value);
}

function removeDiscount(): void {
  emit("updateDiscount", "flat", 0);
  discountEditing.value = false;
}
</script>

<template>
  <section class="pos-cart-summary border-t border-surface-200 p-4 dark:border-surface-700" :aria-label="t('Sale summary')">
    <div class="flex items-center justify-between gap-4 text-surface-600 dark:text-surface-300">
      <span>{{ t("Subtotal") }}</span>
      <span class="font-medium tabular-nums">{{ formatCurrency(String(subTotal)) }}</span>
    </div>

    <div class="sr-only mt-3 flex items-center justify-between gap-4 text-surface-600 dark:text-surface-300">
      <span>{{ t("Tax") }} ({{ taxRate }}%)</span>
      <span class="font-medium tabular-nums">{{ formatCurrency(String(taxAmount)) }}</span>
    </div>

    <div class="mt-3">
      <div v-if="!discountEditing" class="flex items-center justify-between gap-3">
        <span class="font-medium">{{ t("Discount") }}</span>
        <Button
          :label="t('Add discount')"
          icon="fa fa-plus"
          text
          class="min-h-11 !pr-0"
          :disabled="subTotal === 0"
          @click="discountEditing = true"
        />
      </div>

      <div v-else class="flex flex-wrap items-center justify-between gap-3">
        <span class="font-medium">{{ t("Discount") }}</span>
        <div class="flex items-center gap-2">
          <SelectButton
            :model-value="discountType"
            :options="discountOptions"
            option-label="label"
            option-value="value"
            :aria-label="t('Discount type')"
            @update:model-value="updateDiscountType($event)"
          />
          <InputNumber
            :model-value="discountValue"
            :mode="discountType === 'flat' ? 'currency' : 'decimal'"
            :currency="discountType === 'flat' ? currencyCode : undefined"
            show-clear
            :suffix="discountType === 'percentage' ? '%' : undefined"
            :min="0"
            :max="maximumDiscount"
            :min-fraction-digits="2"
            :max-fraction-digits="2"
            :disabled="subTotal === 0"
            :aria-label="t('Discount value')"
            input-class="min-h-11 w-18 text-right tabular-nums"
            @update:model-value="updateDiscountValue($event)"
          />
        </div>
      </div>

      <div v-if="discountAmount > 0" class="mt-2 flex items-center justify-between gap-3 text-sm text-surface-500 dark:text-surface-400">
        <span>{{ t("Discount applied") }}: −{{ formatCurrency(String(discountAmount)) }}</span>
        <Button :label="t('Remove discount')" icon="fa fa-times" text class="min-h-11 !pr-0" @click="removeDiscount" />
      </div>
    </div>

    <div v-if="showPayAction" class="pos-cart-summary__checkout mt-4 border-t border-surface-200 pt-4 dark:border-surface-700">
      <div class="flex items-center justify-between gap-4 text-lg font-bold">
        <span>{{ t("Total") }}</span>
        <span class="tabular-nums">{{ formatCurrency(String(total)) }}</span>
      </div>
      <Button
        :label="t('Continue to payment')"
        icon="fa fa-credit-card"
        :disabled="!canPay"
        :loading="processing"
        raised
        class="pos-cart-summary__pay-button min-h-12 w-full uppercase"
        @click="emit('pay')"
      />
    </div>
    <small v-if="showPayAction" class="mt-2 block text-center text-surface-500 dark:text-surface-400">
      {{
        !customerChoiceMade
          ? t("Select a customer or Walk-in before payment")
          : total <= 0
            ? t("A draft can be saved, but the total must be greater than zero to pay")
            : customerChoiceMade
              ? t("Payment will be available on the next step")
              : ""
      }}
    </small>
  </section>
</template>

<style scoped>
.pos-cart-summary__pay-button {
  margin-top: 1rem;
}
</style>
