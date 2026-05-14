<script setup lang="ts">
import { Card } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";

defineProps<{
  subTotal: number | null;
  discount: number | null;
  total: number | null;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
</script>

<template>
  <Card>
    <template #title>{{ t("Summary") }}</template>
    <template #content>
      <div class="flex flex-col gap-3">
        <div class="flex justify-between">
          <span class="text-surface-500">{{ t("Sub Total") }}</span>
          <span class="font-medium">{{ formatCurrency(String(subTotal ?? 0)) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-surface-500">{{ t("Discount") }}</span>
          <span class="font-medium">{{ formatCurrency(String(discount ?? 0)) }}</span>
        </div>
        <div class="flex justify-between border-t border-surface-200 dark:border-surface-700 pt-3">
          <span class="font-bold">{{ t("Total") }}</span>
          <span class="font-bold text-lg">{{ formatCurrency(String(total ?? 0)) }}</span>
        </div>
      </div>
    </template>
  </Card>
</template>