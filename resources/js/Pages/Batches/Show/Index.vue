<script setup lang="ts">
import { Card, Button, ProgressBar } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { BatchResponse } from "@/Types/batch-types";
import BatchStatusTag from "./Components/BatchStatusTag.vue";
import ExpiryBadge from "./Components/ExpiryBadge.vue";
import CloseBatchModal from "./Components/CloseBatchModal.vue";
import { ref, computed } from "vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  batch: BatchResponse;
}>();

const { t } = useI18n();
const closeModalVisible = ref(false);

function formatDate(date: string | null): string {
  if (!date) return "---";
  return new Date(date).toLocaleDateString();
}

const soldPercent = computed(() => {
  if (!props.batch.initial_quantity) return 0;
  return Math.round((props.batch.sold_quantity / props.batch.initial_quantity) * 100);
});

const remainingPercent = computed(() => {
  if (!props.batch.initial_quantity) return 0;
  return Math.round((props.batch.remaining_quantity / props.batch.initial_quantity) * 100);
});

const missingPercent = computed(() => {
  if (!props.batch.initial_quantity) return 0;
  return Math.round((props.batch.missing_quantity / props.batch.initial_quantity) * 100);
});
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded @click="router.visit(route('batches'))" />
        <h2 class="text-2xl font-bold m-0">{{ t("Batch Details") }}</h2>
        <BatchStatusTag :status="batch.status" />
      </div>
      <Button
        v-if="batch.status !== 'closed'"
        :label="t('Close Batch')"
        icon="fa-solid fa-xmark"
        severity="danger"
        outlined
        @click="closeModalVisible = true"
      />
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 lg:col-span-8">
        <Card>
          <template #title>{{ t("Details") }}</template>
          <template #content>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Product Variant") }}</span>
                <span class="font-medium">{{ batch.product_variant?.product_name }}</span>
                <div class="text-sm text-surface-500">{{ batch.product_variant?.label }}</div>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Store") }}</span>
                <span class="font-medium">{{ batch.store?.name ?? "---" }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Reception Order") }}</span>
                <span class="font-medium">
                  <template v-if="batch.reception_order">
                    #{{ batch.reception_order.id }}
                    <span v-if="batch.reception_order.reception_date" class="text-surface-500">
                      — {{ formatDate(batch.reception_order.reception_date) }}
                    </span>
                  </template>
                  <template v-else>#{{ batch.reception_order_id }}</template>
                </span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Expiry Date") }}</span>
                <div class="flex items-center gap-2">
                  <span class="font-medium">{{ formatDate(batch.expiry_date) }}</span>
                  <ExpiryBadge v-if="batch.expiry_status" :status="batch.expiry_status" />
                </div>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Created At") }}</span>
                <span class="font-medium">{{ formatDate(batch.created_at) }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="col-span-12 lg:col-span-4">
        <Card>
          <template #title>{{ t("Quantity Breakdown") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span>{{ t("Initial Quantity") }}</span>
                  <span class="font-bold">{{ batch.initial_quantity }}</span>
                </div>
              </div>

              <ProgressBar :value="soldPercent + remainingPercent + missingPercent" :show-value="false" style="height: 24px" />

              <div class="flex flex-col gap-2 text-sm">
                <div class="flex justify-between items-center">
                  <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-blue-500 inline-block" />
                    <span>{{ t("Sold Quantity") }}</span>
                  </div>
                  <span class="font-medium">{{ batch.sold_quantity }} ({{ soldPercent }}%)</span>
                </div>
                <div class="flex justify-between items-center">
                  <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-green-500 inline-block" />
                    <span>{{ t("Remaining Quantity") }}</span>
                  </div>
                  <span class="font-medium">{{ batch.remaining_quantity }} ({{ remainingPercent }}%)</span>
                </div>
                <div class="flex justify-between items-center">
                  <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500 inline-block" />
                    <span>{{ t("Missing Quantity") }}</span>
                  </div>
                  <span class="font-medium">{{ batch.missing_quantity }} ({{ missingPercent }}%)</span>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <CloseBatchModal
      v-model:visible="closeModalVisible"
      :batch-id="batch.id"
    />
  </div>
</template>
