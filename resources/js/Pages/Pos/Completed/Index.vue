<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Button } from "primevue";
import { onMounted, onUnmounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import PosLayout from "@layouts/pos.vue";
import { usePosStore } from "@composables/usePosStore";

defineOptions({ layout: PosLayout });

const { t } = useI18n();
const posStore = usePosStore();
const secondsRemaining = ref(3);
let redirectTimer: ReturnType<typeof setInterval> | undefined;

function returnToPos(): void {
  if (redirectTimer !== undefined) window.clearInterval(redirectTimer);
  router.visit(route("pos"));
}

onMounted(() => {
  posStore.clearSale();
  redirectTimer = setInterval(() => {
    secondsRemaining.value -= 1;

    if (secondsRemaining.value === 0) {
      window.clearInterval(redirectTimer);
      returnToPos();
    }
  }, 1000);
});

onUnmounted(() => {
  if (redirectTimer !== undefined) window.clearInterval(redirectTimer);
});
</script>

<template>
  <Head :title="t('Payment completed')" />

  <div class="flex min-h-full items-center justify-center p-4 lg:p-6">
    <section class="w-full max-w-xl rounded-lg border border-surface-200 bg-surface-0 p-6 text-center dark:border-surface-700 dark:bg-surface-900 sm:p-8">
      <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 text-3xl text-green-700 dark:bg-green-950 dark:text-green-300">
        <i class="fa fa-check" aria-hidden="true" />
      </div>

      <div class="mt-6 flex flex-col gap-2">
        <h1 class="m-0 text-3xl font-bold text-surface-900 dark:text-surface-0">{{ t("Payment completed") }}</h1>
        <p class="m-0 text-surface-600 dark:text-surface-300">{{ t("The sale has been completed and the payment was recorded.") }}</p>
      </div>

      <p class="mt-6 text-sm font-medium text-surface-700 dark:text-surface-200" aria-live="polite">
        {{ t("Returning to the POS in {seconds} seconds", { seconds: secondsRemaining }) }}
      </p>

      <Button :label="t('Continue')" icon="fa fa-arrow-right" icon-pos="right" raised class="mt-2 uppercase" @click="returnToPos" />
    </section>
  </div>
</template>
