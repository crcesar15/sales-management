<script setup lang="ts">
import { InputNumber, SelectButton } from "primevue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import type { PurchaseDiscountType } from "@app-types/purchase-order-types";

defineProps<{ inputId: string; error?: string }>();
const type = defineModel<PurchaseDiscountType>("type", { required: true });
const value = defineModel<number | null>("value", { required: true });
const { t } = useI18n();
const { currencyCode } = useCurrencyFormatter();
const options = computed(() => [
  { label: currencyCode, value: "flat" },
  { label: "%", value: "percentage" },
]);
function changeType(next: PurchaseDiscountType) {
  if (next === type.value) return;
  type.value = next;
}
</script>

<template>
  <div class="flex min-w-0 flex-col gap-2">
    <label :for="inputId">{{ t(type === "flat" ? "Item discount — whole line" : "Item discount (%)") }}</label>
    <div class="flex items-center gap-2">
      <InputNumber
        :input-id="inputId"
        v-model="value"
        :mode="type === 'flat' ? 'currency' : 'decimal'"
        :currency="currencyCode"
        :suffix="type === 'percentage' ? '%' : undefined"
        :min="0"
        :max="type === 'percentage' ? 100 : undefined"
        :min-fraction-digits="2"
        :max-fraction-digits="2"
        :invalid="!!error"
        class="min-w-0 flex-1"
        fluid
      />
      <SelectButton
        :model-value="type"
        :options="options"
        option-label="label"
        option-value="value"
        :allow-empty="false"
        :aria-label="t('Item discount type')"
        @update:model-value="changeType"
      />
    </div>
    <small v-if="error" class="text-red-400 dark:text-red-300">{{ t(error) }}</small>
  </div>
</template>
