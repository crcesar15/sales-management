<script setup lang="ts">
import { Tag } from "primevue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import type { SalesOrderPaymentStatus } from "@/Types/sales-order-types";

const props = defineProps<{ status: SalesOrderPaymentStatus }>();
const { t } = useI18n();

const config = computed(() => {
  const statuses: Record<SalesOrderPaymentStatus, { label: string; severity: "secondary" | "warn" | "success" }> = {
    pending: { label: "Pending", severity: "warn" },
    partially_paid: { label: "Partially Paid", severity: "secondary" },
    paid: { label: "Paid", severity: "success" },
  };

  return statuses[props.status];
});
</script>

<template>
  <Tag :value="t(config.label)" :severity="config.severity" />
</template>
