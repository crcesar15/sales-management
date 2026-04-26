<script setup lang="ts">
import { Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import type { ExpiryStatus } from "@/Types/batch-types";

const props = defineProps<{ status: ExpiryStatus }>();
const { t } = useI18n();

const statusMap: Record<string, { label: string; severity: "success" | "warn" | "danger" | "secondary" }> = {
  ok: { label: "OK", severity: "success" },
  expiring_soon: { label: "Expiring Soon", severity: "warn" },
  expired: { label: "Expired", severity: "danger" },
};

const config = computed(() => {
  if (!props.status) return { label: "---", severity: "secondary" as const };
  return statusMap[props.status] ?? { label: props.status, severity: "secondary" as const };
});
</script>

<template>
  <Tag :value="t(config.label)" :severity="config.severity" />
</template>
