<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { Button, useConfirm, useToast } from "primevue";
import axios from "axios";
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import PosLayout from "@layouts/pos.vue";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { usePosStore } from "@composables/usePosStore";
import { usePosClient } from "@composables/usePosClient";
import PosPaymentForm from "@pages/Pos/Components/PosPaymentForm.vue";
import PosHandoverPanel from "@pages/Pos/Components/PosHandoverPanel.vue";
import type { PosPaymentPayload } from "@/Types/pos";
import type { SalesOrderHandoverPreview, SalesOrderResponse } from "@/Types/sales-order-types";

defineOptions({ layout: PosLayout });

const props = defineProps<{
  order: SalesOrderResponse;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const posStore = usePosStore();
const posClient = usePosClient();
const confirm = useConfirm();
const toast = useToast();
const discardForm = useForm({});
const processing = ref(false);
const paymentError = ref<string>();
const handoverPreview = ref<SalesOrderHandoverPreview | null>(null);
const handoverLoading = ref(false);
const handoverError = ref<string>();

async function generateHandoverPreview(): Promise<void> {
  handoverLoading.value = true;
  handoverError.value = undefined;
  handoverPreview.value = null;

  try {
    handoverPreview.value = await posClient.generateHandoverPreview(props.order.id);
  } catch (error) {
    if (axios.isAxiosError<{ message?: string }>(error)) {
      handoverError.value = error.response?.data.message ?? t("Unable to generate the handover list. Please try again.");
    } else {
      handoverError.value = error instanceof Error ? error.message : t("Unable to generate the handover list. Please try again.");
    }
  } finally {
    handoverLoading.value = false;
  }
}

function submitPayment(payload: PosPaymentPayload): void {
  if (processing.value || handoverPreview.value === null) return;

  processing.value = true;
  paymentError.value = undefined;
  router.post(
    route("pos.sales.complete", props.order.id),
    { ...payload, handover_token: handoverPreview.value.token },
    {
      preserveScroll: true,
      onSuccess: () => posStore.clearSale(),
      onError: (errors) => {
        paymentError.value = errors.payment ?? t("Unable to complete the payment");
        toast.add({ severity: "error", summary: t("Payment failed"), detail: paymentError.value, life: 4000, group: "pos" });
        void generateHandoverPreview();
      },
      onFinish: () => {
        processing.value = false;
      },
    },
  );
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

onMounted(() => void generateHandoverPreview());
</script>

<template>
  <div class="min-h-full p-4 lg:p-6">
    <div class="mx-auto flex max-w-6xl flex-col gap-4">
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

      <section
        class="rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900"
        :aria-label="t('Amount due')"
      >
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
          <span>
            {{ t("Subtotal") }}:
            <strong>{{ formatCurrency(String(order.sub_total)) }}</strong>
          </span>
          <span>
            {{ t("Discount") }}:
            <strong>−{{ formatCurrency(String(order.discount)) }}</strong>
          </span>
          <span>
            {{ t("Tax") }}:
            <strong>{{ formatCurrency(String(order.tax_amount)) }}</strong>
          </span>
        </div>
      </section>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <PosHandoverPanel
          class="xl:col-start-2 xl:row-start-1"
          :preview="handoverPreview"
          :loading="handoverLoading"
          :error="handoverError"
          @regenerate="generateHandoverPreview"
        />

        <section
          class="rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900 xl:col-start-1 xl:row-start-1"
        >
          <PosPaymentForm
            :total="order.total"
            :processing="processing"
            :disabled="handoverPreview === null || handoverLoading"
            :server-error="paymentError"
            @submit="submitPayment"
          />
        </section>
      </div>
    </div>
  </div>
</template>
