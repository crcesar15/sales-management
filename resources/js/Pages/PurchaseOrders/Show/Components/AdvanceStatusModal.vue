<script setup lang="ts">
import { Dialog, Button, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import { computed } from "vue";

const props = defineProps<{
  visible: boolean;
  purchaseOrderId: number;
  targetStatus: string;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const { t } = useI18n();
const toast = useToast();

const confirmMessages: Record<string, string> = {
  awaiting_approval: "Are you sure you want to submit this purchase order for approval?",
  approved: "Are you sure you want to approve this purchase order?",
  sent: "Are you sure you want to mark this purchase order as sent?",
  paid: "Are you sure you want to mark this purchase order as paid?",
};

const routeNames: Record<string, string> = {
  awaiting_approval: "purchase-orders.submit",
  approved: "purchase-orders.approve",
  sent: "purchase-orders.send",
  paid: "purchase-orders.pay",
};

const confirmMessage = computed(() => confirmMessages[props.targetStatus] ?? "Are you sure you want to change the status?");
const headerLabel = computed(() => {
  const map: Record<string, string> = {
    awaiting_approval: "Submit for Approval",
    approved: "Approve Purchase Order",
    sent: "Mark as Sent",
    paid: "Mark as Paid",
  };
  return map[props.targetStatus] ?? "Change Status";
});

function confirmAction() {
  const routeName = routeNames[props.targetStatus];
  if (!routeName) return;

  const body: Record<string, string> = {};
  if (props.targetStatus === "approved") {
    body.status = "approved";
  } else if (props.targetStatus === "paid") {
    body.status = "paid";
  } else if (props.targetStatus === "sent") {
    body.status = "sent";
  }

  router.patch(route(routeName, { purchaseOrder: props.purchaseOrderId }), body, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Purchase order status updated successfully"), life: 3000 });
      emit("update:visible", false);
    },
    onError: () => {
      toast.add({ severity: "error", summary: t("Error"), detail: t("Could not update purchase order status"), life: 3000 });
    },
  });
}
</script>

<template>
  <Dialog :visible="visible" modal :header="t(headerLabel)" :style="{ width: '450px' }" @update:visible="emit('update:visible', $event)">
    <p>{{ t(confirmMessage) }}</p>
    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" @click="emit('update:visible', false)" />
      <Button :label="t('Confirm')" @click="confirmAction" />
    </template>
  </Dialog>
</template>
