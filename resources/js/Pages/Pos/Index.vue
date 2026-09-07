<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
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
import { usePage } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
import type { PosCartItem } from "@/Types/pos";

defineOptions({ layout: PosLayout });

const { t } = useI18n();
const posStore = usePosStore();
const posClient = usePosClient();
const page = usePage();
const toast = useToast();
const { cartItems } = storeToRefs(posStore);

const showRegisterDialog = ref(false);
const sessionLoading = ref(true);
const sessionError = ref(false);

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
  posStore.clearSession();

  try {
    const session = await posClient.getSession();
    if (session.store) posStore.setStore(session.store);
    if (session.register) posStore.setRegister(session.register);
    posStore.setShift(session.shift);

    if (session.shift?.status !== "open") {
      showRegisterDialog.value = true;
    }
  } catch {
    sessionError.value = true;
  } finally {
    sessionLoading.value = false;
  }
}

onMounted(() => {
  // Set user info from Inertia page props
  const authUser = page.props.auth?.user as unknown as { id: number; name: string; email: string } | undefined;
  if (authUser) {
    posStore.setUserId(authUser.id);
  }

  void loadSession();
});

watch(
  () => `${posStore.store?.id ?? "none"}:${posStore.shift?.id ?? "none"}`,
  (context, previousContext) => {
    if (previousContext !== undefined && context !== previousContext) {
      posStore.clearSale();
    }
  },
);
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
          :total="posStore.total"
          :discount-type="posStore.discountType"
          :discount-value="posStore.discountValue"
          :customer-choice-made="posStore.customerChoiceMade"
          @update-discount="posStore.setDiscount"
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
