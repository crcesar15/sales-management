<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from "vue";
import { Toast, ConfirmDialog } from "primevue";
import PosShiftBar from "@layouts/Components/PosShiftBar.vue";
import PosSidebar from "@layouts/Components/PosSidebar.vue";
import CashMovementDialog from "@pages/Pos/Components/CashMovementDialog.vue";
import { useLayout } from "@layouts/Components/Composables/useLayout";
import { usePosLayout } from "@composables/usePosLayout";
import { usePosClient } from "@composables/usePosClient";
import { usePosStore } from "@composables/usePosStore";
import { usePage } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";

const { isDarkMode } = useLayout();
const { t } = useI18n();
const { isCashMovementDialogVisible, isCompactViewport, isMobileNavigationOpen, closeMobileNavigation, setCompactViewport } =
  usePosLayout();
const page = usePage();
const posClient = usePosClient();
const posStore = usePosStore();

// Reactive viewport check (updates on resize)
const windowWidth = ref(window.innerWidth);

const updateWidth = () => {
  windowWidth.value = window.innerWidth;
  setCompactViewport(windowWidth.value < 1024);
};

async function hydrateSessionForStandalonePage(): Promise<void> {
  const currentRoute = route().current() ?? "";
  if (["pos", "pos.sales.edit"].includes(currentRoute)) return;

  const user = page.props.auth.user;
  if (user !== null) posStore.setUserId(user.id);

  try {
    posStore.setSession(await posClient.getSession());
  } catch {
    // The page remains usable if the shift summary cannot be refreshed.
  }
}

onMounted(() => {
  updateWidth();
  window.addEventListener("resize", updateWidth);
  void hydrateSessionForStandalonePage();
});

onUnmounted(() => {
  window.removeEventListener("resize", updateWidth);
});

const containerClass = computed(() => [
  "min-h-screen",
  isDarkMode.value ? "bg-surface-ground text-surface-0" : "bg-surface-ground text-surface-900",
]);
</script>

<template>
  <a href="#pos-main" class="skip-link sr-only focus:not-sr-only">
    {{ t("Skip to main content") }}
  </a>

  <div :class="containerClass">
    <PosShiftBar />
    <div class="flex min-h-[100dvh] pt-14 lg:fixed lg:inset-x-0 lg:bottom-0 lg:top-14 lg:min-h-0 lg:overflow-hidden lg:pt-0">
      <PosSidebar />
      <button
        v-if="isCompactViewport && isMobileNavigationOpen"
        type="button"
        class="fixed inset-0 z-[1000] bg-surface-950/50"
        :aria-label="t('Close menu')"
        @click="closeMobileNavigation"
      />
      <main id="pos-main" class="min-w-0 flex-1 overflow-x-clip lg:min-h-0 lg:overflow-x-hidden lg:overflow-y-auto" role="main">
        <slot />
      </main>
    </div>

    <Toast position="top-center" group="pos" :pt="{ root: { class: 'pos-toast-offset' } }" />
    <ConfirmDialog />
    <CashMovementDialog v-model:visible="isCashMovementDialogVisible" />
  </div>
</template>

<style scoped>
/* Debugbar reserves body space for its fixed toolbar, which creates a second POS scrollbar. */
:global(body:has(#pos-main)) {
  padding-bottom: 0 !important;
}

.pos-toast-offset {
  top: calc(64px + env(safe-area-inset-top)) !important;
}

/* Skip link for accessibility */
.skip-link {
  @apply absolute -top-10 left-0 z-[9999] px-4 py-2 bg-primary-500 text-white no-underline;
}

.skip-link:focus {
  @apply top-0;
}

.sr-only {
  @apply absolute w-px h-px p-0 -m-px overflow-hidden whitespace-nowrap border-0;
  clip: rect(0, 0, 0, 0);
}

.sr-only:focus {
  @apply w-auto h-auto p-2 m-0 overflow-visible whitespace-normal;
  clip: auto;
}
</style>
