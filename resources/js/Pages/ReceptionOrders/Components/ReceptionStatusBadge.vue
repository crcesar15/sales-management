<script setup lang="ts">
import { Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import type { ReceptionOrderStatus } from "@/Types/reception-order-types";

const props = defineProps<{ status: ReceptionOrderStatus }>();
const { t } = useI18n();

const statusMap: Record<
  string,
  { label: string; severity: "success" | "info" | "warn" | "secondary" | "danger" }
> = {
  pending: { label: "Pending", severity: "warn" },
  completed: { label: "Completed", severity: "success" },
  cancelled: { label: "Cancelled", severity: "danger" },
};

const config = computed(() => statusMap[props.status] ?? { label: props.status, severity: "info" as const });
</script>

<template>
  <Tag :value="t(config.label)" :severity="config.severity" />
</template>