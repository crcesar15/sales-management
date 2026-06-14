<script setup lang="ts">
import { Card, Divider } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { computed } from "vue";
import type { DiscountType } from "@/Types/sales-order-types";

const props = withDefaults(
  defineProps<{
    subTotal: number;
    discount: number;
    taxAmount: number;
    total: number;
    discountType?: DiscountType;
    discountValue?: number;
  }>(),
  { discountType: "flat", discountValue: 0 },
);

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const discountPercentage = computed(() => {
  if (props.discountType === "percentage" && props.discountValue > 0) {
    return props.discountValue.toFixed(1);
  }
  if (!props.discount || !props.subTotal || props.subTotal === 0) return null;
  return ((props.discount / props.subTotal) * 100).toFixed(1);
});
</script>

<template>
  <Card>
    <template #title>{{ t("Order Summary") }}</template>
    <template #content>
      <div class="flex flex-col gap-3">
        <div class="flex justify-between">
          <span class="text-surface-500">{{ t("Sub Total") }}</span>
          <span class="font-medium">{{ formatCurrency(String(subTotal)) }}</span>
        </div>
        <div v-if="discount > 0" class="flex justify-between items-center">
          <span class="text-surface-500">{{ t("Discount") }}</span>
          <div class="text-right">
            <span class="text-red-500 dark:text-red-400 font-medium">-{{ formatCurrency(String(discount)) }}</span>
            <span v-if="discountPercentage" class="text-surface-400 text-sm ml-1">({{ discountPercentage }}%)</span>
          </div>
        </div>
        <div class="flex justify-between">
          <span class="text-surface-500">{{ t("Tax Amount") }}</span>
          <span class="font-medium">{{ formatCurrency(String(taxAmount)) }}</span>
        </div>
        <Divider class="!my-1" />
        <div class="flex justify-between">
          <span class="font-bold">{{ t("Total") }}</span>
          <span class="font-bold text-lg">{{ formatCurrency(String(total)) }}</span>
        </div>
      </div>
    </template>
  </Card>
</template>