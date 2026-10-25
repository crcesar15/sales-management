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
}>();

const emit = defineEmits<{
  (e: "updatePrice", itemId: string, price: number): void;
}>();

const visible = defineModel<boolean>("visible", { default: false });
const { t } = useI18n();
const { currencyCode, formatCurrency } = useCurrencyFormatter();
const schema = toTypedSchema(object({ unitPrice: number().required().min(0) }));
const { handleSubmit, defineField, errors, resetForm } = useForm({
  validationSchema: schema,
  initialValues: { unitPrice: 0 },
});
const [unitPrice, unitPriceAttrs] = defineField("unitPrice");
const priceModified = computed(
  () => props.item !== null && Number(props.item.unit_price.toFixed(2)) !== Number(props.item.original_unit_price.toFixed(2)),
);

watch(visible, (isVisible) => {
  if (!isVisible || props.item === null) return;

  resetForm({ values: { unitPrice: props.item.unit_price } });
  void nextTick(() => document.getElementById("pos-unit-price")?.focus());
});

const applyPrice = handleSubmit(({ unitPrice: price }) => {
  if (props.item === null) return;

  emit("updatePrice", props.item.id, Number(price.toFixed(2)));
  visible.value = false;
});

function resetPrice(): void {
  if (props.item === null) return;

  resetForm({ values: { unitPrice: props.item.original_unit_price } });
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Edit price')"
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
          id="pos-unit-price"
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
          autofocus
        />
        <small v-if="errors.unitPrice" class="text-red-400 dark:text-red-300">{{ errors.unitPrice }}</small>
      </div>

      <div v-if="props.item" class="flex items-center justify-between gap-3 text-sm text-surface-500 dark:text-surface-400">
        <span>{{ t("Original price") }}</span>
        <span class="tabular-nums">{{ formatCurrency(String(props.item.original_unit_price)) }}</span>
      </div>
    </form>

    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" @click="visible = false" />
      <Button v-if="priceModified" :label="t('Reset price')" severity="secondary" outlined @click="resetPrice" />
      <Button :label="t('Apply')" @click="applyPrice" />
    </template>
  </Dialog>
</template>
