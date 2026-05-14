<script setup lang="ts">
import { Button } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import type { PurchaseOrderStatus } from "@/Types/purchase-order-types";

const props = defineProps<{
  status: PurchaseOrderStatus;
}>();

const emit = defineEmits<{
  (e: "advance", status: string): void;
  (e: "cancel"): void;
}>();

const { t } = useI18n();

const nextAction = computed<{ label: string; status: string } | null>(() => {
  const map: Record<string, { label: string; status: string }> = {
    draft: { label: "Submit for Approval", status: "awaiting_approval" },
    awaiting_approval: { label: "Approve", status: "approved" },
    approved: { label: "Mark as Sent", status: "sent" },
    sent: { label: "Mark as Paid", status: "paid" },
  };
  return map[props.status] ?? null;
});

const canCancel = computed(() => ["draft", "awaiting_approval", "approved"].includes(props.status));

const advancePermission = computed(() => {
  const map: Record<string, string> = {
    draft: "purchase_order.edit",
    awaiting_approval: "purchase_order.approve",
    approved: "purchase_order.edit",
    sent: "purchase_order.edit",
  };
  return map[props.status] ?? "";
});
</script>

<template>
  <div v-if="nextAction || canCancel" class="flex gap-2">
    <Button
      v-if="nextAction"
      v-can="advancePermission"
      :label="t(nextAction.label)"
      icon="fa fa-arrow-right"
      @click="emit('advance', nextAction.status)"
    />
    <Button
      v-if="canCancel"
      v-can="'purchase_order.edit'"
      :label="t('Cancel')"
      icon="fa fa-ban"
      severity="danger"
      outlined
      @click="emit('cancel')"
    />
  </div>
</template>