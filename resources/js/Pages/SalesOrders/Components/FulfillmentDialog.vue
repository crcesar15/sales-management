<script setup lang="ts">
import { Button, Column, DataTable, Dialog } from "primevue";
import { useI18n } from "vue-i18n";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import type { SalesOrderHandoverPreview } from "@/Types/sales-order-types";

defineProps<{ visible: boolean; preview: SalesOrderHandoverPreview | null; processing?: boolean }>();
const emit = defineEmits<{ "update:visible": [visible: boolean]; confirm: []; regenerate: [] }>();
const { t } = useI18n();
const { formatDate } = useDatetimeFormatter();
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
      <p class="mb-4 text-surface-500 dark:text-surface-400">{{ t("Review the allocated stock before confirming handover.") }}</p>
      <DataTable :value="preview.allocations" striped-rows>
        <Column field="product" :header="t('Product')" />
        <Column :header="t('Quantity')">
          <template #body="{ data }">
            {{ data.quantity }}
            <span class="text-sm font-medium text-surface-400 dark:text-surface-500">({{ data.base_unit }})</span>
          </template>
        </Column>
        <Column field="batch_identifier" :header="t('Batch Identifier')" />
        <Column :header="t('Expiry')">
          <template #body="{ data }">{{ data.expiry_date ? formatDate(data.expiry_date) : "---" }}</template>
        </Column>
      </DataTable>
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
