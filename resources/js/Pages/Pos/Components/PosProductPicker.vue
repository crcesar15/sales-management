<script setup lang="ts">
import { Button, Dialog } from "primevue";
import { nextTick, ref } from "vue";
import PosProductSearch from "./PosProductSearch.vue";
import type { PosCartItem } from "@/Types/pos";

const props = defineProps<{
  storeId: number | null;
  cartItems: PosCartItem[];
  getRemainingBase: (variantId: number) => number | null;
}>();

const emit = defineEmits<{
  (e: "add", item: PosCartItem): void;
}>();

const visible = defineModel<boolean>("visible", { default: false });
const barcodeMode = defineModel<boolean>("barcodeMode", { default: false });
const search = ref<InstanceType<typeof PosProductSearch>>();

function focusSearch(): void {
  void nextTick(() => search.value?.focusSearch());
}

function resetSearch(): void {
  search.value?.resetSearch();
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    dismissable-mask
    :closable="false"
    :draggable="false"
    class="m-0 h-[100dvh] max-h-none w-screen max-w-none rounded-none"
    :pt="{
      header: { class: '!border-b !border-surface-200 !px-3 !py-2 dark:!border-surface-700' },
      content: { class: '!flex !min-h-0 !flex-1 !p-0' },
    }"
    @show="focusSearch"
    @hide="resetSearch"
  >
    <template #header>
      <div class="flex min-w-0 flex-1 items-center justify-end gap-2">
        <Button icon="fa fa-times" text rounded severity="secondary" class="min-h-11 shrink-0" @click="visible = false" />
      </div>
    </template>

    <PosProductSearch
      ref="search"
      input-id="pos-product-search-mobile"
      v-model:barcode-mode="barcodeMode"
      :store-id="props.storeId"
      :cart-items="props.cartItems"
      :get-remaining-base="props.getRemainingBase"
      :focus-on-clear="false"
      @add="emit('add', $event)"
    />
  </Dialog>
</template>
