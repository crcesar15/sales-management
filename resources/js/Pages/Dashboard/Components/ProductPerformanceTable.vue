<script setup lang="ts">
import { Button } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import type { ProductPerformance } from "@/Types/dashboard-types";
defineProps<{ products: ProductPerformance[]; showProfit: boolean; canView: boolean }>();
const emit = defineEmits<{ product: [id: number] }>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
</script>

<template>
  <section
    class="min-w-0 rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-800"
    aria-labelledby="products-performance-title"
  >
    <h2 id="products-performance-title" class="m-0 text-xl font-bold">{{ t("Top-selling products") }}</h2>
    <p class="mb-4 mt-1 text-sm text-surface-600 dark:text-surface-300">{{ t("Ranked by net sales after discounts") }}</p>
    <p v-if="!products.length" class="py-6 text-center text-surface-600 dark:text-surface-300">
      {{ t("No fulfilled products in this period.") }}
    </p>
    <ul v-if="products.length" class="m-0 list-none divide-y divide-surface-200 p-0 sm:hidden dark:divide-surface-700">
      <li v-for="product in products" :key="product.variant_id" class="py-3">
        <Button
          v-if="canView"
          :label="product.name"
          link
          class="!p-0 !text-left dark:!text-primary-200"
          @click="emit('product', product.variant_id)"
        />
        <span v-else class="font-medium">{{ product.name }}</span>
        <p v-if="product.identifier" class="mb-2 mt-1 text-xs text-surface-600 dark:text-surface-300">{{ product.identifier }}</p>
        <dl class="m-0 grid grid-cols-2 gap-3 text-sm tabular-nums">
          <div>
            <dt class="text-surface-600 dark:text-surface-300">{{ t("Net sales") }}</dt>
            <dd class="m-0 mt-1 font-medium">{{ formatCurrency(String(product.net_sales)) }}</dd>
          </div>
          <div v-if="showProfit">
            <dt class="text-surface-600 dark:text-surface-300">{{ t("Gross profit") }}</dt>
            <dd class="m-0 mt-1 font-medium">
              {{ product.profitability?.complete ? formatCurrency(String(product.profitability.gross_profit)) : t("Incomplete costs") }}
            </dd>
          </div>
        </dl>
      </li>
    </ul>
    <div v-if="products.length" class="hidden overflow-x-auto sm:block">
      <table class="w-full text-left text-sm tabular-nums">
        <caption class="sr-only">{{ t("Product sales and historical gross profit") }}</caption>
        <thead>
          <tr class="border-b border-surface-200 dark:border-surface-700">
            <th scope="col" class="pb-3 pr-3 font-medium">{{ t("Product") }}</th>
            <th scope="col" class="whitespace-nowrap pb-3 text-right font-medium">{{ t("Net sales") }}</th>
            <th v-if="showProfit" scope="col" class="whitespace-nowrap pb-3 pl-4 text-right font-medium">{{ t("Gross profit") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="product in products"
            :key="product.variant_id"
            class="border-b border-surface-100 last:border-0 dark:border-surface-700"
          >
            <th scope="row" class="py-3 pr-3 text-left font-normal">
              <Button
                v-if="canView"
                :label="product.name"
                link
                class="!p-0 !text-left dark:!text-primary-200"
                @click="emit('product', product.variant_id)"
              />
              <span v-else class="font-medium">{{ product.name }}</span>
              <span v-if="product.identifier" class="mt-1 block text-xs text-surface-600 dark:text-surface-300">
                {{ product.identifier }}
              </span>
            </th>
            <td class="whitespace-nowrap py-3 text-right">{{ formatCurrency(String(product.net_sales)) }}</td>
            <td v-if="showProfit" class="whitespace-nowrap py-3 pl-4 text-right">
              {{ product.profitability?.complete ? formatCurrency(String(product.profitability.gross_profit)) : t("Incomplete costs") }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
