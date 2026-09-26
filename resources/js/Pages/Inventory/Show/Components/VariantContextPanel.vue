<script setup lang="ts">
import { Card } from "primevue";

import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import type { InventoryProductDetail, InventoryVariantDetail } from "@app-types/inventory-variant-types";
import type { StockStoreBreakdown } from "@app-types/stock-overview-types";

const props = defineProps<{
  product: InventoryProductDetail;
  variant: InventoryVariantDetail;
  stores: StockStoreBreakdown[];
  canEditProduct: boolean;
}>();
const { t } = useI18n();
const { formatDatetime } = useDatetimeFormatter();

const isLowStock = computed(() => {
  if (props.variant.minimum_stock_level !== null) {
    return props.variant.stock < props.variant.minimum_stock_level;
  }

  return props.variant.stock <= 0;
});

</script>

<template>
  <Card class="lg:sticky lg:top-4">
    <template #title>
      <div class="flex flex-col gap-1">
        <span class="text-base font-semibold leading-6">{{ t("Summary") }}</span>
        <Link
          v-if="canEditProduct"
          :href="route('products.edit', { product: product.id })"
          class="w-fit text-sm font-medium text-primary hover:underline"
        >
          {{ product.name }}
          <i class="fa fa-arrow-up-right-from-square ml-1 text-xs" aria-hidden="true" />
        </Link>
        <span v-else class="text-sm font-medium text-surface-600 dark:text-surface-300">{{ product.name }}</span>
      </div>
    </template>
    <template #content>
      <div class="flex flex-col gap-5">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 border-b border-surface-200 pb-4 sm:grid-cols-2 lg:grid-cols-1 2xl:grid-cols-2 dark:border-surface-700">
          <div class="flex flex-col gap-1">
            <dt class="text-sm leading-5 text-surface-500 dark:text-surface-400">{{ t("Brand") }}</dt>
            <dd class="m-0 text-base font-medium leading-6">{{ product.brand?.name ?? "—" }}</dd>
          </div>
          <div class="flex flex-col gap-1">
            <dt class="text-sm leading-5 text-surface-500 dark:text-surface-400">{{ t("Measurement Unit") }}</dt>
            <dd class="m-0 text-base font-medium leading-6">{{ product.measurement_unit?.name ?? "—" }}</dd>
          </div>
          <div class="flex flex-col gap-1 sm:col-span-2 lg:col-span-1 2xl:col-span-2">
            <dt class="text-sm leading-5 text-surface-500 dark:text-surface-400">{{ t("Categories") }}</dt>
            <dd class="m-0 text-base font-medium leading-6">{{ product.categories.map((category) => category.name).join(", ") || "—" }}</dd>
          </div>
        </dl>

        <section class="flex flex-col gap-3" :aria-label="t('Stock status')">
          <div class="flex items-center justify-between gap-3">
            <span class="text-base leading-5">{{ t("Total Stock") }}</span>
            <span class="inline-flex items-center gap-2 text-base font-bold leading-6">
              <i v-if="isLowStock" class="fa-solid fa-triangle-exclamation text-red-500" :title="t('Low Stock')" aria-hidden="true" />
              {{ variant.stock }}
            </span>
          </div>
          <div class="flex items-center justify-between gap-3">
            <span class="text-base leading-5">{{ t("Minimum Stock Level") }}</span>
            <span class="text-base font-medium leading-6">{{ variant.minimum_stock_level ?? "—" }}</span>
          </div>
          <p v-if="isLowStock" class="m-0 text-sm font-medium leading-5 text-red-600 dark:text-red-300">
            {{ t("Stock is below its configured minimum.") }}
          </p>
        </section>

        <section class="flex flex-col gap-3 border-t border-surface-200 pt-4 dark:border-surface-700" :aria-labelledby="`stock-by-store-${variant.id}`">
          <h2 :id="`stock-by-store-${variant.id}`" class="m-0 text-base font-semibold leading-6">{{ t("Stock by Store") }}</h2>
          <ul v-if="stores.length" class="m-0 flex list-none flex-col gap-2 p-0">
            <li v-for="store in stores" :key="store.store_id" class="flex items-center justify-between gap-3">
              <span class="min-w-0 truncate text-base font-medium leading-6">
                {{ store.store_name }}
                <span class="text-sm font-normal text-surface-500 dark:text-surface-400">({{ store.store_code }})</span>
              </span>
              <span class="text-base font-semibold leading-6" :class="{ 'text-red-600 dark:text-red-300': store.quantity <= 0 }">{{ store.quantity }}</span>
            </li>
          </ul>
          <p v-else class="m-0 text-sm leading-5 text-surface-500 dark:text-surface-400">{{ t("No stock records found") }}</p>
        </section>

        <details class="border-t border-surface-200 pt-4 dark:border-surface-700">
          <summary class="cursor-pointer text-sm font-medium leading-5">{{ t("Audit details") }}</summary>
          <dl class="mt-3 flex flex-col gap-2 text-sm leading-5">
            <div class="flex justify-between gap-3">
              <dt class="text-surface-500 dark:text-surface-400">{{ t("Created") }}</dt>
              <dd class="m-0 text-right">{{ formatDatetime(variant.created_at) }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-surface-500 dark:text-surface-400">{{ t("Updated") }}</dt>
              <dd class="m-0 text-right">{{ formatDatetime(variant.updated_at) }}</dd>
            </div>
          </dl>
        </details>
      </div>
    </template>
  </Card>
</template>
