<script setup lang="ts">
import { InputNumber, SelectButton } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { computed, ref } from "vue";

const props = defineProps<{
  subTotal: number;
  discountValue: number | null | undefined;
  discountAttrs: Record<string, unknown>;
  maxDiscount: number;
}>();

const emit = defineEmits<{
  "update:discountValue": [value: number | null];
}>();

const { t } = useI18n();
const { formatCurrency, currencyCode } = useCurrencyFormatter();

const discountMode = ref<"amount" | "percentage">("amount");
const modeOptions = [
  { label: currencyCode, value: "amount" },
  { label: "%", value: "percentage" },
];

const discountDisplayValue = computed<number | null>({
  get() {
    const amount = props.discountValue;
    if (amount === null || amount === undefined) return null;
    if (discountMode.value === "percentage") {
      return props.subTotal > 0 ? (amount / props.subTotal) * 100 : 0;
    }
    return amount;
  },
  set(value: number | null) {
    if (value === null || value === undefined) {
      emit("update:discountValue", null);
      return;
    }
    if (discountMode.value === "percentage") {
      emit("update:discountValue", props.subTotal * (value / 100));
      return;
    }
    emit("update:discountValue", value);
  },
});

const discountMax = computed(() => (discountMode.value === "percentage" ? 100 : props.maxDiscount));

const discountHint = computed(() => {
  if (!props.discountValue || props.discountValue <= 0) return null;
  if (discountMode.value === "amount") {
    return props.subTotal > 0 ? `(${((props.discountValue / props.subTotal) * 100).toFixed(1)}%)` : null;
  }
  return `(${formatCurrency(String(props.discountValue))})`;
});
</script>

<template>
  <div class="flex items-center justify-between gap-2">
    <span class="text-lg lg:text-base">{{ t("Discount") }}</span>
    <div class="flex items-center gap-2">
      <SelectButton
        :model-value="discountMode"
        :options="modeOptions"
        option-label="label"
        option-value="value"
        @update:model-value="discountMode = $event"
      />
      <InputNumber
        :model-value="discountDisplayValue"
        v-bind="discountAttrs"
        :mode="discountMode === 'percentage' ? 'decimal' : 'currency'"
        :currency="discountMode === 'amount' ? currencyCode : undefined"
        :suffix="discountMode === 'percentage' ? '%' : undefined"
        :min="0"
        :max="discountMax"
        :min-fraction-digits="2"
        input-class="w-28 text-right"
        @update:model-value="discountDisplayValue = $event"
      />
      <span v-if="discountHint" class="text-sm text-surface-500 whitespace-nowrap dark:text-surface-400">
        {{ discountHint }}
      </span>
    </div>
  </div>
</template>
