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
  sellingPrice: number;
  canEdit: boolean;
}>();

const emit = defineEmits<{
  (e: "update:price", value: number): void;
  (e: "update:purchasePrice", value: number | null): void;
  (e: "loaded", data: PurchasePriceHistory): void;
}>();

const { t } = useI18n();
const toast = useToast();
const { formatCurrency } = useCurrencyFormatter();
const { formatDate } = useDatetimeFormatter();
const { getSetting } = useAuth();
const currency = getSetting("finance", "currency") ?? "USD";
const { fetchPurchasePriceHistory } = useVariantClient();

const priceHistory = ref<PurchasePriceHistory | null>(null);
const referencePurchasePrice = ref<number | null>(null);
const localPrice = ref(props.sellingPrice);
const marginType = ref<"percent" | "amount">("percent");
const marginValue = ref<number | null>(null);
const showHistory = ref(false);

const stats = computed(() => priceHistory.value?.stats ?? null);
const hasStats = computed(() => stats.value !== null && (
  stats.value.latest !== null || stats.value.average !== null
));

const marginTypeOptions = computed(() => [
  { label: "%", value: "percent" },
  { label: t("Amount"), value: "amount" },
]);

const canCalculateMargin = computed(
  () => referencePurchasePrice.value !== null && referencePurchasePrice.value > 0,
);

const recalculateMargin = () => {
  const purchasePrice = referencePurchasePrice.value;
  const price = localPrice.value;

  if (purchasePrice === null || purchasePrice <= 0 || price === null || price === undefined || price <= 0) {
    marginValue.value = null;
    return;
  }

  if (marginType.value === "percent") {
    marginValue.value = Math.round(((price - purchasePrice) / price) * 10000) / 100;
  } else {
    marginValue.value = Math.round((price - purchasePrice) * 100) / 100;
  }
};

const recalculatePriceFromMargin = () => {
  const purchasePrice = referencePurchasePrice.value;
  if (purchasePrice === null || purchasePrice <= 0 || marginValue.value === null) {
    return;
  }

  let newPrice: number;
  if (marginType.value === "percent") {
    if (marginValue.value >= 100) {
      return;
    }
    newPrice = Math.round((purchasePrice / (1 - marginValue.value / 100)) * 100) / 100;
  } else {
    newPrice = Math.round((purchasePrice + marginValue.value) * 100) / 100;
  }

  if (newPrice < 0) newPrice = 0;
  localPrice.value = newPrice;
  emit("update:price", newPrice);
};

const onPurchasePriceUpdate = (value: number | null) => {
  referencePurchasePrice.value = value;
  emit("update:purchasePrice", value);
  if (marginValue.value !== null && canCalculateMargin.value) {
    recalculatePriceFromMargin();
  } else {
    recalculateMargin();
  }
};

const onMarginUpdate = (value: number | null) => {
  marginValue.value = value;
  recalculatePriceFromMargin();
};

const onPriceUpdate = (value: number | null) => {
  localPrice.value = value ?? 0;
  emit("update:price", localPrice.value);
  recalculateMargin();
};

const setPurchasePriceFromStat = (stat: "latest" | "average" | "highest" | "lowest") => {
  if (stats.value && stats.value[stat] !== null) {
    onPurchasePriceUpdate(stats.value[stat] as number);
  }
};

const onMarginTypeChange = () => {
  if (canCalculateMargin.value && referencePurchasePrice.value !== null) {
    const purchasePrice = referencePurchasePrice.value;
    if (marginValue.value !== null) {
      if (marginType.value === "amount") {
        const percent = marginValue.value;
        if (percent < 100) {
          marginValue.value = Math.round((purchasePrice * percent / (100 - percent)) * 100) / 100;
        }
      } else {
        const amount = marginValue.value;
        marginValue.value = Math.round((amount / (purchasePrice + amount)) * 10000) / 100;
      }
    }
    recalculatePriceFromMargin();
  }
};

onMounted(async () => {
  try {
    const response = await fetchPurchasePriceHistory(props.variantId);
    priceHistory.value = response.data.data;

    if (response.data.data.latest_purchase_price !== null) {
      referencePurchasePrice.value = response.data.data.latest_purchase_price;
    }

    emit("loaded", response.data.data);
  } catch {
    toast.add({ severity: "error", summary: t("Error"), detail: t("Failed to load purchase price history"), life: 3000 });
  }
});

watch(
  () => props.sellingPrice,
  (newPrice) => {
    localPrice.value = newPrice;
    if (marginValue.value === null) {
      recalculateMargin();
    }
  },
);

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
</script>

<template>
  <div class="flex flex-col gap-4">
    <!-- Purchase Price -->
    <div class="flex flex-col gap-2">
      <label for="purchase-price">{{ t("Purchase Price") }}</label>
      <InputNumber
        id="purchase-price"
        :model-value="referencePurchasePrice"
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
      <small v-if="!canCalculateMargin && referencePurchasePrice === null" class="text-surface-500">
        {{ t("Enter a purchase price to calculate margin") }}
      </small>
    </div>

    <!-- Margin -->
    <div class="flex flex-col gap-2">
      <label for="margin">{{ t("Margin") }}</label>
      <div class="flex items-center gap-2">
        <InputNumber
          id="margin"
          :model-value="marginValue"
          :mode="marginType === 'amount' ? 'currency' : undefined"
          :currency="marginType === 'amount' ? currency : undefined"
          :suffix="marginType === 'percent' ? '%' : undefined"
          :min-fraction-digits="marginType === 'percent' ? 2 : undefined"
          :max-fraction-digits="marginType === 'percent' ? 2 : undefined"
          :disabled="!canEdit || !canCalculateMargin"
          @update:model-value="onMarginUpdate"
          class="flex-1"
        />
        <SelectButton
          v-model="marginType"
          :options="marginTypeOptions"
          option-label="label"
          option-value="value"
          :allow-empty="false"
          @change="onMarginTypeChange"
        />
      </div>
      <small v-if="!canCalculateMargin && referencePurchasePrice !== null" class="text-surface-500">
        {{ t("Purchase price must be greater than zero") }}
      </small>
    </div>

    <!-- Selling Price -->
    <div class="flex flex-col gap-2">
      <label for="selling-price" class="font-semibold">
        {{ t("Price") }}
        <span class="text-red-400">*</span>
      </label>
      <InputNumber
        id="selling-price"
        :model-value="localPrice"
        mode="currency"
        :currency="currency"
        :min="0"
        :disabled="!canEdit"
        @update:model-value="onPriceUpdate"
      />
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