<script setup lang="ts">
import { Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import type { TransferStatus } from "@/Types/stock-transfer-types";

const props = defineProps<{ status: TransferStatus }>();
const { t } = useI18n();

const statusMap: Record<
  string,
  { label: string; severity: "success" | "info" | "warn" | "secondary" | "danger" }
> = {
  requested: { label: "Requested", severity: "info" },
  picked: { label: "Picked", severity: "info" },
  in_transit: { label: "In Transit", severity: "warn" },
  received: { label: "Received", severity: "success" },
  completed: { label: "Completed", severity: "success" },
  cancelled: { label: "Cancelled", severity: "danger" },
};

const config = computed(() => statusMap[props.status] ?? { label: props.status, severity: "info" as const });
</script>

<template>
  <Tag :value="t(config.label)" :severity="config.severity" />
</template>
