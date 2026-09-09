<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { Button, Message, ProgressSpinner, useToast } from "primevue";
import PosLayout from "@layouts/pos.vue";
import RegisterSelectDialog from "./Components/RegisterSelectDialog.vue";
import PosProductSearch from "./Components/PosProductSearch.vue";
import PosCartTable from "./Components/PosCartTable.vue";
import PosCartSummary from "./Components/PosCartSummary.vue";
import CustomerSelect from "@pages/SalesOrders/Components/CustomerSelect.vue";
import { usePosStore } from "@/Composables/usePosStore";
import { usePosClient } from "@/Composables/usePosClient";
import { useStockLedger } from "@composables/useStockLedger";
import { router, usePage } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import type { PosCartItem } from "@/Types/pos";
import type { SalesOrderResponse } from "@/Types/sales-order-types";
import { useAuth } from "@composables/useAuth";
import { route } from "ziggy-js";

defineOptions({ layout: PosLayout });

const props = defineProps<{
  draft: SalesOrderResponse | null;
}>();

const { t } = useI18n();
const posStore = usePosStore();
const posClient = usePosClient();
const page = usePage();
const toast = useToast();
const { getSetting } = useAuth();
const { cartItems } = storeToRefs(posStore);

const showRegisterDialog = ref(false);
const sessionLoading = ref(true);
const sessionError = ref(false);
const checkoutProcessing = ref(false);
const canPay = computed(() => posStore.cartItems.length > 0 && posStore.customerChoiceMade && posStore.total > 0);

const { getRemainingBase, getRemainingBaseExcludingLine } = useStockLedger(cartItems);

function addCartItem(item: PosCartItem): void {
  if (posStore.addCartItem(item)) {
    toast.add({ severity: "success", summary: t("Added"), detail: t("Product added to the sale"), life: 2000, group: "pos" });
    return;
  }

  toast.add({ severity: "warn", summary: t("Stock limit reached"), detail: t("No more stock is available for this product"), life: 3000, group: "pos" });
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

function checkoutPayload() {
  return {
    customer_id: posStore.customer?.id ?? null,
    is_walk_in: posStore.isWalkIn,
    discount_type: posStore.discountType,
    discount_value: posStore.discountValue,
    items: posStore.cartItems.map((item) => ({
      product_variant_id: item.product_variant_id,
      sale_unit_id: item.sale_unit_id,
      quantity: item.quantity,
    })),
  };
}

function proceedToPayment(): void {
  if (!canPay.value || checkoutProcessing.value) return;

  checkoutProcessing.value = true;
  const options = {
    preserveScroll: true,
    onError: (errors: Record<string, string>) => {
      toast.add({
        severity: "error",
        summary: t("Unable to continue"),
        detail: errors.checkout ?? errors.items ?? t("Please review the sale and try again"),
        life: 4000,
        group: "pos",
      });
    },
    onFinish: () => {
      checkoutProcessing.value = false;
    },
  };

  if (posStore.draftId !== null) {
    router.put(route("pos.sales.update", posStore.draftId), checkoutPayload(), options);
    return;
  }

  router.post(route("pos.sales.store"), checkoutPayload(), options);
}

onMounted(async () => {
  // Set user info from Inertia page props
  const authUser = page.props.auth?.user as unknown as { id: number; name: string; email: string } | undefined;
  if (authUser) {
    posStore.setUserId(authUser.id);
  }

  posStore.setTaxRate(Number(getSetting("tax", "tax_rate", "0") ?? 0));
  await loadSession();
  if (props.draft !== null) posStore.hydrateDraft(props.draft);
});
</script>

<template>
  <div class="h-[calc(100vh-3.5rem)] p-4 lg:p-6">
    <div v-if="sessionLoading" class="flex h-full flex-col items-center justify-center gap-3 text-surface-500 dark:text-surface-400">
      <ProgressSpinner style="width: 36px; height: 36px" />
      <span>{{ t("Loading POS session") }}</span>
    </div>

    <div v-else-if="sessionError" class="flex h-full items-center justify-center">
      <Message severity="error" :closable="false" class="max-w-md">
        <div class="flex flex-col items-center gap-3 text-center">
          <i class="fa fa-triangle-exclamation text-2xl" aria-hidden="true" />
          <span>{{ t("Unable to load the POS session") }}</span>
          <Button :label="t('Retry')" severity="danger" outlined @click="loadSession" />
        </div>
      </Message>
    </div>

    <div v-else-if="posStore.isShiftOpen" class="grid h-full min-h-0 grid-cols-12 gap-4">
      <aside class="col-span-5 flex min-h-0 overflow-hidden rounded-lg border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900">
        <PosProductSearch :store-id="posStore.store?.id ?? null" :get-remaining-base="getRemainingBase" @add="addCartItem" />
      </aside>

      <div class="col-span-7 flex min-h-0 flex-col overflow-hidden rounded-lg border border-surface-200 bg-surface-0 dark:border-surface-700 dark:bg-surface-900">
        <div class="border-b border-surface-200 p-4 dark:border-surface-700">
          <h2 class="m-0 text-lg font-semibold">{{ t("Current sale") }}</h2>
          <div class="mt-3">
            <CustomerSelect
              :model-value="posStore.customer?.id ?? null"
              :initial-customer="posStore.customer"
              toast-group="pos"
              @select="posStore.selectCustomer"
              @walk-in="posStore.selectWalkIn"
              @clear="posStore.clearCustomer"
            />
          </div>
        </div>

        <PosCartTable
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
          @update-discount="posStore.setDiscount"
          @pay="proceedToPayment"
        />
      </div>
    </div>

    <div v-else class="flex h-full flex-col items-center justify-center gap-3 text-center text-surface-500 dark:text-surface-400">
      <i class="fa fa-cash-register text-4xl" aria-hidden="true" />
      <p class="m-0 font-medium">{{ t("Select a register to start a sale") }}</p>
    </div>

    <RegisterSelectDialog v-model:visible="showRegisterDialog" />
  </div>
</template>
