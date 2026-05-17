<script setup lang="ts">
import { Card, Divider, Button } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    subTotal: number | null;
    discount: number | null;
    total: number | null;
    notes?: string | null;
    canCancel?: boolean;
  }>(),
  { canCancel: false },
);

const emit = defineEmits<{
  (e: "edit"): void;
  (e: "cancel"): void;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const discountPercentage = computed(() => {
  if (!props.discount || !props.subTotal || props.subTotal === 0) return null;
  return ((props.discount / props.subTotal) * 100).toFixed(1);
});
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
        <div v-if="discount && discount > 0" class="flex justify-between items-center">
          <span class="text-surface-500">{{ t("Discount") }}</span>
          <div class="text-right">
            <span class="text-red-500 dark:text-red-400 font-medium">-{{ formatCurrency(String(discount)) }}</span>
            <span v-if="discountPercentage" class="text-surface-400 text-sm ml-1">({{ discountPercentage }}%)</span>
          </div>
        </div>
        <Divider class="!my-1" />
        <div class="flex justify-between">
          <span class="font-bold">{{ t("Total") }}</span>
          <span class="font-bold text-lg">{{ formatCurrency(String(total ?? 0)) }}</span>
        </div>
        <template v-if="notes">
          <Divider class="!my-1" />
          <div>
            <span class="text-surface-500 text-sm block mb-1">{{ t("Notes") }}</span>
            <p class="text-sm text-surface-700 dark:text-surface-300 m-0 whitespace-pre-line">{{ notes }}</p>
          </div>
        </template>
        <Divider class="!my-1" />
        <div class="flex gap-2">
          <Button v-can="'purchase_order.edit'" icon="fa fa-pen" :label="t('Edit')" class="flex-1" @click="emit('edit')" />
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
      </div>
    </template>
  </Card>
</template>
