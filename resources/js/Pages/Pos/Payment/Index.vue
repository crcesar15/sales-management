<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { Button, Dialog, Message, useConfirm, useToast } from "primevue";
import axios from "axios";
import { computed, nextTick, onMounted, onUnmounted, ref, shallowRef, useTemplateRef } from "vue";
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
const handoverDialogVisible = shallowRef(false);
const isWideHandoverLayout = shallowRef(window.matchMedia("(min-width: 1280px)").matches);
const shouldFocusPaymentAfterDialogClose = shallowRef(false);
const paymentSection = useTemplateRef<HTMLElement>("paymentSection");
const lineDiscountTotal = computed(() => (props.order.items ?? []).reduce((sum, item) => sum + (item.line_discount ?? 0), 0));

const handoverLayoutQuery = window.matchMedia("(min-width: 1280px)");

function updateHandoverLayout(event: MediaQueryListEvent): void {
  isWideHandoverLayout.value = event.matches;

  if (!event.matches || !handoverDialogVisible.value) return;

  shouldFocusPaymentAfterDialogClose.value = true;
  handoverDialogVisible.value = false;
}

function focusPaymentAfterDialogClose(): void {
  if (!shouldFocusPaymentAfterDialogClose.value) return;

  shouldFocusPaymentAfterDialogClose.value = false;
  void nextTick(() => paymentSection.value?.focus());
}

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

onMounted(() => {
  handoverLayoutQuery.addEventListener("change", updateHandoverLayout);
  void generateHandoverPreview();
});

onUnmounted(() => handoverLayoutQuery.removeEventListener("change", updateHandoverLayout));
</script>

<template>
  <div class="min-h-[calc(100dvh-3.5rem)] p-3 sm:p-4 lg:min-h-full lg:p-6">
    <div class="mx-auto flex max-w-6xl flex-col gap-4">
      <header class="flex flex-wrap items-center justify-between gap-3">
        <Button
          :label="t('Back to sale')"
          icon="fa fa-arrow-left"
          severity="secondary"
          @click="router.visit(route('pos.sales.edit', order.id))"
        />
        <Button :label="t('Discard sale')" icon="fa fa-trash-can" severity="danger" @click="discardDraft" />
      </header>

      <section
        class="rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900"
        :aria-label="t('Amount due')"
      >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <p class="m-0 text-surface-700 dark:text-surface-200">{{ t("Amount due") }}</p>
            <h1 class="m-0 mt-1 text-4xl font-bold tabular-nums">{{ formatCurrency(String(order.total)) }}</h1>
          </div>
          <div class="flex flex-col gap-y-2 text-left text-surface-700 dark:text-surface-200 sm:text-right">
            <span>{{ t("Sales Order") }} #{{ order.id }}</span>
            <span>{{ t("Tax ID") }}: {{ order.customer?.tax_id ?? t("Walk-in customer") }}</span>
            <span>{{ t("Tax ID Name") }}: {{ order.customer?.tax_id_name }}</span>
          </div>
        </div>
        <div
          class="mt-4 flex flex-col gap-1 border-t border-surface-200 pt-3 text-sm dark:border-surface-700 sm:flex-row sm:flex-wrap sm:justify-end sm:gap-x-5"
        >
          <span>
            {{ t("Subtotal after line discounts") }}:
            <strong>{{ formatCurrency(String(order.sub_total)) }}</strong>
          </span>
          <span>
            {{ t("Order discount") }}:
            <strong>−{{ formatCurrency(String(order.discount)) }}</strong>
          </span>
          <span v-if="lineDiscountTotal > 0">{{ t("Line discounts included") }}: <strong>−{{ formatCurrency(String(lineDiscountTotal)) }}</strong></span>
          <span>
            {{ t("Tax") }}:
            <strong>{{ formatCurrency(String(order.tax_amount)) }}</strong>
          </span>
        </div>

        <div class="mt-4 border-t border-surface-200 pt-4 dark:border-surface-700 xl:hidden">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <p class="m-0 font-medium">{{ t("Products to collect") }}</p>
              <p v-if="handoverLoading" class="m-0 mt-1 text-sm text-surface-500 dark:text-surface-400" aria-live="polite">
                <i class="fa fa-spinner fa-spin mr-1" aria-hidden="true" />
                {{ t("Generating handover list") }}
              </p>
            </div>
            <Button
              :label="t('Products to collect')"
              icon="fa fa-boxes-stacked"
              severity="secondary"
              outlined
              class="min-h-12 shrink-0"
              aria-controls="pos-handover-dialog"
              aria-haspopup="dialog"
              :aria-expanded="handoverDialogVisible"
              @click="handoverDialogVisible = true"
            />
          </div>

          <Message v-if="handoverError" severity="error" :closable="false" class="mt-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span>{{ handoverError }}</span>
              <Button :label="t('Retry')" icon="fa fa-rotate" severity="danger" text @click="generateHandoverPreview" />
            </div>
          </Message>
        </div>
      </section>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <section
          ref="paymentSection"
          tabindex="-1"
          class="rounded-lg border border-surface-200 bg-surface-0 p-5 outline-none dark:border-surface-700 dark:bg-surface-900 xl:col-start-1 xl:row-start-1"
        >
          <PosPaymentForm
            :total="order.total"
            :processing="processing"
            :disabled="handoverPreview === null || handoverLoading"
            :server-error="paymentError"
            @submit="submitPayment"
          />
        </section>

        <PosHandoverPanel
          v-if="isWideHandoverLayout"
          class="xl:col-start-2 xl:row-start-1"
          :preview="handoverPreview"
          :loading="handoverLoading"
          :error="handoverError"
          @regenerate="generateHandoverPreview"
        />
      </div>

      <Dialog
        id="pos-handover-dialog"
        v-model:visible="handoverDialogVisible"
        modal
        dismissable-mask
        :header="t('Products to collect')"
        :draggable="false"
        :style="{ maxHeight: 'calc(100dvh - 1rem)' }"
        class="mx-2 w-[calc(100vw-1rem)] max-w-3xl sm:mx-4 sm:w-[calc(100vw-2rem)]"
        :pt="{ content: { class: '!overflow-y-auto !p-4 sm:!p-5' }, footer: { class: '!px-4 !pb-4 sm:!px-5 sm:!pb-5' } }"
        @after-hide="focusPaymentAfterDialogClose"
      >
        <PosHandoverPanel
          embedded
          :preview="handoverPreview"
          :loading="handoverLoading"
          :error="handoverError"
          @regenerate="generateHandoverPreview"
        />

        <template #footer>
          <Button :label="t('Back to payment')" severity="secondary" @click="handoverDialogVisible = false" />
        </template>
      </Dialog>
    </div>
  </div>
</template>
