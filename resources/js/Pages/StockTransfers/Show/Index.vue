<script setup lang="ts">
import { Card, Button, DataTable, Column } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref, computed } from "vue";
import type { StockTransferResponse } from "@/Types/stock-transfer-types";
import TransferStatusTag from "./Components/TransferStatusTag.vue";
import TransferStatusStepper from "./Components/TransferStatusStepper.vue";
import AdvanceTransferModal from "./Components/AdvanceTransferModal.vue";
import CancelTransferModal from "./Components/CancelTransferModal.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  transfer: StockTransferResponse;
}>();

const { t } = useI18n();

const advanceModalVisible = ref(false);
const cancelModalVisible = ref(false);
const targetStatus = ref("");

const nextAction = computed<{ label: string; status: string } | null>(() => {
  const map: Record<string, { label: string; status: string }> = {
    requested: { label: "Mark as Picked", status: "picked" },
    picked: { label: "Mark as In Transit", status: "in_transit" },
    in_transit: { label: "Mark as Received", status: "received" },
    received: { label: "Complete Transfer", status: "completed" },
  };
  return map[props.transfer.status] ?? null;
});

const canCancel = computed(() => !["completed", "cancelled"].includes(props.transfer.status));

function openAdvanceModal() {
  if (nextAction.value) {
    targetStatus.value = nextAction.value.status;
    advanceModalVisible.value = true;
  }
}

function formatDateTime(date: string | null): string {
  if (!date) return "---";
  return new Date(date).toLocaleString();
}

function discrepancy(item: { quantity_sent: number; quantity_received: number }): number {
  return item.quantity_sent - item.quantity_received;
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded @click="router.visit(route('stock-transfers'))" />
        <h2 class="text-2xl font-bold m-0">{{ t("Transfer Details") }}</h2>
        <TransferStatusTag :status="transfer.status" />
      </div>
      <div v-if="canCancel || nextAction" class="flex gap-2">
        <Button
          v-if="nextAction"
          v-can="'stock_transfer.edit'"
          :label="t(nextAction.label)"
          icon="fa fa-arrow-right"
          @click="openAdvanceModal"
        />
        <Button
          v-if="canCancel"
          v-can="'stock_transfer.cancel'"
          :label="t('Cancel Transfer')"
          icon="fa fa-ban"
          severity="danger"
          outlined
          @click="cancelModalVisible = true"
        />
      </div>
    </div>

    <TransferStatusStepper :current-status="transfer.status" :cancelled="transfer.status === 'cancelled'" class="mb-4" />

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 lg:col-span-8">
        <Card>
          <template #title>{{ t("Details") }}</template>
          <template #content>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-sm text-surface-500 block">{{ t("From Store") }}</span>
                <span class="font-medium">{{ transfer.from_store?.name ?? "---" }}</span>
                <span class="text-sm text-surface-400 ml-1">{{ transfer.from_store?.code }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("To Store") }}</span>
                <span class="font-medium">{{ transfer.to_store?.name ?? "---" }}</span>
                <span class="text-sm text-surface-400 ml-1">{{ transfer.to_store?.code }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Requested By") }}</span>
                <span class="font-medium">{{ transfer.requested_by_user?.full_name ?? "---" }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Created At") }}</span>
                <span class="font-medium">{{ formatDateTime(transfer.created_at) }}</span>
              </div>
              <div v-if="transfer.completed_at">
                <span class="text-sm text-surface-500 block">{{ t("Completed At") }}</span>
                <span class="font-medium">{{ formatDateTime(transfer.completed_at) }}</span>
              </div>
              <div v-if="transfer.cancelled_at">
                <span class="text-sm text-surface-500 block">{{ t("Cancelled At") }}</span>
                <span class="font-medium text-red-500">{{ formatDateTime(transfer.cancelled_at) }}</span>
              </div>
              <div v-if="transfer.notes" class="col-span-2">
                <span class="text-sm text-surface-500 block">{{ t("Notes") }}</span>
                <span class="font-medium">{{ transfer.notes }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="col-span-12 lg:col-span-4">
        <Card>
          <template #title>{{ t("Summary") }}</template>
          <template #content>
            <div class="flex flex-col gap-3">
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Total Items") }}</span>
                <span class="font-bold">{{ transfer.items?.length ?? 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Total Requested") }}</span>
                <span class="font-bold">{{ transfer.items?.reduce((sum, i) => sum + i.quantity_requested, 0) ?? 0 }}</span>
              </div>
              <div v-if="['in_transit', 'received', 'completed'].includes(transfer.status)" class="flex justify-between">
                <span class="text-surface-500">{{ t("Total Sent") }}</span>
                <span class="font-bold">{{ transfer.items?.reduce((sum, i) => sum + i.quantity_sent, 0) ?? 0 }}</span>
              </div>
              <div v-if="['received', 'completed'].includes(transfer.status)" class="flex justify-between">
                <span class="text-surface-500">{{ t("Total Received") }}</span>
                <span class="font-bold">{{ transfer.items?.reduce((sum, i) => sum + i.quantity_received, 0) ?? 0 }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <Card class="mt-4">
      <template #title>{{ t("Items") }}</template>
      <template #content>
        <DataTable :value="transfer.items ?? []" size="small">
          <template #empty>
            {{ t("No items") }}
          </template>

          <Column :header="t('Product')">
            <template #body="{ data }">
              <span class="font-medium">{{ data.product_variant?.product_name ?? "---" }}</span>
              <div class="text-sm text-surface-500">{{ data.product_variant?.label }}</div>
            </template>
          </Column>

          <Column :header="t('Quantity Requested')" style="width: 150px">
            <template #body="{ data }">
              {{ data.quantity_requested }}
            </template>
          </Column>

          <Column
            v-if="['in_transit', 'received', 'completed', 'cancelled'].includes(transfer.status)"
            :header="t('Quantity Sent')"
            style="width: 140px"
          >
            <template #body="{ data }">
              {{ data.quantity_sent }}
            </template>
          </Column>

          <Column
            v-if="['received', 'completed', 'cancelled'].includes(transfer.status)"
            :header="t('Quantity Received')"
            style="width: 160px"
          >
            <template #body="{ data }">
              <span>{{ data.quantity_received }}</span>
              <span v-if="discrepancy(data) > 0" class="text-red-500 text-xs ml-2">({{ discrepancy(data) }} missing)</span>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <AdvanceTransferModal
      v-model:visible="advanceModalVisible"
      :transfer-id="transfer.id"
      :target-status="targetStatus"
      :items="transfer.items"
    />

    <CancelTransferModal v-model:visible="cancelModalVisible" :transfer-id="transfer.id" />
  </div>
</template>
