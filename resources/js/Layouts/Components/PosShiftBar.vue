<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import { useConfirm } from "primevue/useconfirm";
import { Button, Badge } from "primevue";
import { usePosStore } from "@/Composables/usePosStore";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

const { t } = useI18n();
const confirm = useConfirm();
const posStore = usePosStore();
const { formatCurrency } = useCurrencyFormatter();

const storeName = computed(() => posStore.store?.name ?? t("Store"));
const registerName = computed(() => posStore.register?.name ?? t("Cash Register"));
const shiftStatus = computed(() => posStore.shift);
const isShiftOpen = computed(() => posStore.shift?.status === "open");
const isCashier = computed(() => posStore.shift?.cashier_id === posStore.userId);

const formattedOpeningBalance = computed(() => {
  if (!shiftStatus.value) return formatCurrency("0");
  return formatCurrency(shiftStatus.value.opening_balance.toString());
});

function exitPos(): void {
  // Cart check will be added in Task 02 (POS Interface)
  router.visit(route("home"));
}

function closeShift(): void {
  confirm.require({
    message: t("Are you sure you want to close this shift?"),
    header: t("Close Shift"),
    icon: "fa fa-exclamation-triangle",
    acceptLabel: t("Yes, close shift"),
    rejectLabel: t("Cancel"),
    accept: () => {
      posStore.setShift(null);
    },
  });
}
</script>

<template>
  <header
    class="fixed top-0 left-0 right-0 h-14 flex items-center justify-between px-4 z-[1000] bg-surface-0 dark:bg-surface-900 border-b border-surface-200 dark:border-surface-700 shadow-sm"
    role="banner"
    :aria-label="t('Point of Sale navigation')"
  >
    <h1 class="sr-only">{{ t("Point of Sale") }}</h1>

    <div class="flex items-center gap-4">
      <!-- Exit button -->
      <Button
        v-tooltip.right="t('Exit POS')"
        icon="fa fa-bars"
        :aria-label="t('Exit POS')"
        severity="secondary"
        text
        size="small"
        @click="exitPos"
      />

      <!-- Store name -->
      <span class="flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300">
        <i class="fa fa-store text-primary-500" aria-hidden="true" />
        <span class="sr-only">{{ t("Store") }}:</span>
        {{ storeName }}
      </span>

      <!-- Register name -->
      <span class="flex items-center gap-2 text-sm text-surface-700 dark:text-surface-300">
        <i class="fa fa-cash-register text-primary-500" aria-hidden="true" />
        <span class="sr-only">{{ t("Cash Register") }}:</span>
        {{ registerName }}
      </span>
    </div>

    <div class="flex items-center gap-4">
      <!-- Shift status -->
      <div v-if="shiftStatus" class="flex items-center gap-2" aria-live="polite">
        <Badge :value="isShiftOpen ? t('Open') : t('Closed')" :severity="isShiftOpen ? 'success' : 'secondary'" />
        <span class="text-sm text-surface-500 dark:text-surface-400">
          {{ t("Shift") }} #{{ shiftStatus.shift_number }}
          <span class="mx-1 text-surface-300 dark:text-surface-600" aria-hidden="true">&bull;</span>
          {{ t("Opened") }}: {{ formattedOpeningBalance }}
        </span>
      </div>
      <div v-else class="flex items-center gap-2" aria-live="polite">
        <Badge :value="t('No shift')" severity="danger" />
      </div>
    </div>

    <div class="flex items-center gap-4">
      <!-- Close shift button (only visible when shift is open and user is cashier) -->
      <Button
        v-if="isShiftOpen && isCashier"
        v-tooltip.left="t('Close shift')"
        icon="fa fa-lock"
        :aria-label="t('Close shift')"
        severity="danger"
        outlined
        size="small"
        @click="closeShift"
      />
    </div>
  </header>
</template>

<style scoped>
.sr-only {
  @apply absolute w-px h-px p-0 -m-px overflow-hidden whitespace-nowrap border-0;
  clip: rect(0, 0, 0, 0);
}
</style>