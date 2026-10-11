<script setup lang="ts">
import { Dialog, Button, InputNumber, DataTable, Column } from "primevue";
import { useI18n } from "vue-i18n";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { StockTransferItem } from "@/Types/stock-transfer-types";

const props = defineProps<{
  visible: boolean;
  transferId: number;
  targetStatus: string;
  items?: StockTransferItem[];
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const { t } = useI18n();
const loading = ref(false);
const localItems = ref<Array<{ id: number; quantity: number }>>([]);

const needsQuantities = computed(() => props.targetStatus === "picked" || props.targetStatus === "received");

const statusLabels: Record<string, string> = {
  picked: "Mark as Picked",
  in_transit: "Mark as In Transit",
  received: "Mark as Received",
  completed: "Complete Transfer",
};

function initItems() {
  if (props.items && needsQuantities.value) {
    localItems.value = props.items.map((item) => ({
      id: item.id,
      quantity: item.quantity_requested,
    }));
  }
}

function close() {
  emit("update:visible", false);
  localItems.value = [];
}

function productName(index: number): string {
  return props.items?.[index]?.product_variant?.product?.name ?? "---";
}

function variantLabel(index: number): string | null {
  const variant = props.items?.[index]?.product_variant;
  return variant?.name ?? variant?.identifier ?? null;
}

function unitName(index: number): string | null {
  return props.items?.[index]?.product_variant?.product?.measurement_unit?.name ?? null;
}

function confirm() {
  loading.value = true;

  const payload: {
    status: string;
    items?: Array<{ id: number; quantity_sent?: number; quantity_received?: number }>;
  } = {
    status: props.targetStatus,
  };

  if (needsQuantities.value) {
    payload.items = localItems.value.map((item) =>
      props.targetStatus === "picked" ? { id: item.id, quantity_sent: item.quantity } : { id: item.id, quantity_received: item.quantity },
    );
  }

  router.patch(route("stock-transfers.update-status", { stockTransfer: props.transferId }), payload, {
    preserveScroll: true,
    onSuccess: () => close(),
    onFinish: () => {
      loading.value = false;
    },
  });
}
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="t(statusLabels[targetStatus] ?? targetStatus)"
    :style="{ width: needsQuantities ? '600px' : '450px' }"
    @update:visible="emit('update:visible', $event)"
    @show="initItems"
  >
    <div class="flex flex-col gap-4">
      <p v-if="!needsQuantities" class="m-0 text-surface-600 dark:text-surface-400">
        {{ t("Are you sure you want to advance this transfer?") }}
      </p>

      <template v-if="needsQuantities && items">
        <DataTable :value="localItems" size="small">
          <Column :header="t('Item')" style="min-width: 200px">
            <template #body="{ index }">
              {{ productName(index) }}
              <div v-if="variantLabel(index)" class="text-xs text-surface-500">{{ variantLabel(index) }}</div>
            </template>
          </Column>
          <Column :header="t('Quantity Requested')" style="width: 140px">
            <template #body="{ index }">
              {{ items[index]?.quantity_requested ?? 0 }} {{ unitName(index) }}
            </template>
          </Column>
          <Column :header="t(targetStatus === 'picked' ? 'Quantity Sent' : 'Quantity Received')" style="width: 160px">
            <template #body="{ index }">
              <div class="flex flex-col gap-1">
                <InputNumber v-model="localItems[index].quantity" :min="0" class="w-full" :input-style="{ width: '100%' }" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </div>

    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" @click="close" />
      <Button :label="t(statusLabels[targetStatus] ?? 'Confirm')" :loading="loading" @click="confirm" />
    </template>
  </Dialog>
</template>
