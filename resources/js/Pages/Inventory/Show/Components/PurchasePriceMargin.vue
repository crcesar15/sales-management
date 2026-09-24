<script setup lang="ts">
import { Button, InputNumber, SelectButton, useToast } from "primevue";
import Chart from "primevue/chart";

import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useAuth } from "@composables/useAuth";
import { useCurrencyFormatter } from "@composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { useVariantClient } from "@composables/useVariantClient";
import type { PurchasePriceHistory } from "@app-types/inventory-variant-types";

const props = defineProps<{
  variantId: number;
  canEdit: boolean;
}>();
const purchasePrice = defineModel<number | null>("purchasePrice", { required: true });
const marginType = defineModel<"percent" | "amount">("marginType", { required: true });
const marginValue = defineModel<number | null>("marginValue", { required: true });
const price = defineModel<number>("price", { required: true });
const { t } = useI18n();
const toast = useToast();
const { getSetting } = useAuth();
const { formatCurrency } = useCurrencyFormatter();
const { formatDate } = useDatetimeFormatter();
const { fetchPurchasePriceHistory } = useVariantClient();

const currency = getSetting("finance", "currency") ?? "USD";
const showHistory = ref(false);
const historyLoading = ref(false);
const historyLoaded = ref(false);
const priceHistory = ref<PurchasePriceHistory | null>(null);
let isProgrammatic = false;

const canCalculateMargin = computed(() => purchasePrice.value !== null && purchasePrice.value > 0);
const marginTypeOptions = computed(() => [
  { label: "%", value: "percent" },
  { label: currency, value: "amount" },
]);
const stats = computed(() => priceHistory.value?.stats ?? null);
const hasStats = computed(() => stats.value !== null && Object.values(stats.value).some((stat) => stat !== null));
const historyStats = ["latest", "average", "highest", "lowest"] as const;

const computePriceFromMargin = (): number | null => {
  if (purchasePrice.value === null || purchasePrice.value <= 0 || marginValue.value === null) return null;

  if (marginType.value === "percent") {
    if (marginValue.value >= 100) return null;

    return Math.round((purchasePrice.value / (1 - marginValue.value / 100)) * 100) / 100;
  }

  return Math.round((purchasePrice.value + marginValue.value) * 100) / 100;
};

const computeMarginFromPrice = (): number | null => {
  if (purchasePrice.value === null || purchasePrice.value <= 0 || price.value <= 0) return null;

  if (marginType.value === "percent") {
    return Math.round((1 - purchasePrice.value / price.value) * 10000) / 100;
  }

  return Math.round((price.value - purchasePrice.value) * 100) / 100;
};

watch([purchasePrice, marginValue], () => {
  if (isProgrammatic) return;

  const nextPrice = computePriceFromMargin();
  if (nextPrice !== null) {
    isProgrammatic = true;
    price.value = nextPrice;
    isProgrammatic = false;
  }
});

watch(price, () => {
  if (isProgrammatic) return;

  const nextMargin = computeMarginFromPrice();
  if (nextMargin !== null) {
    isProgrammatic = true;
    marginValue.value = nextMargin;
    isProgrammatic = false;
  }
});

const onMarginTypeChange = () => {
  if (!canCalculateMargin.value || purchasePrice.value === null || marginValue.value === null) return;

  isProgrammatic = true;
  if (marginType.value === "amount") {
    marginValue.value = marginValue.value < 100 ? Math.round(((purchasePrice.value * marginValue.value) / (100 - marginValue.value)) * 100) / 100 : null;
  } else {
    marginValue.value = Math.round((marginValue.value / (purchasePrice.value + marginValue.value)) * 10000) / 100;
  }
  isProgrammatic = false;
};

const setPurchasePriceFromStat = (stat: "latest" | "average" | "highest" | "lowest") => {
  if (stats.value?.[stat] !== null && stats.value?.[stat] !== undefined) {
    purchasePrice.value = stats.value[stat];
  }
};

const loadHistory = async () => {
  if (historyLoaded.value || historyLoading.value) return;

  historyLoading.value = true;
  try {
    const response = await fetchPurchasePriceHistory(props.variantId);
    priceHistory.value = response.data.data;
    historyLoaded.value = true;
  } catch {
    toast.add({ severity: "error", summary: t("Error"), detail: t("Failed to load purchase price history"), life: 3000 });
  } finally {
    historyLoading.value = false;
  }
};

const toggleHistory = async () => {
  showHistory.value = !showHistory.value;
  if (showHistory.value) await loadHistory();
};

