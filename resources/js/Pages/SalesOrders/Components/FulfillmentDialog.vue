<script setup lang="ts">
import { Button, Dialog } from "primevue";
import { useI18n } from "vue-i18n";
import type { SalesOrderHandoverPreview } from "@/Types/sales-order-types";
import FulfillmentAllocationList from "./FulfillmentAllocationList.vue";

defineProps<{ visible: boolean; preview: SalesOrderHandoverPreview | null; processing?: boolean }>();
const emit = defineEmits<{ "update:visible": [visible: boolean]; confirm: []; regenerate: [] }>();
const { t } = useI18n();
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="t('Product Handover')"
    class="w-full max-w-4xl"
    @update:visible="emit('update:visible', $event)"
  >
    <template v-if="preview">
      <p class="mb-4 text-surface-500 dark:text-surface-400 text-[14px]">
        {{ t("Review the allocated stock before confirming handover.") }}
      </p>
      <FulfillmentAllocationList :allocations="preview.allocations" />
    </template>
    <p v-else class="m-0 text-surface-500 dark:text-surface-400">
      {{ t("The handover list is no longer available. Generate a new list.") }}
    </p>
    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" @click="emit('update:visible', false)" />
      <Button
        :label="preview ? t('Confirm Handover') : t('Regenerate Handover List')"
        :loading="processing"
        @click="preview ? emit('confirm') : emit('regenerate')"
      />
    </template>
  </Dialog>
</template>
