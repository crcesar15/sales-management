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
const quantityField = computed(() => (props.targetStatus === "picked" ? "quantity_sent" : "quantity_received"));

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

function confirm() {
  loading.value = true;

  const payload: Record<string, unknown> = {
    status: props.targetStatus,
  };

  if (needsQuantities.value) {
    payload.items = localItems.value.map((li) => ({
      id: li.id,
      [quantityField.value]: li.quantity,
    }));
  }

  router.patch(route("stock-transfers.update-status", { stock_transfer: props.transferId }), payload as any, {
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
              {{ items[index]?.product_variant?.product_name ?? "---" }}
              <div class="text-xs text-surface-500">{{ items[index]?.product_variant?.label }}</div>
            </template>
          </Column>
          <Column :header="t('Quantity Requested')" style="width: 140px">
            <template #body="{ index }">
              {{ items[index]?.quantity_requested ?? 0 }}
            </template>
          </Column>
          <Column :header="t(targetStatus === 'picked' ? 'Quantity Sent' : 'Quantity Received')" style="width: 160px">
            <template #body="{ index }">
              <InputNumber v-model="localItems[index].quantity" :min="0" class="w-full" :input-style="{ width: '100%' }" />
            </template>
          </Column>
        </DataTable>
      </template>
    </div>

    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" outlined @click="close" />
      <Button
        :label="t(statusLabels[targetStatus] ?? 'Confirm')"
        :loading="loading"
        @click="confirm"
      />
    </template>
  </Dialog>
</template>
