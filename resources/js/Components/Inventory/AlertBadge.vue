<script setup lang="ts">
import { Badge } from "primevue";
import type { AlertsSummary } from "@/Types/stock-alert-types";

defineProps<{
  summary: AlertsSummary;
}>();
</script>

<template>
  <Badge v-if="summary.total > 0" :value="summary.total" severity="danger" />
</template>
