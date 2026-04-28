<script setup lang="ts">
import { Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import type { AdjustmentReason } from "@/Types/stock-adjustment-types";

const props = defineProps<{ reason: AdjustmentReason }>();
const { t } = useI18n();

const reasonMap: Record<string, { label: string; severity: "success" | "info" | "warn" | "secondary" | "danger" }> = {
  physical_audit: { label: "Physical Audit", severity: "info" },
  robbery: { label: "Robbery", severity: "danger" },
  expiry: { label: "Expiry", severity: "warn" },
  damage: { label: "Damage", severity: "warn" },
  correction: { label: "Correction", severity: "success" },
  other: { label: "Other", severity: "secondary" },
};

const config = computed(() => reasonMap[props.reason] ?? { label: props.reason, severity: "secondary" as const });
</script>

<template>
  <Tag :value="t(config.label)" :severity="config.severity" />
</template>
