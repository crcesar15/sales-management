<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { Button } from "primevue";
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import PosLayout from "@layouts/pos.vue";
import { usePosStore } from "@composables/usePosStore";
import PosReceiptPanel from "@pages/Pos/Components/PosReceiptPanel.vue";
import type { SalesOrderResponse } from "@/Types/sales-order-types";

defineOptions({ layout: PosLayout });

defineProps<{
  order: SalesOrderResponse;
}>();

const { t } = useI18n();
const posStore = usePosStore();

onMounted(() => posStore.clearSale());

function startNewSale(): void {
  posStore.clearSale();
  router.visit(route("pos"));
}

function printReceipt(): void {
  window.print();
}
</script>

<template>
  <div class="min-h-full p-4 lg:p-6">
    <div class="mx-auto max-w-2xl">
      <div class="no-print mb-4 flex justify-end gap-3">
        <Button :label="t('Print receipt')" icon="fa fa-print" severity="secondary" outlined @click="printReceipt" />
        <Button :label="t('New sale')" icon="fa fa-plus" raised @click="startNewSale" />
      </div>
      <PosReceiptPanel :order="order" />
    </div>
  </div>
</template>
