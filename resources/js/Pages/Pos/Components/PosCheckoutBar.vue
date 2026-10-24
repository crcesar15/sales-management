<script setup lang="ts">
import { Button } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";

defineProps<{
  total: number;
  canPay: boolean;
  processing: boolean;
}>();

const emit = defineEmits<{
  (e: "pay"): void;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
</script>

<template>
  <section
    class="sticky bottom-0 z-10 border-t border-surface-200 bg-surface-0 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] dark:border-surface-700 dark:bg-surface-900"
    :aria-label="t('Checkout')"
  >
    <div class="flex items-center justify-between gap-4">
      <span class="font-semibold">{{ t("Total") }}</span>
      <span class="text-lg font-bold tabular-nums">{{ formatCurrency(String(total)) }}</span>
    </div>
    <Button
      :label="t('Pay')"
      icon="fa fa-credit-card"
      :disabled="!canPay"
      :loading="processing"
      raised
      class="mt-3 min-h-12 w-full uppercase"
      @click="emit('pay')"
    />
  </section>
</template>
