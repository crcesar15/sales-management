<script setup lang="ts">
import { computed, nextTick, onMounted, ref, shallowRef, watch } from "vue";
import { useI18n } from "vue-i18n";
import { Button, Menu, Message, ProgressSpinner, useConfirm, useToast } from "primevue";
import PosLayout from "@layouts/pos.vue";
import RegisterSelectDialog from "./Components/RegisterSelectDialog.vue";
import PosProductSearch from "./Components/PosProductSearch.vue";
import PosCartTable from "./Components/PosCartTable.vue";
import PosCartSummary from "./Components/PosCartSummary.vue";
import PosCheckoutBar from "./Components/PosCheckoutBar.vue";
import PosProductPicker from "./Components/PosProductPicker.vue";
import CustomerSelect from "@pages/SalesOrders/Components/CustomerSelect.vue";
import { usePosStore } from "@/Composables/usePosStore";
import { usePosClient } from "@/Composables/usePosClient";
import { useStockLedger } from "@composables/useStockLedger";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import type { PosAddItemOptions, PosCartItem } from "@/Types/pos";
import type { SalesOrderResponse } from "@/Types/sales-order-types";
import type { MenuItem } from "primevue/menuitem";
import { useAuth } from "@composables/useAuth";
import { usePosLayout } from "@composables/usePosLayout";
import { route } from "ziggy-js";

defineOptions({ layout: PosLayout });

const props = defineProps<{
  draft: SalesOrderResponse | null;
}>();

const { t } = useI18n();
const posStore = usePosStore();
const posClient = usePosClient();
const page = usePage();
const confirm = useConfirm();
const toast = useToast();
const discardForm = useForm({});
const { getSetting } = useAuth();
const { isCompactViewport } = usePosLayout();
const { cartItems } = storeToRefs(posStore);

const showRegisterDialog = ref(false);
const sessionLoading = ref(true);
const sessionError = ref(false);
const checkoutProcessing = ref(false);
const saveProcessing = ref(false);
const customerSelectorKey = ref(0);
const saleMenu = ref();
const productPickerVisible = ref(false);
const barcodeMode = ref(false);
const checkoutBarHeight = shallowRef(0);
const canSave = computed(() => posStore.cartItems.length > 0 && posStore.customerChoiceMade);
const canPay = computed(
  () => posStore.cartItems.length > 0 && posStore.customerChoiceMade && posStore.total > 0 && !discardForm.processing,
);
const saleMenuItems = computed<MenuItem[]>(() => [
  {
    label: t("Save as draft"),
    icon: "fa fa-floppy-disk",
    disabled: !canSave.value || checkoutProcessing.value || saveProcessing.value,
    command: saveDraft,
  },
  { separator: true },
  {
    label: t("Discard sale"),
    icon: "fa fa-trash-can",
    disabled: posStore.cartItems.length === 0 || checkoutProcessing.value || saveProcessing.value || discardForm.processing,
    command: discardSale,
  },
]);

const { getRemainingBase, getRemainingBaseExcludingLine } = useStockLedger(cartItems);
const cartPanelStyle = computed(() =>
  isCompactViewport.value && checkoutBarHeight.value > 0 ? { marginBottom: `${checkoutBarHeight.value}px` } : undefined,
);

watch(isCompactViewport, (isCompact) => {
  if (!isCompact) checkoutBarHeight.value = 0;
});

function updateCheckoutBarHeight(height: number): void {
  checkoutBarHeight.value = height;
}

function addCartItem(item: PosCartItem, options?: PosAddItemOptions): void {
  if (posStore.addCartItem(item)) {
    if (!options?.keepProductPickerOpen) {
      productPickerVisible.value = false;
      void nextTick(() => document.getElementById("pos-add-products")?.focus());
    }

    toast.add({ severity: "success", summary: t("Added"), detail: t("Product added to the sale"), life: 2000, group: "pos" });
    return;
  }

  toast.add({
    severity: "warn",
    summary: t("Stock limit reached"),
    detail: t("No more stock is available for this product"),
    life: 3000,
    group: "pos",
  });
}

async function loadSession(): Promise<void> {
  sessionLoading.value = true;
  sessionError.value = false;
  showRegisterDialog.value = false;
  const previousStoreId = posStore.store?.id ?? null;
  const previousShiftId = posStore.shift?.id ?? null;

  try {
    const session = await posClient.getSession();
    if (
      props.draft === null &&
      previousStoreId !== null &&
      (session.store?.id !== previousStoreId || session.shift?.id !== previousShiftId)
    ) {
      posStore.clearSale();
    }
    posStore.setSession(session);

    if (session.shift?.status !== "open") {
      showRegisterDialog.value = true;
    }
  } catch {
    sessionError.value = true;
  } finally {
    sessionLoading.value = false;
  }
}

