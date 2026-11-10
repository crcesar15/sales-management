<script setup lang="ts">
import { Button, Paginator, type PageState } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import type { StorePerformance } from "@/Types/dashboard-types";
defineProps<{ stores: StorePerformance; showProfit: boolean }>();
const emit = defineEmits<{ store: [id: number]; page: [page: number] }>();
const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const onPage = (event: PageState) => emit("page", event.page + 1);
</script>

<template>
  <section
    class="min-w-0 rounded-lg border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-800"
    aria-labelledby="store-performance-title"
  >
    <h2 id="store-performance-title" class="m-0 text-xl font-bold">{{ t("Store performance") }}</h2>
    <p class="mb-4 mt-1 text-sm text-surface-600 dark:text-surface-300">{{ t("Select a store to focus the entire dashboard.") }}</p>
    <ul class="m-0 list-none divide-y divide-surface-200 p-0 sm:hidden dark:divide-surface-700">
      <li v-for="store in stores.data" :key="store.id" class="py-3">
        <Button :label="store.name" link class="!p-0 !text-left dark:!text-primary-200" @click="emit('store', store.id)" />
        <dl class="mb-0 mt-3 grid grid-cols-2 gap-3 text-sm tabular-nums">
          <div>
            <dt class="text-surface-600 dark:text-surface-300">{{ t("Net sales") }}</dt>
            <dd class="m-0 mt-1 font-medium">{{ formatCurrency(String(store.net_sales)) }}</dd>
          </div>
          <div>
            <dt class="text-surface-600 dark:text-surface-300">{{ t("Orders") }}</dt>
            <dd class="m-0 mt-1 font-medium">{{ store.order_count }}</dd>
          </div>
          <div v-if="showProfit" class="col-span-2">
            <dt class="text-surface-600 dark:text-surface-300">{{ t("Gross profit") }}</dt>
            <dd class="m-0 mt-1 font-medium">
              {{ store.profitability?.complete ? formatCurrency(String(store.profitability.gross_profit)) : t("Incomplete costs") }}
            </dd>
          </div>
        </dl>
      </li>
    </ul>
    <div class="hidden overflow-x-auto sm:block">
      <table class="w-full text-left text-sm tabular-nums">
        <caption class="sr-only">{{ t("Store performance") }}</caption>
        <thead>
          <tr class="border-b border-surface-200 dark:border-surface-700">
            <th scope="col" class="pb-3 pr-3 font-medium">{{ t("Store") }}</th>
            <th scope="col" class="pb-3 px-3 text-right font-medium">{{ t("Orders") }}</th>
            <th scope="col" class="pb-3 px-3 text-right font-medium">{{ t("Net sales") }}</th>
            <th v-if="showProfit" scope="col" class="pb-3 pl-3 text-right font-medium">{{ t("Gross profit") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="store in stores.data" :key="store.id" class="border-b border-surface-100 last:border-0 dark:border-surface-700">
            <th scope="row" class="py-3 pr-3 font-normal">
              <Button :label="store.name" link class="!p-0 !text-left dark:!text-primary-200" @click="emit('store', store.id)" />
            </th>
            <td class="px-3 py-3 text-right">{{ store.order_count }}</td>
            <td class="whitespace-nowrap px-3 py-3 text-right">{{ formatCurrency(String(store.net_sales)) }}</td>
            <td v-if="showProfit" class="whitespace-nowrap py-3 pl-3 text-right">
              {{ store.profitability?.complete ? formatCurrency(String(store.profitability.gross_profit)) : t("Incomplete costs") }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <Paginator
      v-if="stores.meta.last_page > 1"
      :rows="stores.meta.per_page"
      :total-records="stores.meta.total"
      :first="(stores.meta.current_page - 1) * stores.meta.per_page"
      @page="onPage"
    />
  </section>
</template>
