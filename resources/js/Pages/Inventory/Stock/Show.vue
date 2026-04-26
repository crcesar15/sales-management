<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Button,
  Tag,
  Badge,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { StockVariantDetail } from "@/Types/stock-overview-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  stockDetail: StockVariantDetail;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const variant = props.stockDetail.variant;
const stores = props.stockDetail.stores;
</script>

<template>
  <div>
    <div class="flex items-center gap-3 mb-4">
      <Button
        icon="fa-solid fa-arrow-left"
        text
        rounded
        :aria-label="t('Back')"
        @click="router.visit(route('inventory.stock'))"
      />
      <h2 class="text-2xl font-bold m-0">{{ variant.product_name }}</h2>
    </div>

    <Card class="mb-4">
      <template #content>
        <div class="grid grid-cols-12 gap-4">
          <div class="md:col-span-4 col-span-12">
            <span class="text-color-secondary text-sm">{{ t("Variant") }}</span>
            <div class="mt-1">
              <span class="text-900 font-medium">{{ variant.name || "---" }}</span>
              <div v-if="variant.values.length" class="flex flex-wrap gap-1 mt-1">
                <Badge v-for="val in variant.values" :key="val.id" :value="`${val.option_name}: ${val.value}`" severity="secondary" />
              </div>
            </div>
          </div>
          <div class="md:col-span-2 col-span-6">
            <span class="text-color-secondary text-sm">{{ t("Identifier") }}</span>
            <p class="mt-1 text-900">{{ variant.identifier ?? "---" }}</p>
          </div>
          <div class="md:col-span-2 col-span-6">
            <span class="text-color-secondary text-sm">{{ t("Barcode") }}</span>
            <p class="mt-1 text-900">{{ variant.barcode ?? "---" }}</p>
          </div>
          <div class="md:col-span-2 col-span-6">
            <span class="text-color-secondary text-sm">{{ t("Price") }}</span>
            <p class="mt-1 text-900">{{ formatCurrency(String(variant.price)) }}</p>
          </div>
          <div class="md:col-span-2 col-span-6">
            <span class="text-color-secondary text-sm">{{ t("Total Stock") }}</span>
            <div class="mt-1 flex items-center gap-2">
              <span class="text-900 font-bold text-lg" :class="{ 'text-red-500': variant.is_low_stock }">{{ variant.total_stock }}</span>
              <Tag v-if="variant.is_low_stock" :value="t('Low Stock')" severity="danger" />
            </div>
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #title>
        {{ t("Stock by Store") }}
      </template>
      <template #content>
        <DataTable :value="stores" resizable-columns>
          <template #empty>
            {{ t("No stock records found") }}
          </template>

          <Column field="store_name" :header="t('Store')">
            <template #body="{ data }">
              <span class="text-900 font-medium">{{ data.store_name }}</span>
              <span class="text-color-secondary text-sm ml-2">({{ data.store_code }})</span>
            </template>
          </Column>

          <Column field="store_code" :header="t('Code')" style="width: 120px">
            <template #body="{ data }">
              {{ data.store_code }}
            </template>
          </Column>

          <Column field="quantity" :header="t('Quantity')" style="width: 150px">
            <template #body="{ data }">
              <span class="font-bold" :class="{ 'text-red-500': data.quantity <= 0 }">{{ data.quantity }}</span>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