function checkoutPayload(intent: "save" | "payment") {
  return {
    intent,
    customer_id: posStore.customer?.id ?? null,
    is_walk_in: posStore.customer === null && posStore.isWalkIn,
    discount_type: posStore.discountType,
    discount_value: posStore.discountValue,
    items: posStore.cartItems.map((item) => ({
      product_variant_id: item.product_variant_id,
      sale_unit_id: item.sale_unit_id,
      quantity: item.quantity,
      unit_price: item.unit_price,
      line_discount: item.line_discount ?? 0,
    })),
  };
}

function submitSale(intent: "save" | "payment"): void {
  if ((intent === "save" ? !canSave.value : !canPay.value) || checkoutProcessing.value || saveProcessing.value || discardForm.processing)
    return;

  if (intent === "save") {
    saveProcessing.value = true;
  } else {
    checkoutProcessing.value = true;
  }
  const options = {
    preserveScroll: true,
    onError: (errors: Record<string, string>) => {
      toast.add({
        severity: "error",
        summary: intent === "save" ? t("Unable to save draft") : t("Unable to continue"),
        detail: errors.checkout ?? errors.items ?? t("Please review the sale and try again"),
        life: 4000,
        group: "pos",
      });
    },
    onSuccess: () => {
      if (intent === "save") {
        posStore.clearSale();
        customerSelectorKey.value += 1;
        toast.add({ severity: "success", summary: t("Draft saved"), detail: t("Sale saved as draft"), life: 3000, group: "pos" });
      }
    },
    onFinish: () => {
      checkoutProcessing.value = false;
      saveProcessing.value = false;
    },
  };

  if (posStore.draftId !== null) {
    router.put(route("pos.sales.update", posStore.draftId), checkoutPayload(intent), options);
    return;
  }

  router.post(route("pos.sales.store"), checkoutPayload(intent), options);
}

function saveDraft(): void {
  submitSale("save");
}

function clearCurrentSale(): void {
  posStore.clearSale();
  customerSelectorKey.value += 1;
  toast.add({ severity: "success", summary: t("Sale discarded"), detail: t("The current sale was discarded"), life: 3000, group: "pos" });
}

function discardSale(): void {
  if (posStore.cartItems.length === 0 || discardForm.processing) return;

  confirm.require({
    header: t("Discard sale"),
    message: t("Discard this sale and return to an empty cart?"),
    icon: "fa fa-triangle-exclamation",
    rejectLabel: t("Keep sale"),
    acceptLabel: t("Discard"),
    acceptProps: { severity: "danger" },
    rejectProps: { severity: "secondary" },
    accept: () => {
      if (posStore.draftId === null) {
        clearCurrentSale();
        return;
      }

      discardForm.delete(route("pos.sales.discard", posStore.draftId), {
        preserveScroll: true,
        onSuccess: clearCurrentSale,
        onError: (errors) => {
          toast.add({
            severity: "error",
            summary: t("Unable to discard sale"),
            detail: errors.checkout ?? t("Please review the sale and try again"),
            life: 4000,
            group: "pos",
          });
        },
      });
    },
  });
}

function toggleSaleMenu(event: Event): void {
  saleMenu.value.toggle(event);
}

function openProductPicker(): void {
  productPickerVisible.value = true;
}

function proceedToPayment(): void {
  submitSale("payment");
}

function hydrateIncomingDraft(): void {
  if (props.draft !== null && posStore.draftId !== props.draft.id) {
    posStore.hydrateDraft(props.draft);
  }
}

onMounted(async () => {
  // Set user info from Inertia page props
  const authUser = page.props.auth?.user as unknown as { id: number; name: string; email: string } | undefined;
  if (authUser) {
    posStore.setUserId(authUser.id);
  }

  posStore.setTaxRate(Number(getSetting("tax", "tax_rate", "0") ?? 0));
  await loadSession();
  hydrateIncomingDraft();
});

watch(() => props.draft, hydrateIncomingDraft);
</script>

