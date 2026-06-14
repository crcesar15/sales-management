<script setup lang="ts">
import { Button, ConfirmDialog, useConfirm, useToast } from "primevue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { computed } from "vue";
import type { SalesOrderResponse, SalesOrderStatus } from "@/Types/sales-order-types";

const props = defineProps<{
  order: SalesOrderResponse;
}>();

const emit = defineEmits<{
  (e: "transitioned"): void;
}>();

const { t } = useI18n();
const confirm = useConfirm();
const toast = useToast();

// Status transition map (mirrors backend SalesOrderService)
const transitionMap: Record<SalesOrderStatus, { label: string; status: SalesOrderStatus; icon: string; severity: string }[]> = {
  draft: [
    { label: t("Mark as Sent"), status: "sent", icon: "fa fa-paper-plane", severity: "info" },
    { label: t("Mark as Paid"), status: "paid", icon: "fa fa-check-circle", severity: "success" },
    { label: t("Hold Order"), status: "held", icon: "fa fa-pause", severity: "warn" },
    { label: t("Cancel Order"), status: "cancelled", icon: "fa fa-ban", severity: "danger" },
  ],
  held: [
    { label: t("Resume Order"), status: "draft", icon: "fa fa-play", severity: "info" },
    { label: t("Cancel Order"), status: "cancelled", icon: "fa fa-ban", severity: "danger" },
  ],
  sent: [
    { label: t("Mark as Paid"), status: "paid", icon: "fa fa-check-circle", severity: "success" },
    { label: t("Cancel Order"), status: "cancelled", icon: "fa fa-ban", severity: "danger" },
  ],
  paid: [
    { label: t("Cancel Order"), status: "cancelled", icon: "fa fa-ban", severity: "danger" },
  ],
  cancelled: [],
};

const availableTransitions = computed(() => transitionMap[props.order.status] ?? []);

function transitionStatus(newStatus: SalesOrderStatus) {
  router.patch(
    route("sales-orders.status", props.order.id),
    { status: newStatus },
    {
      onSuccess: () => {
        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("Sales order status updated successfully"),
          life: 3000,
        });
        emit("transitioned");
      },
      onError: () => {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t("Could not update sales order status"),
          life: 3000,
        });
      },
    },
  );
}

function handleTransition(action: { label: string; status: SalesOrderStatus; severity: string }) {
  if (action.status === "cancelled") {
    confirm.require({
      message: t("Are you sure you want to cancel this sales order?"),
      header: t("Cancel Order"),
      icon: "fa fa-exclamation-triangle",
      rejectProps: { label: t("Cancel"), severity: "secondary", outlined: true },
      acceptProps: { label: t("Yes, cancel"), severity: "danger" },
      accept: () => transitionStatus("cancelled"),
    });
  } else if (action.status === "held") {
    confirm.require({
      message: t("Are you sure you want to hold this sales order?"),
      header: t("Hold Order"),
      icon: "fa fa-pause",
      rejectProps: { label: t("Cancel"), severity: "secondary", outlined: true },
      acceptProps: { label: t("Yes, hold"), severity: "warn" },
      accept: () => transitionStatus("held"),
    });
  } else if (action.status === "paid") {
    confirm.require({
      message: t("Are you sure you want to mark this sales order as paid?"),
      header: t("Mark as Paid"),
      icon: "fa fa-check-circle",
      rejectProps: { label: t("Cancel"), severity: "secondary", outlined: true },
      acceptProps: { label: t("Yes, mark as paid"), severity: "success" },
      accept: () => transitionStatus("paid"),
    });
  } else if (action.status === "sent") {
    confirm.require({
      message: t("Are you sure you want to mark this sales order as sent?"),
      header: t("Mark as Sent"),
      icon: "fa fa-paper-plane",
      rejectProps: { label: t("Cancel"), severity: "secondary", outlined: true },
      acceptProps: { label: t("Yes, mark as sent"), severity: "info" },
      accept: () => transitionStatus("sent"),
    });
  } else if (action.status === "draft") {
    // Resume: transition held → draft, then redirect to edit page
    confirm.require({
      message: t("Are you sure you want to resume this sales order?"),
      header: t("Resume Order"),
      icon: "fa fa-play",
      rejectProps: { label: t("Cancel"), severity: "secondary", outlined: true },
      acceptProps: { label: t("Yes, resume"), severity: "info" },
      accept: () => {
        router.patch(
          route("sales-orders.status", props.order.id),
          { status: "draft" },
          {
            onSuccess: () => {
              toast.add({
                severity: "success",
                summary: t("Success"),
                detail: t("Sales order status updated successfully"),
                life: 3000,
              });
              router.visit(route("sales-orders.edit", props.order.id));
            },
            onError: () => {
              toast.add({
                severity: "error",
                summary: t("Error"),
                detail: t("Could not update sales order status"),
                life: 3000,
              });
            },
          },
        );
      },
    });
  }
}
</script>

<template>
  <div v-if="availableTransitions.length > 0" class="flex flex-col gap-2">
    <Button
      v-for="action in availableTransitions"
      v-can="'sales.manage'"
      :key="action.status"
      :icon="action.icon"
      :label="action.label"
      :severity="(action.severity as any)"
      outlined
      class="w-full"
      @click="handleTransition(action)"
    />
    <ConfirmDialog />
  </div>
</template>