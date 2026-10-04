<script setup lang="ts">
import { Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import type { PurchaseOrderStatus } from "@/Types/purchase-order-types";

const props = defineProps<{ status: PurchaseOrderStatus }>();
const { t } = useI18n();

const statusMap: Record<string, { label: string; severity: "success" | "info" | "primary" | "secondary" | "danger" }> = {
  draft: { label: "Draft", severity: "info" },
  approved: { label: "Approved", severity: "success" },
  closed: { label: "Closed", severity: "primary" },
  cancelled: { label: "Cancelled", severity: "danger" },
};

const config = computed(() => statusMap[props.status] ?? { label: props.status, severity: "info" as const });
</script>

<template>
  <Tag :value="t(config.label)" :severity="config.severity" />
</template>
