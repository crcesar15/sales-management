<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { Button, useConfirm, useToast } from "primevue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import PosLayout from "@layouts/pos.vue";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { usePosStore } from "@composables/usePosStore";
import PosPaymentForm from "@pages/Pos/Components/PosPaymentForm.vue";
import type { PosPaymentPayload } from "@/Types/pos";
import type { SalesOrderResponse } from "@/Types/sales-order-types";

defineOptions({ layout: PosLayout });

const props = defineProps<{
  order: SalesOrderResponse;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const posStore = usePosStore();
const confirm = useConfirm();
const toast = useToast();
const discardForm = useForm({});
const processing = ref(false);
const paymentError = ref<string>();

function submitPayment(payload: PosPaymentPayload): void {
  if (processing.value) return;

  processing.value = true;
  paymentError.value = undefined;
  router.post(route("pos.sales.complete", props.order.id), { ...payload }, {
    preserveScroll: true,
    onSuccess: () => posStore.clearSale(),
    onError: (errors) => {
      paymentError.value = errors.payment ?? t("Unable to complete the payment");
      toast.add({ severity: "error", summary: t("Payment failed"), detail: paymentError.value, life: 4000, group: "pos" });
    },
    onFinish: () => {
      processing.value = false;
    },
  });
}

function discardDraft(): void {
  confirm.require({
    header: t("Discard sale"),
    message: t("Discard this sale and return to an empty cart?"),
    icon: "fa fa-triangle-exclamation",
    rejectLabel: t("Keep sale"),
    acceptLabel: t("Discard"),
    acceptProps: { severity: "danger" },
    accept: () => {
      discardForm.delete(route("pos.sales.discard", props.order.id), {
        onSuccess: () => posStore.clearSale(),
      });
    },
  });
}
</script>

<template>
  <div class="min-h-full p-4 lg:p-6">
    <div class="mx-auto flex max-w-3xl flex-col gap-4">
      <header class="flex items-center justify-between gap-4">
        <Button
          :label="t('Back to sale')"
          icon="fa fa-arrow-left"
          severity="secondary"
          outlined
          @click="router.visit(route('pos.sales.edit', order.id))"
        />
        <Button :label="t('Discard sale')" icon="fa fa-trash-can" severity="danger" text @click="discardDraft" />
      </header>

      <section class="rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900" :aria-label="t('Amount due')">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div>
            <p class="m-0 text-surface-500 dark:text-surface-400">{{ t("Amount due") }}</p>
            <h1 class="m-0 mt-1 text-4xl font-bold tabular-nums">{{ formatCurrency(String(order.total)) }}</h1>
          </div>
          <div class="text-right text-sm text-surface-500 dark:text-surface-400">
            <div>{{ t("Sale") }} #{{ order.id }}</div>
            <div>{{ order.customer?.display_name ?? t("Walk-in customer") }}</div>
          </div>
        </div>
        <div class="mt-4 flex flex-wrap justify-end gap-x-5 gap-y-1 border-t border-surface-200 pt-3 text-sm dark:border-surface-700">
          <span>{{ t("Subtotal") }}: <strong>{{ formatCurrency(String(order.sub_total)) }}</strong></span>
          <span>{{ t("Discount") }}: <strong>−{{ formatCurrency(String(order.discount)) }}</strong></span>
          <span>{{ t("Tax") }}: <strong>{{ formatCurrency(String(order.tax_amount)) }}</strong></span>
        </div>
      </section>

      <section class="rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900">
        <PosPaymentForm :total="order.total" :processing="processing" :server-error="paymentError" @submit="submitPayment" />
      </section>
    </div>
  </div>
</template>
