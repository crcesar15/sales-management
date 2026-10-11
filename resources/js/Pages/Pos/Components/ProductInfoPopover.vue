<script setup lang="ts">
import { Popover } from "primevue";
import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import type { PosProductInfo } from "@/Types/pos";

const props = defineProps<{
  info: PosProductInfo | null;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const popover = ref();

function toggle(event: Event): void {
  popover.value?.toggle(event);
}

function showSaleUnit(info: PosProductInfo): boolean {
  return info.sale_unit_name !== null && info.sale_unit_name !== (info.base_unit_name ?? t("Unit"));
}

defineExpose({ toggle });

watch(
  () => props.info,
  (value) => {
    if (value === null) popover.value?.hide();
  },
);
</script>

<template>
  <Popover ref="popover">
    <div v-if="info" class="w-96">
      <div class="mb-1 text-xs font-medium uppercase text-surface-500 dark:text-surface-400">{{ t("Product Details") }}</div>
      <div class="font-semibold">{{ info.product_name }}</div>
      <div v-if="info.brand_name" class="mt-0.5 text-surface-500 dark:text-surface-400">{{ t("Brand") }}: {{ info.brand_name }}</div>

      <div class="mt-3 flex flex-col gap-2 border-t border-surface-200 pt-3 dark:border-surface-700">
        <div class="flex items-center justify-between gap-3">
          <span class="text-surface-500 dark:text-surface-400">{{ t("Unit Price") }}</span>
          <span class="font-semibold tabular-nums">{{ formatCurrency(String(info.unit_price)) }}</span>
        </div>
        <div class="flex items-center justify-between gap-3">
          <span class="text-surface-500 dark:text-surface-400">{{ t("Base Unit") }}</span>
          <span>{{ info.base_unit_name ?? t("Unit") }}</span>
        </div>
        <div v-if="showSaleUnit(info)" class="flex items-center justify-between gap-3">
          <span class="text-surface-500 dark:text-surface-400">{{ t("Sale Unit") }}</span>
          <span>{{ info.sale_unit_name }}</span>
        </div>
        <div v-if="info.conversion_factor !== 1" class="flex items-center justify-between gap-3">
          <span class="text-surface-500 dark:text-surface-400">{{ t("Conversion Factor") }}</span>
          <span class="tabular-nums">
            1 {{ info.sale_unit_name ?? info.base_unit_name ?? t("Unit") }} = {{ info.conversion_factor }}
            {{ info.base_unit_name ?? t("Unit") }}
          </span>
        </div>
      </div>
    </div>
  </Popover>
</template>
