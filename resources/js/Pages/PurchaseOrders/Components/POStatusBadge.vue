<script setup lang="ts">
import { Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import type { PurchaseOrderStatus } from "@/Types/purchase-order-types";

const props = defineProps<{ status: PurchaseOrderStatus }>();
const { t } = useI18n();

const statusMap: Record<
  string,
  { label: string; severity: "success" | "info" | "warn" | "secondary" | "danger" }
> = {
  draft: { label: "Draft", severity: "info" },
  awaiting_approval: { label: "Awaiting Approval", severity: "warn" },
  approved: { label: "Approved", severity: "success" },
  sent: { label: "Sent", severity: "info" },
  paid: { label: "Paid", severity: "success" },
  cancelled: { label: "Cancelled", severity: "danger" },
};

const config = computed(() => statusMap[props.status] ?? { label: props.status, severity: "info" as const });
</script>

<template>
  <Tag :value="t(config.label)" :severity="config.severity" />
</template>