<script setup lang="ts">
import { Button, Tag } from "primevue";
import { useI18n } from "vue-i18n";
import { useDatetimeFormatter } from "@/Composables/useDatetimeFormatter";
import type { InventoryAttention } from "@/Types/dashboard-types";

defineProps<{ inventory: InventoryAttention }>();
const emit = defineEmits<{ alerts: [storeId: number | null, tab: "low-stock" | "expiry"] }>();
const { t } = useI18n();
const { formatDateOnly } = useDatetimeFormatter();
</script>

<template>
  <section
    class="min-w-0 rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-800"
    aria-labelledby="inventory-attention-title"
  >
    <div class="flex items-start justify-between gap-3">
      <div>
        <h2 id="inventory-attention-title" class="m-0 text-xl font-bold">{{ t("Inventory attention") }}</h2>
        <p class="mb-3 mt-1 text-sm text-surface-600 dark:text-surface-300">{{ t("Current status") }}</p>
      </div>
      <Button :label="t('View alerts')" text size="small" class="dark:!text-primary-200" @click="emit('alerts', null, 'low-stock')" />
    </div>
    <dl class="m-0 grid grid-cols-2 gap-3 border-b border-surface-200 pb-4 dark:border-surface-700">
      <div>
        <dt class="text-sm text-surface-600 dark:text-surface-300">{{ t("Out of Stock") }}</dt>
        <dd class="m-0 mt-1 text-xl font-semibold tabular-nums">{{ inventory.stockouts }}</dd>
      </div>
      <div>
        <dt class="text-sm text-surface-600 dark:text-surface-300">{{ t("Low Stock") }}</dt>
        <dd class="m-0 mt-1 text-xl font-semibold tabular-nums">{{ inventory.low_stock }}</dd>
      </div>
      <div>
        <dt class="text-sm text-surface-600 dark:text-surface-300">{{ t("Expired batches") }}</dt>
        <dd class="m-0 mt-1 text-xl font-semibold tabular-nums">{{ inventory.expired }}</dd>
      </div>
      <div>
        <dt class="text-sm text-surface-600 dark:text-surface-300">{{ t("Expiring Soon") }}</dt>
        <dd class="m-0 mt-1 text-xl font-semibold tabular-nums">{{ inventory.expiring }}</dd>
      </div>
    </dl>
    <ul class="m-0 list-none divide-y divide-surface-200 p-0 dark:divide-surface-700">
      <li
        v-for="item in inventory.stock_items.slice(0, 3)"
        :key="`${item.variant_id}:${item.store_id}`"
        class="flex items-center justify-between gap-3 py-3"
      >
        <div class="min-w-0">
          <p class="m-0 break-words font-medium">{{ item.name }}</p>
          <p class="m-0 mt-1 text-sm text-surface-600 dark:text-surface-300">
            {{ item.store_name }} · {{ t("{count} available", { count: item.quantity }) }}
          </p>
        </div>
        <Button
          text
          size="small"
          :aria-label="t('View alerts for {product}', { product: item.name })"
          @click="emit('alerts', item.store_id, 'low-stock')"
        >
          <Tag :value="item.quantity <= 0 ? t('Out of Stock') : t('Low Stock')" :severity="item.quantity <= 0 ? 'danger' : 'warn'" />
        </Button>
      </li>
      <li
        v-for="item in inventory.expiry_items.slice(0, 2)"
        :key="`batch:${item.batch_id}`"
        class="flex items-center justify-between gap-3 py-3"
      >
        <div class="min-w-0">
          <p class="m-0 break-words font-medium">{{ item.product_name }}</p>
          <p class="m-0 mt-1 text-sm text-surface-600 dark:text-surface-300">
            {{ item.store_name }} · {{ t("Expires {date}", { date: formatDateOnly(item.expiry_date) }) }}
          </p>
        </div>
        <Button
          icon="fa fa-arrow-right"
          class="dark:!text-primary-200"
          text
          size="small"
          :aria-label="t('View expiry alerts for {product}', { product: item.product_name })"
          @click="emit('alerts', item.store_id, 'expiry')"
        />
      </li>
    </ul>
    <p
      v-if="!inventory.stock_items.length && !inventory.expiry_items.length"
      class="mb-0 mt-4 text-sm text-surface-600 dark:text-surface-300"
    >
      <i class="fa fa-circle-check mr-1" aria-hidden="true" />
      {{ t("No stock risks need attention.") }}
    </p>
    <p class="mb-0 mt-2 text-xs text-surface-600 dark:text-surface-300">
      {{ t("Stock alerts count product/store positions. Expiry alerts count batches.") }}
    </p>
  </section>
</template>
