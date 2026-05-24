<script setup lang="ts">
import { Card, Button, Message, Tag } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { BatchResponse } from "@/Types/batch-types";
import BatchStatusTag from "./Components/BatchStatusTag.vue";
import ExpiryBadge from "./Components/ExpiryBadge.vue";
import CloseBatchModal from "./Components/CloseBatchModal.vue";
import EditBatchModal from "./Components/EditBatchModal.vue";
import QuantityDoughnut from "./Components/QuantityDoughnut.vue";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { ref, computed } from "vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  batch: BatchResponse;
}>();

const { t } = useI18n();
const { formatDate, formatDatetime } = useDatetimeFormatter();
const closeModalVisible = ref(false);
const editModalVisible = ref(false);

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
        <Button icon="fa fa-arrow-left" text rounded :aria-label="t('Back')" @click="router.visit(route('batches'))" />
        <h2 class="text-2xl font-bold m-0">{{ t("Batch Details") }}</h2>
        <BatchStatusTag :status="batch.status" />
      </div>
      <div class="flex items-center gap-2">
        <template v-if="batch.status !== 'closed'">
          <Button
            v-can="'batch.edit'"
            :label="t('Edit')"
            icon="fa-solid fa-pen"
            @click="editModalVisible = true"
          />
        </template>
        <Tag
          v-else
          :value="t('Locked')"
          icon="fa-solid fa-lock"
          severity="info"
        />
      </div>
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 lg:col-span-8">
        <Card>
          <template #title>{{ t("Details") }}</template>
          <template #content>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-surface-500 block">{{ t("Product") }}</span>
                <span class="font-medium">{{ batch.product?.name ?? "---" }}</span>
              </div>
              <div>
                <span class="text-surface-500 block">{{ t("Brand") }}</span>
                <span class="font-medium">{{ batch.product?.brand_name ?? "---" }}</span>
              </div>
              <div>
                <span class="text-surface-500 block">{{ t("Product Variant") }}</span>
                <span class="font-medium">{{ batch.product_variant?.label ?? "---" }}</span>
              </div>
              <div>
                <span class="text-surface-500 block">{{ t("Store") }}</span>
                <span class="font-medium">{{ batch.store?.name ?? "---" }}</span>
              </div>
              <div>
                <span class="text-surface-500 block">{{ t("Batch Identifier") }}</span>
                <span class="font-medium">{{ batch.batch_identifier ?? "---" }}</span>
              </div>
              <div v-if="batch.reception_order">
                <span class="text-surface-500 block">{{ t("Reception Order") }}</span>
                <span class="font-medium">
                  <a
                    class="text-primary-500 hover:underline cursor-pointer"
                    @click="router.visit(route('reception-orders.show', { receptionOrder: batch.reception_order.id }))"
                  >
                    #{{ batch.reception_order.id }}
                  </a>
                  <span v-if="batch.reception_order.reception_date" class="text-surface-500">
                    — {{ formatDate(batch.reception_order.reception_date) }}
                  </span>
                </span>
              </div>
              <div>
                <span class="text-surface-500 block">{{ t("Expiry Date") }}</span>
                <div class="flex items-center gap-2">
                  <span class="font-medium">{{ formatDate(batch.expiry_date) }}</span>
                  <ExpiryBadge v-if="batch.expiry_status" :status="batch.expiry_status" />
                </div>
              </div>
              <div>
                <span class="text-surface-500 block">{{ t("Created At") }}</span>
                <span class="font-medium">{{ formatDatetime(batch.created_at) }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Message v-if="batch.status === 'closed'" severity="secondary" icon="fa-solid fa-lock" class="mb-3">
          {{ t("This batch is permanently locked. No further sales or edits are allowed.") }}
        </Message>
      </div>
      <div class="col-span-12 lg:col-span-4">
        <Card>
          <template #title>{{ t("Quantity Breakdown") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <div>
                <div class="flex justify-between mb-1">
                  <span>{{ t("Initial Quantity") }}</span>
                  <span class="font-bold">{{ batch.initial_quantity }}</span>
                </div>
              </div>

              <QuantityDoughnut
                :sold="batch.sold_quantity"
                :remaining="batch.remaining_quantity"
                :missing="batch.missing_quantity"
                :initial="batch.initial_quantity"
              />

              <div class="flex flex-col gap-2">
                <div class="flex justify-between items-center">
                  <div class="flex items-center gap-2">
                    <i class="fa-solid fa-cart-shopping text-blue-500 w-4 text-center" />
                    <span class="w-3 h-3 rounded-full bg-blue-500 inline-block" />
                    <span>{{ t("Sold Quantity") }}</span>
                  </div>
                  <span class="font-medium">{{ batch.sold_quantity }} ({{ soldPercent }}%)</span>
                </div>
                <div class="flex justify-between items-center">
                  <div class="flex items-center gap-2">
                    <i class="fa-solid fa-box text-green-500 w-4 text-center" />
                    <span class="w-3 h-3 rounded-full bg-green-500 inline-block" />
                    <span>{{ t("Remaining Quantity") }}</span>
                  </div>
                  <span class="font-medium">{{ batch.remaining_quantity }} ({{ remainingPercent }}%)</span>
                </div>
                <div class="flex justify-between items-center">
                  <div class="flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 w-4 text-center" />
                    <span class="w-3 h-3 rounded-full bg-red-500 inline-block" />
                    <span>{{ t("Missing Quantity") }}</span>
                  </div>
                  <span class="font-medium">{{ batch.missing_quantity }} ({{ missingPercent }}%)</span>
                </div>
              </div>
            </div>
          </template>
        </Card>
        <div class="mt-4 flex justify-end gap-2">
            <template v-if="batch.status !== 'closed'">
              <Button
                :label="t('Close Batch')"
                icon="fa-solid fa-xmark"
                class="block w-full"
                @click="closeModalVisible = true"
              />
            </template>
            <Button
              v-if="batch.product_variant?.id"
              :label="t('View Variant')"
              icon="fa-solid fa-arrow-up-right-from-square"
              class="block w-full"
              outlined
              @click="router.visit(route('inventory.variants.show', { variant: batch.product_variant.id }))"
            />
        </div>
      </div>
    </div>

    <CloseBatchModal
      v-model:visible="closeModalVisible"
      :batch-id="batch.id"
    />

    <EditBatchModal
      v-model:visible="editModalVisible"
      :batch-id="batch.id"
      :batch-identifier="batch.batch_identifier"
      :expiry-date="batch.expiry_date"
    />
  </div>
</template>
