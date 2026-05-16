<script setup lang="ts">
import { Card, InputNumber, Textarea, SelectButton, Divider } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { ref, computed } from "vue";

const props = defineProps<{
  subTotal: number;
  total: number;
  discount: number | null | undefined;
  discountAttrs: Record<string, unknown>;
  maxDiscount: number;
  notes: string | null | undefined;
  notesAttrs: Record<string, unknown>;
  submitCount: number;
  errors: Record<string, string>;
}>();

const emit = defineEmits<{
  "update:discount": [value: number | null];
  "update:notes": [value: string | null];
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
    const amount = props.discount;
    if (amount === null || amount === undefined) return null;
    if (discountMode.value === "percentage") {
      return props.subTotal > 0 ? (amount / props.subTotal) * 100 : 0;
    }
    return amount;
  },
  set(val: number | null) {
    if (val === null || val === undefined) {
      emit("update:discount", null);
      return;
    }
    if (discountMode.value === "percentage") {
      emit("update:discount", props.subTotal * (val / 100));
    } else {
      emit("update:discount", val);
    }
  },
});

const discountMax = computed(() => {
  return discountMode.value === "percentage" ? 100 : props.maxDiscount;
});

function onModeChange(mode: "amount" | "percentage") {
  discountMode.value = mode;
}

function updateNotes(val: string | null) {
  emit("update:notes", val);
}
</script>

<template>
  <Card>
    <template #title>{{ t("Summary") }}</template>
    <template #content>
      <div class="flex flex-col gap-3">
        <div class="flex justify-between">
          <span class="text-surface-500">{{ t("Sub Total") }}</span>
          <span class="font-medium">{{ formatCurrency(String(subTotal ?? 0)) }}</span>
        </div>

        <div class="flex justify-between items-center gap-2">
          <span class="text-surface-500">{{ t("Discount") }}</span>
          <div class="flex items-center gap-2">
            <InputNumber
              :model-value="discountDisplayValue"
              :mode="discountMode === 'percentage' ? 'decimal' : 'currency'"
              :currency="discountMode === 'amount' ? currencyCode : undefined"
              :suffix="discountMode === 'percentage' ? '%' : undefined"
              :min="0"
              :max="discountMax"
              :min-fraction-digits="2"
              input-class="w-28 text-right"
              @update:model-value="discountDisplayValue = $event"
            />
            <SelectButton
              :model-value="discountMode"
              :options="modeOptions"
              option-label="label"
              option-value="value"
              @update:model-value="onModeChange"
            />
          </div>
        </div>

        <Divider class="!my-1" />

        <div class="flex justify-between">
          <span class="font-bold">{{ t("Total") }}</span>
          <span class="font-bold text-lg">{{ formatCurrency(String(total ?? 0)) }}</span>
        </div>

        <div class="flex flex-col gap-1 mt-2">
          <label for="po-notes">{{ t("Notes") }}</label>
          <Textarea
            id="po-notes"
            :model-value="notes"
            v-bind="notesAttrs"
            rows="4"
            :class="{ 'p-invalid': submitCount > 0 && !!errors.notes }"
            @update:model-value="updateNotes($event)"
          />
          <small v-if="submitCount > 0 && errors.notes" class="text-red-400 dark:text-red-300">{{ errors.notes }}</small>
        </div>
      </div>
    </template>
  </Card>
</template>