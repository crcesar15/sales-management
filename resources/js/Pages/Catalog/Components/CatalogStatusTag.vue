<script setup lang="ts">
import { Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";

const props = defineProps<{ status: "active" | "inactive" }>();
const { t } = useI18n();

const statusMap: Record<string, { label: string; severity: "success" | "warn" }> = {
  active: { label: "Active", severity: "success" },
  inactive: { label: "Inactive", severity: "warn" },
};

const config = computed(() => statusMap[props.status] ?? { label: props.status, severity: "warn" as const });
</script>

<template>
  <Tag :value="t(config.label)" :severity="config.severity" rounded />
</template>