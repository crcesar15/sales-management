<script setup lang="ts">
import { Button, Message, Skeleton } from "primevue";
import { useI18n } from "vue-i18n";
import FulfillmentAllocationList from "@pages/SalesOrders/Components/FulfillmentAllocationList.vue";
import type { SalesOrderHandoverPreview } from "@/Types/sales-order-types";

defineProps<{
  preview: SalesOrderHandoverPreview | null;
  loading: boolean;
  error?: string;
}>();

const emit = defineEmits<{
  regenerate: [];
}>();

const { t } = useI18n();
</script>

<template>
  <section
    class="rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900"
    :aria-label="t('Products to collect')"
  >
    <header class="mb-4 flex items-start justify-between gap-4">
      <div>
        <h2 class="m-0 text-xl font-bold">{{ t("Products to collect") }}</h2>
        <p class="m-0 mt-1 text-sm text-surface-500 dark:text-surface-400">
          {{ t("Collect the listed batches before confirming the sale.") }}
        </p>
      </div>
      <i class="fa fa-boxes-stacked mt-1 text-xl text-primary-600 dark:text-primary-400" aria-hidden="true" />
    </header>

    <div v-if="loading" class="flex flex-col gap-4" aria-live="polite">
      <span class="sr-only">{{ t("Generating handover list") }}</span>
      <div
        v-for="index in 3"
        :key="index"
        class="flex flex-col gap-2 border-t border-surface-200 pt-4 first:border-0 first:pt-0 dark:border-surface-700"
      >
        <Skeleton width="70%" height="1.25rem" />
        <Skeleton width="45%" height="1rem" />
        <Skeleton width="100%" height="2.5rem" />
      </div>
    </div>

    <div v-else-if="preview">
      <FulfillmentAllocationList :allocations="preview.allocations" compact />
      <div class="mt-4 flex items-center justify-between gap-4 border-t border-surface-200 pt-4 dark:border-surface-700">
        <small class="text-surface-500 dark:text-surface-400">{{ t("Stock is rechecked when you confirm the sale.") }}</small>
        <Button :label="t('Refresh list')" icon="fa fa-rotate" severity="secondary" text @click="emit('regenerate')" />
      </div>
    </div>

    <div v-else class="flex flex-col gap-4">
      <Message severity="error" :closable="false">{{ error ?? t("Unable to generate the handover list. Please try again.") }}</Message>
      <Button :label="t('Regenerate Handover List')" icon="fa fa-rotate" outlined @click="emit('regenerate')" />
    </div>
  </section>
</template>
