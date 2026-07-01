<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { InputNumber, SelectButton, Button, useToast } from "primevue";
import Chart from "primevue/chart";
import { useI18n } from "vue-i18n";
import { useVariantClient } from "@/Composables/useVariantClient";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { useAuth } from "@/Composables/useAuth";
import type { PurchasePriceHistory } from "@/Types/inventory-variant-types";

const props = defineProps<{
  variantId: number;
  purchasePrice: number | null;
  marginType: "percent" | "amount";
  marginValue: number | null;
  price: number;
  canEdit: boolean;
}>();

const { t } = useI18n();
const toast = useToast();
const { formatCurrency } = useCurrencyFormatter();
const { formatDate } = useDatetimeFormatter();
const { getSetting } = useAuth();
const currency = getSetting("finance", "currency") ?? "USD";
const { fetchPurchasePriceHistory } = useVariantClient();

const priceHistory = ref<PurchasePriceHistory | null>(null);
const localPurchasePrice = ref<number | null>(props.purchasePrice);
const localMarginType = ref<"percent" | "amount">(props.marginType);
const localMarginValue = ref<number | null>(props.marginValue);
const localPrice = ref<number>(props.price);
const showHistory = ref(false);

// Prevents infinite watcher loops during programmatic updates
let isProgrammatic = false;

const stats = computed(() => priceHistory.value?.stats ?? null);
const hasStats = computed(() => stats.value !== null && (stats.value.latest !== null || stats.value.average !== null));

const marginTypeOptions = computed(() => [
  { label: "%", value: "percent" },
  { label: currency, value: "amount" },
]);

const canCalculateMargin = computed(() => localPurchasePrice.value !== null && localPurchasePrice.value > 0);

// selling = purchase / (1 - margin/100)  [percent]
// selling = purchase + margin            [amount]
const computePriceFromMargin = (): number | null => {
  if (localPurchasePrice.value === null || localPurchasePrice.value <= 0 || localMarginValue.value === null) {
    return null;
  }
  if (localMarginType.value === "percent") {
    if (localMarginValue.value >= 100) return null;
    return Math.round((localPurchasePrice.value / (1 - localMarginValue.value / 100)) * 100) / 100;
  }
  return Math.round((localPurchasePrice.value + localMarginValue.value) * 100) / 100;
};

// Reverse: derive margin from purchase price + selling price
// margin = (1 - purchase/price) * 100  [percent]
// margin = price - purchase              [amount]
const computeMarginFromPrice = (): number | null => {
  if (localPurchasePrice.value === null || localPurchasePrice.value <= 0 || localPrice.value <= 0) {
    return null;
  }
  if (localMarginType.value === "percent") {
    const margin = (1 - localPurchasePrice.value / localPrice.value) * 100;
    return Math.round(margin * 100) / 100;
  }
  return Math.round((localPrice.value - localPurchasePrice.value) * 100) / 100;
};

// purchase price change → hold margin, recompute price
watch(
  localPurchasePrice,
  () => {
    if (isProgrammatic) return;
    const newPrice = computePriceFromMargin();
    if (newPrice !== null) {
      isProgrammatic = true;
      localPrice.value = newPrice;
      isProgrammatic = false;
    }
  },
  { flush: "sync" },
);

// margin change → hold purchase, recompute price
watch(
  localMarginValue,
  () => {
    if (isProgrammatic) return;
    const newPrice = computePriceFromMargin();
    if (newPrice !== null) {
      isProgrammatic = true;
      localPrice.value = newPrice;
      isProgrammatic = false;
    }
  },
  { flush: "sync" },
);

// selling price change → hold purchase, recompute margin
watch(
  localPrice,
  () => {
    if (isProgrammatic) return;
    const newMargin = computeMarginFromPrice();
    if (newMargin !== null) {
      isProgrammatic = true;
      localMarginValue.value = newMargin;
      isProgrammatic = false;
    }
  },
  { flush: "sync" },
);

const onPurchasePriceUpdate = (value: number | null) => {
  localPurchasePrice.value = value;
};

const onMarginUpdate = (value: number | null) => {
  localMarginValue.value = value;
};

const onPriceUpdate = (value: number | null) => {
  localPrice.value = value ?? 0;
};

const onMarginTypeChange = () => {
  // Convert margin value to keep the selling price constant
  if (canCalculateMargin.value && localPurchasePrice.value !== null && localMarginValue.value !== null) {
    const purchasePrice = localPurchasePrice.value;
    isProgrammatic = true;
    if (localMarginType.value === "amount") {
      const percent = localMarginValue.value;
      if (percent < 100) {
        localMarginValue.value = Math.round(((purchasePrice * percent) / (100 - percent)) * 100) / 100;
      }
    } else {
      const amount = localMarginValue.value;
      localMarginValue.value = Math.round((amount / (purchasePrice + amount)) * 10000) / 100;
    }
    isProgrammatic = false;
  }
};

const setPurchasePriceFromStat = (stat: "latest" | "average" | "highest" | "lowest") => {
  if (stats.value && stats.value[stat] !== null) {
    onPurchasePriceUpdate(stats.value[stat] as number);
  }
};

onMounted(async () => {
  try {
    const response = await fetchPurchasePriceHistory(props.variantId);
    priceHistory.value = response.data.data;

    if (localPurchasePrice.value === null && response.data.data.latest_purchase_price !== null) {
      localPurchasePrice.value = response.data.data.latest_purchase_price;
    }
  } catch {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t("Failed to load purchase price history"),
      life: 3000,
    });
  }
});

