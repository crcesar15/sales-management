<script setup lang="ts">
import { Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import type { SalesOrderStatus } from "@/Types/sales-order-types";

const props = defineProps<{ status: SalesOrderStatus }>();
const { t } = useI18n();

const statusMap: Record<string, { label: string; severity: "secondary" | "info" | "success" | "warn" | "danger" }> = {
  draft: { label: "Draft", severity: "info" },
  validated: { label: "Validated", severity: "info" },
  fulfilled: { label: "Fulfilled", severity: "warn" },
  completed: { label: "Completed", severity: "success" },
  cancelled: { label: "Cancelled", severity: "danger" },
};

const config = computed(() => statusMap[props.status] ?? { label: props.status, severity: "info" as const });
</script>

<template>
  <Tag :value="t(config.label)" :severity="config.severity" class="text-lg" />
</template>
