<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from "vue";
import { Toast, ConfirmDialog } from "primevue";
import PosShiftBar from "@layouts/Components/PosShiftBar.vue";
import { useLayout } from "@layouts/Components/Composables/useLayout";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";

const { isDarkMode } = useLayout();
const { t } = useI18n();

// Reactive viewport check (updates on resize)
const windowWidth = ref(window.innerWidth);

const updateWidth = () => {
  windowWidth.value = window.innerWidth;
};

onMounted(() => {
  window.addEventListener("resize", updateWidth);
});

onUnmounted(() => {
  window.removeEventListener("resize", updateWidth);
});

const isViewportUnsupported = computed(() => windowWidth.value < 768);

const containerClass = computed(() => [
  "min-h-screen",
  isDarkMode.value ? "bg-surface-900 text-surface-0" : "bg-surface-ground text-surface-900",
]);
</script>

<template>
  <!-- Skip link for keyboard users -->
  <a href="#pos-main" class="skip-link sr-only focus:not-sr-only">
    {{ t("Skip to main content") }}
  </a>

  <div :class="containerClass">
    <!-- Unsupported viewport message -->
    <div v-if="isViewportUnsupported" class="flex flex-col items-center justify-center h-screen text-center p-8">
      <i class="fa fa-tablet-alt text-6xl text-primary-500 mb-4" aria-hidden="true" />
      <h2 class="text-xl font-semibold mb-2">{{ t("POS requires a tablet or desktop") }}</h2>
      <p class="text-surface-500 dark:text-surface-400 mb-4">
        {{ t("Please use a device with a screen width of at least 768px.") }}
      </p>
      <a :href="route('home')" class="text-primary-500 underline">
        {{ t("Return to Dashboard") }}
      </a>
    </div>

    <!-- Main POS interface -->
    <template v-else>
      <PosShiftBar />
      <main id="pos-main" class="pt-14 h-screen overflow-y-auto" role="main">
        <slot />
      </main>
    </template>

    <Toast position="top-center" group="pos" :pt="{ root: { class: 'pos-toast-offset' } }" />
    <ConfirmDialog />
  </div>
</template>

<style scoped>
.pos-toast-offset {
  top: 64px !important; /* 56px bar + 8px spacing */
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