const chartData = computed(() => {
  const history = priceHistory.value?.history ?? [];
  const entries = [...history].reverse();
  return {
    labels: entries.map((h) => formatDate(h.date)),
    datasets: [
      {
        label: t("Purchase Price"),
        data: entries.map((h) => h.price),
        borderColor: "#3b82f6",
        backgroundColor: "rgba(59, 130, 246, 0.1)",
        fill: true,
        tension: 0.3,
        pointRadius: 4,
      },
    ],
  };
});

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      callbacks: {
        label: (context: { raw: number }) => formatCurrency(String(context.raw)),
      },
    },
  },
  scales: {
    y: {
      beginAtZero: false,
      ticks: {
        callback: (value: number) => formatCurrency(String(value)),
      },
    },
    x: {
      ticks: {
        maxRotation: 45,
      },
    },
  },
}));

const breakdownText = computed(() => {
  if (!canCalculateMargin.value) {
    return t("Set purchase price to enable auto-calculation");
  }
  if (localMarginValue.value === null) {
    return t("Enter margin or selling price to calculate");
  }
  if (localPrice.value <= 0) {
    return t("Enter selling price or margin to calculate");
  }
  const marginDisplay = localMarginType.value === "percent" ? `${localMarginValue.value}%` : formatCurrency(String(localMarginValue.value));
  return `${formatCurrency(String(localPurchasePrice.value))} + ${marginDisplay} = ${formatCurrency(String(localPrice.value))}`;
});

defineExpose({
  getValues: () => ({
    purchase_price: localPurchasePrice.value,
    margin_type: localMarginType.value,
    margin_value: localMarginValue.value,
    price: localPrice.value,
  }),
});
</script>

<template>
  <div class="flex flex-col gap-4">
    <!-- Purchase Price & Margin Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Purchase Price -->
      <div class="flex flex-col gap-2">
        <label for="purchase-price">
          {{ t("Purchase Price") }}
          <span class="text-red-400">*</span>
        </label>
        <InputNumber
          id="purchase-price"
          :model-value="localPurchasePrice"
          mode="currency"
          :currency="currency"
          :min="0"
          :disabled="!canEdit"
          placeholder="0"
          @update:model-value="onPurchasePriceUpdate"
        />
        <div v-if="hasStats" class="flex flex-wrap gap-2">
          <Button
            :label="t('Latest')"
            size="small"
            severity="secondary"
            :disabled="!canEdit || stats?.latest === null"
            @click="setPurchasePriceFromStat('latest')"
          />
          <Button
            :label="t('Average')"
            size="small"
            severity="secondary"
            :disabled="!canEdit || stats?.average === null"
            @click="setPurchasePriceFromStat('average')"
          />
          <Button
            :label="t('Highest')"
            size="small"
            severity="secondary"
            :disabled="!canEdit || stats?.highest === null"
            @click="setPurchasePriceFromStat('highest')"
          />
          <Button
            :label="t('Lowest')"
            size="small"
            severity="secondary"
            :disabled="!canEdit || stats?.lowest === null"
            @click="setPurchasePriceFromStat('lowest')"
          />
        </div>
      </div>

      <!-- Margin -->
      <div class="flex flex-col gap-2">
        <label for="margin">
          {{ t("Margin") }}
          <span class="text-red-400">*</span>
        </label>
        <div class="flex items-center gap-2">
          <InputNumber
            id="margin"
            :model-value="localMarginValue"
            :mode="localMarginType === 'amount' ? 'currency' : undefined"
            :currency="localMarginType === 'amount' ? currency : undefined"
            :suffix="localMarginType === 'percent' ? '%' : undefined"
            :min-fraction-digits="localMarginType === 'percent' ? 2 : undefined"
            :max-fraction-digits="localMarginType === 'percent' ? 2 : undefined"
            :disabled="!canEdit || !canCalculateMargin"
            @update:model-value="onMarginUpdate"
            class="flex-1"
          />
          <SelectButton
            v-model="localMarginType"
            :options="marginTypeOptions"
            option-label="label"
            option-value="value"
            :allow-empty="false"
            :disabled="!canEdit"
            @change="onMarginTypeChange"
          />
        </div>
        <small v-if="!canCalculateMargin && localPurchasePrice !== null" class="text-surface-500">
          {{ t("Purchase price must be greater than zero") }}
        </small>
      </div>
    </div>

    <!-- Selling Price (Editable, auto-linked to margin) -->
    <div class="flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <label for="selling-price" class="font-semibold">{{ t("Selling Price") }}</label>
        <i
          class="fa-solid fa-calculator text-sm text-surface-400"
          v-tooltip="t('Auto-calculated from purchase price and margin. Edit to override.')"
        />
      </div>
      <InputNumber
        id="selling-price"
        :model-value="localPrice"
        mode="currency"
        :currency="currency"
        :min="0"
        :disabled="!canEdit"
        placeholder="0"
        @update:model-value="onPriceUpdate"
      />
      <small class="text-surface-500">{{ breakdownText }}</small>
    </div>

    <!-- Price History Toggle -->
    <div v-if="(priceHistory?.history?.length ?? 0) > 0">
      <Button
        :label="showHistory ? t('Hide price history') : t('Show price history')"
        icon="fa fa-chart-line"
        link
        size="small"
        @click="showHistory = !showHistory"
      />
    </div>

    <!-- Price History Chart -->
    <div v-if="showHistory" class="mt-2" style="height: 250px">
      <Chart type="line" :data="chartData" :options="chartOptions" class="w-full h-full" />
    </div>
  </div>
</template>
