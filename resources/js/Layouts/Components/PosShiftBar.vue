<script setup lang="ts">
import { computed, shallowRef, useTemplateRef } from "vue";
import { useI18n } from "vue-i18n";
import { Button, Badge, Popover } from "primevue";
import { router } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import { route } from "ziggy-js";
import { usePosStore } from "@/Composables/usePosStore";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { usePosLayout } from "@composables/usePosLayout";
import CloseShiftDialog from "@pages/Pos/Components/CloseShiftDialog.vue";
import ShiftStatusBadge from "@pages/Pos/Components/ShiftStatusBadge.vue";

const { t } = useI18n();
const posStore = usePosStore();
const { formatCurrency } = useCurrencyFormatter();
const { isSidebarCollapsed, toggleSidebar } = usePosLayout();
const { shift: shiftStatus, isShiftOpen, isCashier } = storeToRefs(posStore);
const shiftDetailsPopover = useTemplateRef<{ toggle: (event: Event) => void }>("shiftDetailsPopover");

const storeName = computed(() => posStore.store?.name ?? t("Store"));
const registerName = computed(() => posStore.register?.name ?? t("Cash Register"));
const displayedShiftStatus = computed<"open" | "closed">(() => (isShiftOpen.value ? "open" : "closed"));
const shiftDetailsLabel = computed(() => {
  if (!shiftStatus.value) return t("Shift Details");

  return `${t("Shift Details")}: ${t("Shift")} #${shiftStatus.value.id}, ${isShiftOpen.value ? t("Open") : t("Closed")}`;
});
const showCloseDialog = shallowRef(false);
const isShiftDetailsVisible = shallowRef(false);

const formattedOpeningBalance = computed(() => {
  if (!shiftStatus.value) return formatCurrency("0");
  return formatCurrency(shiftStatus.value.opening_balance.toString());
});

function closeShift(): void {
  showCloseDialog.value = true;
}

function toggleShiftDetails(event: Event): void {
  shiftDetailsPopover.value?.toggle(event);
}

function completeClose(): void {
  posStore.clearSession();
  router.visit(route("home"));
}
</script>

<template>
  <header
    class="fixed left-0 right-0 top-0 z-[1000] flex h-14 items-center gap-3 border-b border-surface-200 bg-surface-0 px-3 dark:border-surface-700 dark:bg-surface-900 lg:px-4"
    role="banner"
    :aria-label="t('Point of Sale')"
  >
    <h1 class="sr-only">{{ t("Point of Sale") }}</h1>

    <div class="flex min-w-0 flex-1 items-center gap-3">
      <!-- Sidebar toggle -->
      <Button
        v-tooltip.right="isSidebarCollapsed ? t('Expand sidebar') : t('Collapse sidebar')"
        icon="fa fa-bars"
        :aria-label="isSidebarCollapsed ? t('Expand sidebar') : t('Collapse sidebar')"
        :aria-expanded="!isSidebarCollapsed"
        aria-controls="pos-sidebar"
        severity="secondary"
        text
        class="h-11 w-11 shrink-0"
        @click="toggleSidebar"
      />

      <!-- Store name -->
      <span
        class="flex min-w-0 max-w-40 items-center gap-2 text-sm text-surface-700 dark:text-surface-300 lg:max-w-56"
        :title="storeName"
      >
        <i class="fa fa-store text-primary-500" aria-hidden="true" />
        <span class="sr-only">{{ t("Store") }}:</span>
        <span class="truncate">{{ storeName }}</span>
      </span>

      <!-- Register name -->
      <span
        class="flex min-w-0 max-w-40 items-center gap-2 text-sm text-surface-700 dark:text-surface-300 lg:max-w-56"
        :title="registerName"
      >
        <i class="fa fa-cash-register text-primary-500" aria-hidden="true" />
        <span class="sr-only">{{ t("Cash Register") }}:</span>
        <span class="truncate">{{ registerName }}</span>
      </span>
    </div>

    <div class="flex shrink-0 items-center gap-2">
      <!-- Shift status -->
      <button
        v-if="shiftStatus"
        type="button"
        class="flex min-h-11 items-center gap-2 rounded-md px-2 text-left hover:bg-surface-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 dark:hover:bg-surface-800"
        :aria-label="shiftDetailsLabel"
        aria-live="polite"
        aria-haspopup="dialog"
        aria-controls="pos-shift-details"
        :aria-expanded="isShiftDetailsVisible"
        @click="toggleShiftDetails"
      >
        <ShiftStatusBadge :status="displayedShiftStatus" show-details :shift-number="shiftStatus.id" />
        <i class="fa fa-chevron-down text-xs text-surface-500" aria-hidden="true" />
      </button>
      <div v-else class="flex min-h-11 items-center gap-2" aria-live="polite">
        <Badge :value="t('No shift')" severity="danger" />
      </div>

      <!-- Close shift button (only visible when shift is open and user is cashier) -->
      <Button
        v-if="isShiftOpen && isCashier"
        icon="fa fa-lock"
        :label="t('Close shift')"
        severity="danger"
        outlined
        class="min-h-11 shrink-0"
        @click="closeShift"
      />
    </div>
  </header>

  <Popover
    id="pos-shift-details"
    ref="shiftDetailsPopover"
    @show="isShiftDetailsVisible = true"
    @hide="isShiftDetailsVisible = false"
  >
    <div v-if="shiftStatus" class="min-w-64">
      <h2 class="mb-3 text-base font-semibold">{{ t("Shift Details") }}</h2>
      <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
        <dt class="text-surface-500 dark:text-surface-400">{{ t("Shift") }}</dt>
        <dd class="text-right font-medium">#{{ shiftStatus.id }}</dd>
        <dt class="text-surface-500 dark:text-surface-400">{{ t("Store") }}</dt>
        <dd class="max-w-48 truncate text-right font-medium" :title="storeName">{{ storeName }}</dd>
        <dt class="text-surface-500 dark:text-surface-400">{{ t("Cash Register") }}</dt>
        <dd class="max-w-48 truncate text-right font-medium" :title="registerName">{{ registerName }}</dd>
        <dt class="text-surface-500 dark:text-surface-400">{{ t("Opening Balance") }}</dt>
        <dd class="text-right font-medium">{{ formattedOpeningBalance }}</dd>
      </dl>
    </div>
  </Popover>
  <CloseShiftDialog v-model:visible="showCloseDialog" @completed="completeClose" />
</template>

<style scoped>
.sr-only {
  @apply absolute w-px h-px p-0 -m-px overflow-hidden whitespace-nowrap border-0;
  clip: rect(0, 0, 0, 0);
}
</style>
