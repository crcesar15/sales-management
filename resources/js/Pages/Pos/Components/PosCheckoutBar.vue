<script setup lang="ts">
import { Button } from "primevue";
import { computed, onMounted, onUnmounted, useTemplateRef } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";

const props = defineProps<{
  total: number;
  hasItems: boolean;
  customerChoiceMade: boolean;
  canPay: boolean;
  processing: boolean;
}>();

const emit = defineEmits<{
  (e: "pay"): void;
  (e: "resize", height: number): void;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const checkoutBar = useTemplateRef<HTMLElement>("checkoutBar");
const hint = computed(() => {
  if (!props.hasItems) return t("Add products to continue");
  if (!props.customerChoiceMade) return t("Select a customer or Walk-in before payment");
  if (props.total <= 0) return t("A draft can be saved, but the total must be greater than zero to pay");

  return "";
});

let resizeObserver: ResizeObserver | undefined;

function reportHeight(): void {
  const height = checkoutBar.value?.getBoundingClientRect().height;
  if (height !== undefined) emit("resize", Math.ceil(height));
}

onMounted(() => {
  reportHeight();
  resizeObserver = new ResizeObserver(reportHeight);
  if (checkoutBar.value !== null) resizeObserver.observe(checkoutBar.value);
});

onUnmounted(() => resizeObserver?.disconnect());
</script>

<template>
  <section
    ref="checkoutBar"
    class="pos-checkout-bar fixed inset-x-0 bottom-0 z-20 border-t border-surface-200 bg-surface-0 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] dark:border-surface-700 dark:bg-surface-900"
    :aria-label="t('Checkout')"
  >
    <div class="pos-checkout-bar__primary flex items-center justify-between gap-4">
      <span class="font-semibold">{{ t("Total") }}</span>
      <span class="text-lg font-bold tabular-nums">{{ formatCurrency(String(total)) }}</span>
    </div>
    <Button
      :label="t('Continue to payment')"
      icon="fa fa-credit-card"
      :disabled="!canPay"
      :loading="processing"
      raised
      class="pos-checkout-bar__button min-h-12 w-full uppercase"
      :aria-describedby="hint ? 'pos-checkout-hint' : undefined"
      @click="emit('pay')"
    />
    <small v-if="hint" id="pos-checkout-hint" class="mt-2 block text-center text-surface-500 dark:text-surface-400">
      {{ hint }}
    </small>
  </section>
</template>

<style scoped>
.pos-checkout-bar__button {
  margin-top: 0.75rem;
}

@media (orientation: landscape) and (max-height: 35rem) {
  .pos-checkout-bar {
    padding-top: 0.5rem;
  }

  .pos-checkout-bar__primary {
    display: none;
  }

  .pos-checkout-bar__button {
    margin-top: 0;
  }
}
</style>
