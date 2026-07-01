<script setup lang="ts">
import { Dialog, Button, Textarea, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import { ref, computed } from "vue";

const props = defineProps<{
  visible: boolean;
  purchaseOrderId: number;
  targetStatus: string;
  isFullyReceived?: boolean;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const { t } = useI18n();
const toast = useToast();

const completionNotes = ref("");

const confirmMessages: Record<string, string> = {
  awaiting_approval: "Are you sure you want to submit this purchase order for approval?",
  approved: "Are you sure you want to approve this purchase order?",
  sent: "Are you sure you want to mark this purchase order as sent?",
  received: "Are you sure you want to mark this purchase order as received?",
};

const routeNames: Record<string, string> = {
  awaiting_approval: "purchase-orders.submit",
  approved: "purchase-orders.approve",
  sent: "purchase-orders.send",
  received: "purchase-orders.approve",
};

const isReceivedTransition = computed(() => props.targetStatus === "received");
const requiresCompletionNotes = computed(() => isReceivedTransition.value && !props.isFullyReceived);
const canConfirm = computed(() => {
  if (!isReceivedTransition.value) return true;
  if (requiresCompletionNotes.value) return completionNotes.value.trim().length > 0;
  return true;
});

const confirmMessage = computed(() => confirmMessages[props.targetStatus] ?? "Are you sure you want to change the status?");
const headerLabel = computed(() => {
  const map: Record<string, string> = {
    awaiting_approval: "Submit for Approval",
    approved: "Approve Purchase Order",
    sent: "Mark as Sent",
    received: "Mark as Received",
  };
  return map[props.targetStatus] ?? "Change Status";
});

function confirmAction() {
  const routeName = routeNames[props.targetStatus];
  if (!routeName) return;

  const body: Record<string, string> = {};
  if (props.targetStatus === "approved") {
    body.status = "approved";
  } else if (props.targetStatus === "sent") {
    body.status = "sent";
  } else if (props.targetStatus === "received") {
    body.status = "received";
  }

  if (isReceivedTransition.value) {
    body.completion_notes = completionNotes.value.trim() || "";
  }

  router.patch(route(routeName, { purchaseOrder: props.purchaseOrderId }), body, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Purchase order status updated successfully"), life: 3000 });
      completionNotes.value = "";
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
    <div v-if="isReceivedTransition" class="flex flex-col gap-2 mt-3">
      <label v-if="requiresCompletionNotes" for="completion-notes" class="font-medium">
        {{ t("Completion Notes") }}
        <span class="text-red-500">*</span>
      </label>
      <label v-else for="completion-notes" class="font-medium">
        {{ t("Completion Notes") }}
      </label>
      <Textarea
        id="completion-notes"
        v-model="completionNotes"
        rows="3"
        :placeholder="
          requiresCompletionNotes
            ? t('Reason for closing the purchase order before all items have been received')
            : t('Optional notes about completing this purchase order')
        "
      />
      <small v-if="requiresCompletionNotes" class="text-surface-500">
        {{ t("Reason for closing the purchase order before all items have been received") }}
      </small>
    </div>
    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" @click="emit('update:visible', false)" />
      <Button :label="t('Confirm')" :disabled="!canConfirm" @click="confirmAction" />
    </template>
  </Dialog>
</template>
