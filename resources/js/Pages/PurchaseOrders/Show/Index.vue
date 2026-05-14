<script setup lang="ts">
import { Card, Button, DataTable, Column } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref } from "vue";
import type { PurchaseOrderResponse } from "@/Types/purchase-order-types";
import POStatusBadge from "../Components/POStatusBadge.vue";
import POStatusStepper from "../Components/POStatusStepper.vue";
import POActionButtons from "../Components/POActionButtons.vue";
import POTotalsPanel from "../Components/POTotalsPanel.vue";
import AdvanceStatusModal from "./Components/AdvanceStatusModal.vue";
import CancelPOModal from "./Components/CancelPOModal.vue";

defineOptions({ layout: AppLayout });

defineProps<{
  purchaseOrder: PurchaseOrderResponse;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const advanceModalVisible = ref(false);
const cancelModalVisible = ref(false);
const targetStatus = ref("");

function openAdvanceModal(status: string) {
  targetStatus.value = status;
  advanceModalVisible.value = true;
}

function formatDateTime(date: string | null): string {
  if (!date) return "---";
  return useDatetimeFormatter(date);
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded @click="router.visit(route('purchase-orders'))" />
        <h2 class="text-2xl font-bold m-0">{{ t("Purchase Order") }} #{{ purchaseOrder.id }}</h2>
        <POStatusBadge :status="purchaseOrder.status" />
      </div>
      <POActionButtons
        :status="purchaseOrder.status"
        @advance="openAdvanceModal"
        @cancel="cancelModalVisible = true"
      />
    </div>

    <POStatusStepper
      :current-status="purchaseOrder.status"
      :cancelled="purchaseOrder.status === 'cancelled'"
      class="mb-4"
    />

    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12 lg:col-span-8">
        <Card class="mb-4">
          <template #title>{{ t("Details") }}</template>
          <template #content>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Vendor") }}</span>
                <span class="font-medium">{{ purchaseOrder.vendor?.fullname ?? "---" }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Created By") }}</span>
                <span class="font-medium">{{ purchaseOrder.user?.full_name ?? "---" }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Order Date") }}</span>
                <span class="font-medium">{{ purchaseOrder.order_date ?? "---" }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Expected Arrival Date") }}</span>
                <span class="font-medium">{{ purchaseOrder.expected_arrival_date ?? "---" }}</span>
              </div>
              <div>
                <span class="text-sm text-surface-500 block">{{ t("Created At") }}</span>
                <span class="font-medium">{{ formatDateTime(purchaseOrder.created_at) }}</span>
              </div>
              <div v-if="purchaseOrder.notes" class="col-span-2">
                <span class="text-sm text-surface-500 block">{{ t("Notes") }}</span>
                <span class="font-medium">{{ purchaseOrder.notes }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Card>
          <template #title>{{ t("Line Items") }}</template>
          <template #content>
            <DataTable :value="purchaseOrder.line_items ?? []" size="small">
              <template #empty>
                {{ t("No items") }}
              </template>
              <Column :header="t('Product')">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.product_variant?.product?.name ?? "---" }}</span>
                  <div class="text-sm text-surface-500">{{ data.product_variant?.name ?? data.product_variant?.identifier ?? "---" }}</div>
                </template>
              </Column>
              <Column :header="t('Quantity')" style="width: 120px">
                <template #body="{ data }">
                  {{ data.quantity }}
                </template>
              </Column>
              <Column :header="t('Unit Price')" style="width: 140px">
                <template #body="{ data }">
                  {{ formatCurrency(String(data.price)) }}
                </template>
              </Column>
              <Column :header="t('Line Total')" style="width: 140px">
                <template #body="{ data }">
                  <span class="font-medium">{{ formatCurrency(String(data.total)) }}</span>
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>

      <div class="col-span-12 lg:col-span-4">
        <POTotalsPanel
          :sub-total="purchaseOrder.sub_total ?? 0"
          :discount="purchaseOrder.discount ?? 0"
          :total="purchaseOrder.total ?? 0"
        />
      </div>
    </div>

    <AdvanceStatusModal
      v-model:visible="advanceModalVisible"
      :purchase-order-id="purchaseOrder.id"
      :target-status="targetStatus"
    />

    <CancelPOModal
      v-model:visible="cancelModalVisible"
      :purchase-order-id="purchaseOrder.id"
    />
  </div>
</template>