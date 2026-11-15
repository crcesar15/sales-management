<script setup lang="ts">
import { Card, Divider, Button } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { computed } from "vue";
import type { PurchaseDiscountType } from "@app-types/purchase-order-types";

const props = withDefaults(
  defineProps<{
    subTotal: number | null;
    discount: number | null;
    total: number | null;
    grossSubTotal?: number | null;
    lineDiscountTotal?: number | null;
    discountType?: PurchaseDiscountType;
    discountValue?: number | null;
    notes?: string | null;
    canEdit?: boolean;
    canCancel?: boolean;
    canMarkAsPaid?: boolean;
  }>(),
  { canEdit: true, canCancel: false, canMarkAsPaid: false },
);

const emit = defineEmits<{
  (e: "edit"): void;
  (e: "cancel"): void;
  (e: "mark-as-paid"): void;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const discountPercentage = computed(() => {
  return props.discountType === "percentage" ? Number(props.discountValue ?? 0).toFixed(2) : null;
});
</script>

<template>
  <Card>
    <template #title>{{ t("Summary") }}</template>
    <template #content>
      <div class="flex flex-col gap-3">
        <div class="flex flex-wrap justify-between gap-2">
          <span>{{ t("Gross subtotal") }}</span>
          <span class="ml-auto shrink-0 whitespace-nowrap tabular-nums">{{ formatCurrency(String(grossSubTotal ?? subTotal ?? 0)) }}</span>
        </div>
        <div class="flex flex-wrap justify-between gap-2">
          <span>{{ t("Item discounts") }}</span>
          <span class="ml-auto shrink-0 whitespace-nowrap font-medium tabular-nums text-red-500 dark:text-red-400">
            {{ formatCurrency(String(lineDiscountTotal ?? 0)) }}
          </span>
        </div>
        <div class="flex flex-wrap justify-between gap-2">
          <span class="text-surface-500">{{ t("Subtotal") }}</span>
          <span class="ml-auto shrink-0 whitespace-nowrap font-medium tabular-nums">{{ formatCurrency(String(subTotal ?? 0)) }}</span>
        </div>
        <div v-if="discount && discount > 0" class="flex flex-wrap items-start justify-between gap-2">
          <span class="text-surface-500">{{ t("PO discount") }}</span>
          <div class="ml-auto text-right">
            <span class="whitespace-nowrap text-red-500 dark:text-red-400 font-medium">-{{ formatCurrency(String(discount)) }}</span>
            <span v-if="discountPercentage" class="block text-surface-400 text-sm">({{ discountPercentage }}%)</span>
          </div>
        </div>
        <Divider class="!my-1" />
        <div class="flex flex-wrap justify-between gap-2">
          <span class="font-bold">{{ t("Total") }}</span>
          <span class="ml-auto shrink-0 whitespace-nowrap font-bold text-lg tabular-nums">{{ formatCurrency(String(total ?? 0)) }}</span>
        </div>
        <template v-if="notes">
          <Divider class="!my-1" />
          <div>
            <span class="text-surface-500 text-sm block mb-1">{{ t("Notes") }}</span>
            <p class="text-sm text-surface-700 dark:text-surface-300 m-0 whitespace-pre-line">{{ notes }}</p>
          </div>
        </template>
        <Divider class="!my-1" />
        <div class="flex flex-col gap-2">
          <div class="grid grid-cols-2 gap-2 lg:grid-cols-1 xl:grid-cols-2">
            <Button v-if="canEdit" v-can="'purchase_order.edit'" icon="fa fa-pen" :label="t('Edit')" class="flex-1" @click="emit('edit')" />
            <Button
              v-if="canCancel"
              v-can="'purchase_order.edit'"
              icon="fa fa-ban"
              :label="t('Cancel')"
              severity="secondary"
              class="flex-1"
              @click="emit('cancel')"
            />
          </div>
          <Button
            v-if="canMarkAsPaid"
            v-can="'purchase_order.edit'"
            icon="fa fa-dollar-sign"
            :label="t('Mark as Paid')"
            severity="success"
            class="w-full"
            @click="emit('mark-as-paid')"
          />
        </div>
      </div>
    </template>
  </Card>
</template>
