<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { Badge } from "primevue";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

interface ShiftStatusBadgeProps {
  /** Shift status: 'open' | 'closed' */
  status: "open" | "closed";
  /** Show additional details */
  showDetails?: boolean;
  /** Shift number */
  shiftNumber?: string;
  /** Opening balance */
  openingBalance?: number;
}

const props = withDefaults(defineProps<ShiftStatusBadgeProps>(), {
  showDetails: false,
  shiftNumber: undefined,
  openingBalance: undefined,
});

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const severity = computed(() => (props.status === "open" ? "success" : "secondary"));
const label = computed(() => (props.status === "open" ? t("Open") : t("Closed")));
const formattedBalance = computed(() =>
  props.openingBalance !== undefined ? formatCurrency(props.openingBalance.toString()) : "",
);
</script>

<template>
  <div class="flex items-center gap-2">
    <Badge :value="label" :severity="severity" />
    <span v-if="showDetails && shiftNumber" class="text-sm text-surface-500 dark:text-surface-400">
      {{ t("Shift") }} #{{ shiftNumber }}
      <template v-if="openingBalance !== undefined">
        <span class="mx-1 text-surface-300 dark:text-surface-600" aria-hidden="true">&bull;</span>
        {{ t("Opened") }}: {{ formattedBalance }}
      </template>
    </span>
  </div>
</template>