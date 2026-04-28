<script setup lang="ts">
import { Card, Button } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { computed } from "vue";
import type { StockAdjustmentResponse } from "@/Types/stock-adjustment-types";
import AdjustmentReasonTag from "./Components/AdjustmentReasonTag.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  adjustment: StockAdjustmentResponse;
}>();

const { t } = useI18n();

const quantityClass = computed(() =>
  props.adjustment.quantity_change < 0 ? "text-red-500 font-bold" : "text-green-600 font-bold",
);

const formattedQuantity = computed(() => {
  const val = props.adjustment.quantity_change;
  return val > 0 ? `+${val}` : `${val}`;
});

function formatDateTime(date: string | null): string {
  if (!date) return "---";
  return new Date(date).toLocaleString();
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded @click="router.visit(route('stock-adjustments'))" />
        <h2 class="text-2xl font-bold m-0">{{ t("Adjustment Details") }}</h2>
        <AdjustmentReasonTag :reason="adjustment.reason" />
      </div>
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 lg:col-span-8">
        <Card>
          <template #title>{{ t("Details") }}</template>
          <template #content>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Product") }}</span>
                <span class="font-medium">{{ adjustment.product_variant?.product?.name ?? "---" }}</span>
                <span class="text-sm text-surface-500 ml-1">{{ adjustment.product_variant?.name }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Brand") }}</span>
                <span class="font-medium">{{ adjustment.product_variant?.product?.brand ?? "---" }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Store") }}</span>
                <span class="font-medium">{{ adjustment.store?.name ?? "---" }}</span>
                <span class="text-sm text-surface-400 ml-1">{{ adjustment.store?.code }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("User") }}</span>
                <span class="font-medium">{{ adjustment.user?.full_name ?? "---" }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Quantity Change") }}</span>
                <span :class="quantityClass">{{ formattedQuantity }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Reason") }}</span>
                <AdjustmentReasonTag :reason="adjustment.reason" />
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Batch") }}</span>
                <span v-if="adjustment.batch" class="font-medium">#{{ adjustment.batch.id }}</span>
                <span v-else class="text-surface-400">---</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Created At") }}</span>
                <span class="font-medium">{{ formatDateTime(adjustment.created_at) }}</span>
              </div>
              <div v-if="adjustment.notes" class="col-span-2">
                <span class="text-sm text-surface-500 block">{{ t("Notes") }}</span>
                <span class="font-medium">{{ adjustment.notes }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="col-span-12 lg:col-span-4">
        <Card>
          <template #title>{{ t("Batch Info") }}</template>
          <template #content>
            <div v-if="adjustment.batch" class="flex flex-col gap-3">
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Batch ID") }}</span>
                <span class="font-bold">#{{ adjustment.batch.id }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Initial Quantity") }}</span>
                <span class="font-bold">{{ adjustment.batch.initial_quantity }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Remaining Quantity") }}</span>
                <span class="font-bold">{{ adjustment.batch.remaining_quantity }}</span>
              </div>
            </div>
            <div v-else class="text-surface-400 text-center py-4">
              {{ t("No batch associated") }}
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>