<template>
  <div class="min-h-[calc(100dvh-3.5rem)] p-3 sm:p-4 lg:h-[calc(100vh-3.5rem)] lg:p-6">
    <div
      v-if="sessionLoading"
      class="flex min-h-[calc(100dvh-5rem)] flex-col items-center justify-center gap-3 text-surface-500 dark:text-surface-400 lg:h-full lg:min-h-0"
    >
      <ProgressSpinner style="width: 36px; height: 36px" />
      <span>{{ t("Loading POS session") }}</span>
    </div>

    <div v-else-if="sessionError" class="flex min-h-[calc(100dvh-5rem)] items-center justify-center lg:h-full lg:min-h-0">
      <Message severity="error" :closable="false" class="max-w-md">
        <div class="flex flex-col items-center gap-3 text-center">
          <i class="fa fa-triangle-exclamation text-2xl" aria-hidden="true" />
          <span>{{ t("Unable to load the POS session") }}</span>
          <Button :label="t('Retry')" severity="danger" outlined @click="loadSession" />
        </div>
      </Message>
    </div>

    <div
      v-else-if="posStore.isShiftOpen"
      class="flex min-h-[calc(100dvh-5rem)] flex-col gap-4 lg:grid lg:h-full lg:min-h-0 lg:grid-cols-12"
    >
      <aside
        class="col-span-5 hidden min-h-0 overflow-hidden rounded-lg border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900 lg:flex"
      >
        <PosProductSearch
          input-id="pos-product-search-desktop"
          v-model:barcode-mode="barcodeMode"
          :store-id="posStore.store?.id ?? null"
          :cart-items="cartItems"
          :get-remaining-base="getRemainingBase"
          @add="addCartItem"
        />
      </aside>

      <div
        class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900 lg:col-span-7"
        :style="cartPanelStyle"
      >
        <div class="border-b border-surface-200 p-4 dark:border-surface-700">
          <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
              <Button
                v-tooltip.top="t('Sale actions')"
                icon="fa fa-ellipsis-vertical"
                severity="secondary"
                :aria-label="t('Sale actions')"
                @click="toggleSaleMenu"
              />
              <Menu ref="saleMenu" :model="saleMenuItems" popup />
              <h2 class="m-0 text-lg font-semibold">{{ t("Current sale") }}</h2>
            </div>
            <div class="flex items-center gap-2">
              <Button
                v-if="isCompactViewport"
                id="pos-add-products"
                icon="fa fa-plus"
                :label="t('Products')"
                raised
                class="min-h-11"
                @click="openProductPicker"
              />
            </div>
          </div>
          <div class="mt-3">
            <CustomerSelect
              :key="customerSelectorKey"
              :model-value="posStore.customer?.id ?? null"
              :initial-customer="posStore.customer"
              :is-walk-in="posStore.isWalkIn"
              :compact-pos="isCompactViewport"
              :edit-url="posStore.customer ? route('pos.customers.edit', posStore.customer.id) : undefined"
              toast-group="pos"
              @select="posStore.selectCustomer"
              @walk-in="posStore.selectWalkIn"
              @clear="posStore.clearCustomer"
            />
          </div>
        </div>

        <PosCartTable
          toast-group="pos"
          :model-value="posStore.cartItems"
          :get-remaining-base-excluding-line="getRemainingBaseExcludingLine"
          @update:model-value="posStore.setCartItems"
        />
        <PosCartSummary
          :sub-total="posStore.subTotal"
          :discount-amount="posStore.discountAmount"
          :tax-amount="posStore.taxAmount"
          :tax-rate="posStore.taxRate"
          :total="posStore.total"
          :discount-type="posStore.discountType"
          :discount-value="posStore.discountValue"
          :customer-choice-made="posStore.customerChoiceMade"
          :can-pay="canPay"
          :processing="checkoutProcessing"
          :show-pay-action="!isCompactViewport"
          @update-discount="posStore.setDiscount"
          @pay="proceedToPayment"
        />
      </div>

      <PosCheckoutBar
        v-if="isCompactViewport"
        :total="posStore.total"
        :has-items="posStore.cartItems.length > 0"
        :customer-choice-made="posStore.customerChoiceMade"
        :can-pay="canPay"
        :processing="checkoutProcessing"
        @pay="proceedToPayment"
        @resize="updateCheckoutBarHeight"
      />

      <PosProductPicker
        v-model:visible="productPickerVisible"
        v-model:barcode-mode="barcodeMode"
        :store-id="posStore.store?.id ?? null"
        :cart-items="cartItems"
        :get-remaining-base="getRemainingBase"
        @add="addCartItem"
      />
    </div>

    <div
      v-else
      class="flex min-h-[calc(100dvh-5rem)] flex-col items-center justify-center gap-3 text-center text-surface-500 dark:text-surface-400 lg:h-full lg:min-h-0"
    >
      <i class="fa fa-cash-register text-4xl" aria-hidden="true" />
      <p class="m-0 font-medium">{{ t("Select a register to start a sale") }}</p>
    </div>

    <RegisterSelectDialog v-model:visible="showRegisterDialog" />
  </div>
</template>
