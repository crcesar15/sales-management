<script setup lang="ts">
import { Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";

const props = defineProps<{ status: string }>();
const { t } = useI18n();

const statusMap: Record<string, { label: string; severity: "success" | "info" | "secondary" }> = {
  queued: { label: "Queued", severity: "info" },
  active: { label: "Active", severity: "success" },
  closed: { label: "Closed", severity: "secondary" },
};

const config = computed(() => statusMap[props.status] ?? { label: props.status, severity: "info" as const });
</script>

<template>
  <Tag :value="t(config.label)" :severity="config.severity" />
</template>
