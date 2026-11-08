<script setup lang="ts">
import { Button, Dialog, InputNumber } from "primevue";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { number, object } from "yup";
import { computed, nextTick, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import type { PosCartItem } from "@/Types/pos";

const props = defineProps<{
  item: PosCartItem | null;
  maximumQuantity: number | null;
}>();

const emit = defineEmits<{
  (e: "updateItem", itemId: string, quantity: number, price: number, discount: number): void;
}>();

const visible = defineModel<boolean>("visible", { default: false });
const { t } = useI18n();
const { currencyCode, formatCurrency } = useCurrencyFormatter();
const schema = toTypedSchema(object({
  quantity: number().required().integer().min(1),
  unitPrice: number().required().min(0),
  lineDiscount: number().transform((value, originalValue) => (originalValue === null || originalValue === "" ? 0 : value)).required().min(0),
}));
const { handleSubmit, defineField, errors, resetForm, setFieldValue, setFieldError } = useForm({
  validationSchema: schema,
  initialValues: { quantity: 1, unitPrice: 0, lineDiscount: 0 },
});
const [quantity, quantityAttrs] = defineField("quantity");
const [unitPrice, unitPriceAttrs] = defineField("unitPrice");
const [lineDiscount, lineDiscountAttrs] = defineField("lineDiscount");
const grossAmount = computed(() => Math.round((unitPrice.value ?? 0) * (quantity.value ?? 0) * 100) / 100);
const previewTotal = computed(() => Math.max(0, Math.round((grossAmount.value - (lineDiscount.value ?? 0)) * 100) / 100));
const priceModified = computed(
  () => props.item !== null && Number((unitPrice.value ?? 0).toFixed(2)) !== Number(props.item.original_unit_price.toFixed(2)),
);

watch(visible, (isVisible) => {
  if (!isVisible || props.item === null) return;

  resetForm({ values: { quantity: props.item.quantity, unitPrice: props.item.unit_price, lineDiscount: props.item.line_discount ?? 0 } });
  void nextTick(() => document.getElementById("pos-unit-price")?.focus());
});

const applyPrice = handleSubmit(({ quantity: nextQuantity, unitPrice: price, lineDiscount: discount }) => {
  if (props.item === null) return;

  if (props.maximumQuantity !== null && nextQuantity > props.maximumQuantity) {
    setFieldError("quantity", t("Quantity cannot exceed available stock"));
    return;
  }
  const normalizedPrice = Number(price.toFixed(2));
  const normalizedDiscount = Number(discount.toFixed(2));
  if (Math.round(normalizedDiscount * 100) > Math.round(normalizedPrice * nextQuantity * 100)) {
    setFieldError("lineDiscount", t("Line discount cannot exceed the line amount"));
    return;
  }
  emit("updateItem", props.item.id, nextQuantity, normalizedPrice, normalizedDiscount);
  visible.value = false;
});

function resetPrice(): void {
  if (props.item === null) return;

  setFieldValue("unitPrice", props.item.original_unit_price);
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Edit item')"
    :style="{ width: '420px', maxHeight: 'calc(100dvh - 1rem)' }"
    :breakpoints="{ '768px': 'calc(100vw - 1rem)' }"
  >
    <form class="flex flex-col gap-4" @submit.prevent="applyPrice">
      <div v-if="props.item" class="border-b border-surface-200 pb-3 dark:border-surface-700">
        <p class="m-0 font-semibold">{{ props.item.product_name }}</p>
        <p v-if="props.item.variant_identity || props.item.sale_unit?.name" class="mt-1 text-sm text-surface-500 dark:text-surface-400">
          {{ props.item.variant_identity }}
          <template v-if="props.item.variant_identity && props.item.sale_unit?.name">·</template>
          {{ props.item.sale_unit?.name }}
        </p>
      </div>

      <div class="flex flex-col gap-1">
        <label for="pos-unit-price">{{ t("Unit Price") }}</label>
        <InputNumber
          input-id="pos-unit-price"
          v-model="unitPrice"
          v-bind="unitPriceAttrs"
          mode="currency"
          :currency="currencyCode"
          show-clear
          :min="0"
          :min-fraction-digits="2"
          :max-fraction-digits="2"
          :class="{ 'p-invalid': errors.unitPrice }"
          input-class="min-h-11 tabular-nums"
        />
        <small v-if="errors.unitPrice" class="text-red-400 dark:text-red-300">{{ errors.unitPrice }}</small>
        <div v-if="props.item" class="flex items-center justify-between gap-3 text-sm text-surface-500 dark:text-surface-400">
          <span>{{ t("Original price") }}</span>
          <span class="tabular-nums">{{ formatCurrency(String(props.item.original_unit_price)) }}</span>
        </div>
      </div>

      <div class="flex flex-col gap-1">
        <label for="pos-edit-quantity">{{ t("Quantity") }}</label>
        <InputNumber
          input-id="pos-edit-quantity"
          v-model="quantity"
          v-bind="quantityAttrs"
          :min="1"
          :max="maximumQuantity === null ? undefined : Math.max(1, maximumQuantity)"
          :min-fraction-digits="0"
          :max-fraction-digits="0"
          :class="{ 'p-invalid': errors.quantity }"
          show-buttons
          button-layout="horizontal"
          increment-button-icon="fa fa-plus"
          decrement-button-icon="fa fa-minus"
          fluid
          input-class="min-h-11 w-full tabular-nums"
          decrement-button-class="!min-h-11 !min-w-11"
          increment-button-class="!min-h-11 !min-w-11"
        />
        <small v-if="errors.quantity" class="text-red-400 dark:text-red-300">{{ errors.quantity }}</small>
        <small v-else-if="maximumQuantity !== null" class="text-surface-500 dark:text-surface-400">{{ t("Max") }}: {{ maximumQuantity }}</small>
      </div>

      <div class="flex flex-col gap-1">
        <label for="pos-line-discount">{{ t("Line discount") }}</label>
        <InputNumber
          input-id="pos-line-discount"
          v-model="lineDiscount"
          v-bind="lineDiscountAttrs"
          mode="currency"
          :currency="currencyCode"
          show-clear
          :min="0"
          :min-fraction-digits="2"
          :max-fraction-digits="2"
          :class="{ 'p-invalid': errors.lineDiscount }"
          input-class="min-h-11 tabular-nums"
        />
        <small v-if="errors.lineDiscount" class="text-red-400 dark:text-red-300">{{ errors.lineDiscount }}</small>
      </div>

      <div class="flex items-baseline justify-between gap-3 border-t border-surface-200 pt-4 dark:border-surface-700" aria-live="polite">
        <span class="font-semibold">{{ t("Line total") }}</span>
        <span class="text-2xl font-bold tabular-nums">{{ formatCurrency(String(previewTotal)) }}</span>
      </div>
    </form>

    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" @click="visible = false" />
      <Button v-if="priceModified" :label="t('Reset price')" severity="secondary" outlined @click="resetPrice" />
      <Button :label="t('Apply')" @click="applyPrice" />
    </template>
  </Dialog>
</template>