const chartData = computed(() => {
  const history = [...(priceHistory.value?.history ?? [])].reverse();

  return {
    labels: history.map((entry) => formatDate(entry.date)),
    datasets: [
      {
        label: t("Purchase Price"),
        data: history.map((entry) => entry.price),
        borderColor: "#00539b",
        backgroundColor: "rgba(0, 83, 155, 0.12)",
        fill: true,
        tension: 0.25,
        pointRadius: 3,
      },
    ],
  };
});

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (context: { raw: number }) => formatCurrency(String(context.raw)),
      },
    },
  },
  scales: {
    y: {
      beginAtZero: false,
      ticks: { callback: (value: number) => formatCurrency(String(value)) },
    },
  },
}));

const breakdownText = computed(() => {
  if (!canCalculateMargin.value) return t("Set purchase price to enable auto-calculation");
  if (marginValue.value === null) return t("Enter margin or selling price to calculate");
  if (price.value <= 0) return t("Enter selling price or margin to calculate");

  const margin = marginType.value === "percent" ? `${marginValue.value}%` : formatCurrency(String(marginValue.value));

  return `${formatCurrency(String(purchasePrice.value))} + ${margin} = ${formatCurrency(String(price.value))}`;
});
</script>

<template>
  <div class="flex flex-col gap-5">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div class="flex flex-col gap-2">
        <label for="purchase-price">{{ t("Purchase Price") }}</label>
        <InputNumber
          input-id="purchase-price"
          v-model="purchasePrice"
          mode="currency"
          :currency="currency"
          :min="0"
          :disabled="!canEdit"
          placeholder="0"
          autocomplete="off"
        />
        <small class="text-surface-500 dark:text-surface-400">{{ t("Optional. Used to calculate margin and suggested selling price.") }}</small>
      </div>

      <div class="flex flex-col gap-2">
        <label for="margin">{{ t("Margin") }}</label>
        <div class="flex items-center gap-2">
          <InputNumber
            input-id="margin"
            v-model="marginValue"
            :mode="marginType === 'amount' ? 'currency' : undefined"
            :currency="marginType === 'amount' ? currency : undefined"
            :suffix="marginType === 'percent' ? '%' : undefined"
            :min-fraction-digits="marginType === 'percent' ? 2 : undefined"
            :max-fraction-digits="marginType === 'percent' ? 2 : undefined"
            :min="0"
            :disabled="!canEdit || !canCalculateMargin"
            class="flex-1"
          />
          <SelectButton
            v-model="marginType"
            :options="marginTypeOptions"
            option-label="label"
            option-value="value"
            :allow-empty="false"
            :disabled="!canEdit"
            aria-label="Margin type"
            @change="onMarginTypeChange"
          />
        </div>
        <small v-if="!canCalculateMargin" class="text-surface-500 dark:text-surface-400">{{ t("Set purchase price to enable auto-calculation") }}</small>
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <label for="selling-price" class="font-semibold">{{ t("Selling Price") }}</label>
      <InputNumber
        input-id="selling-price"
        v-model="price"
        mode="currency"
        :currency="currency"
        :min="0"
        :disabled="!canEdit"
        placeholder="0"
      />
      <small class="text-surface-500 dark:text-surface-400">{{ breakdownText }}</small>
    </div>

    <details class="border-t border-surface-200 pt-4 dark:border-surface-700" :open="showHistory">
      <summary class="flex cursor-pointer list-none items-center justify-between gap-3" @click.prevent="toggleHistory">
        <span class="font-medium">{{ t("Purchase History") }}</span>
        <i :class="showHistory ? 'fa fa-chevron-up' : 'fa fa-chevron-down'" aria-hidden="true" />
      </summary>
      <div v-if="showHistory" class="mt-4 flex flex-col gap-4">
        <div v-if="historyLoading" class="text-sm text-surface-500 dark:text-surface-400">{{ t("Loading purchase history...") }}</div>
        <template v-else-if="priceHistory">
          <div v-if="hasStats" class="flex flex-wrap gap-2">
            <Button
              v-for="stat in historyStats"
              :key="stat"
              :label="t(stat.charAt(0).toUpperCase() + stat.slice(1))"
              size="small"
              severity="secondary"
              :disabled="!canEdit || stats?.[stat] === null"
              @click="setPurchasePriceFromStat(stat)"
            />
          </div>
          <div v-if="priceHistory.history.length" class="h-60">
            <Chart type="line" :data="chartData" :options="chartOptions" class="h-full w-full" />
          </div>
          <ul v-if="priceHistory.history.length" class="m-0 flex list-none flex-col gap-2 p-0 text-sm">
            <li v-for="entry in priceHistory.history" :key="`${entry.po_id}-${entry.date}`" class="flex justify-between gap-3">
              <span>{{ formatDate(entry.date) }}<span v-if="entry.vendor_name"> · {{ entry.vendor_name }}</span></span>
              <span class="font-medium">{{ formatCurrency(String(entry.price)) }}</span>
            </li>
          </ul>
          <p v-else class="m-0 text-sm text-surface-500 dark:text-surface-400">{{ t("No purchase price history found") }}</p>
        </template>
      </div>
    </details>
  </div>
</template>
