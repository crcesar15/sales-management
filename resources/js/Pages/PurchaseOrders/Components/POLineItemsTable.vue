<script setup lang="ts">
import { DataTable, Column, Button, InputNumber, AutoComplete, useToast } from "primevue";
import { useI18n } from "vue-i18n";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { ref, computed, watch } from "vue";
import { usePurchaseOrderClient } from "@/Composables/usePurchaseOrderClient";

export interface LineItem {
  id: string;
  product_variant_id: number;
  product_name: string;
  variant_label: string;
  quantity: number;
  price: number;
  total: number;
}

const props = defineProps<{
  vendorId: number | null;
  modelValue: LineItem[];
}>();

const emit = defineEmits<{
  (e: "update:modelValue", items: LineItem[]): void;
}>();

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const toast = useToast();
const { fetchVendorCatalogApi } = usePurchaseOrderClient();

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const variantSearchResults = ref<any[]>([]);
const variantSearchLoading = ref(false);
const selectedVariant = ref<Record<string, unknown> | null>(null);

const items = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

async function searchVariants(event: { query: string }) {
  if (!props.vendorId) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Select a vendor first"), life: 3000 });
    return;
  }
  if (!event.query || event.query.length < 2) {
    variantSearchResults.value = [];
    return;
  }
  variantSearchLoading.value = true;
  try {
    const response = await fetchVendorCatalogApi(props.vendorId, event.query);
    const data = response.data?.data || response.data || [];
    variantSearchResults.value = Array.isArray(data) ? data : [];
  } catch {
    variantSearchResults.value = [];
  } finally {
    variantSearchLoading.value = false;
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function onVariantSelect(event: { value: any }) {
  const variant = event.value;
  const variantId = variant.product_variant_id ?? variant.id;
  const existingIndex = items.value.findIndex((i) => i.product_variant_id === variantId);

  if (existingIndex !== -1) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Product already added"), life: 3000 });
    selectedVariant.value = null;
    return;
  }

  const productName = variant.product?.name ?? variant.name ?? "—";
  const variantLabel = variant.name ?? variant.identifier ?? productName;
  const price = parseFloat(String(variant.pivot?.price ?? variant.price ?? 0));

  const newItem: LineItem = {
    id: `new-${Date.now()}`,
    product_variant_id: Number(variantId),
    product_name: productName,
    variant_label: variantLabel,
    quantity: 1,
    price,
    total: price,
  };

  emit("update:modelValue", [...items.value, newItem]);
  selectedVariant.value = null;
}

function updateQuantity(index: number, quantity: number) {
  const updated = [...items.value];
  updated[index] = {
    ...updated[index],
    quantity,
    total: quantity * updated[index].price,
  };
  emit("update:modelValue", updated);
}

function removeItem(index: number) {
  const updated = items.value.filter((_, i) => i !== index);
  emit("update:modelValue", updated);
}

watch(() => props.vendorId, () => {
  if (items.value.length > 0) {
    emit("update:modelValue", []);
  }
  selectedVariant.value = null;
  variantSearchResults.value = [];
});
</script>

<template>
  <div>
    <div class="flex flex-col gap-2 mb-3">
      <label>{{ t("Add Product") }}</label>
      <AutoComplete
        v-model="selectedVariant"
        :suggestions="variantSearchResults"
        option-label="name"
        :placeholder="t('Search product...')"
        :loading="variantSearchLoading"
        :disabled="!vendorId"
        dropdown
        force-selection
        @complete="searchVariants"
        @item-select="onVariantSelect"
      >
        <template #option="{ option }">
          <div class="flex justify-between items-center w-full">
            <div>
              <span class="font-medium">{{ option.product?.name ?? option.name }}</span>
              <span v-if="option.identifier" class="text-surface-500 ml-2">
                {{ option.identifier }}
              </span>
            </div>
            <span class="text-surface-500">{{ formatCurrency(String(option.pivot?.price ?? option.price ?? 0)) }}</span>
          </div>
        </template>
      </AutoComplete>
      <small v-if="!vendorId" class="text-surface-400">{{ t("Select a vendor first to add products") }}</small>
    </div>

    <DataTable :value="items" size="small" responsive>
      <template #empty>
        <div class="flex flex-col items-center py-6 text-surface-400">
          <i class="fa fa-cart-plus text-3xl mb-2"></i>
          <span>{{ t("No items added yet") }}</span>
        </div>
      </template>
      <Column :header="t('Product')" style="min-width: 200px">
        <template #body="{ data }">
          <span class="font-medium">{{ data.product_name }}</span>
          <div class="text-sm text-surface-500">{{ data.variant_label }}</div>
        </template>
      </Column>
      <Column :header="t('Unit Price')" style="width: 130px">
        <template #body="{ data }">
          {{ formatCurrency(String(data.price)) }}
        </template>
      </Column>
      <Column :header="t('Quantity')" style="width: 140px">
        <template #body="{ data, index }">
          <InputNumber
            :model-value="data.quantity"
            :min="0.01"
            :max="99999"
            :min-fraction-digits="1"
            :max-fraction-digits="2"
            size="small"
            @update:model-value="(val: number) => updateQuantity(index, val)"
          />
        </template>
      </Column>
      <Column :header="t('Line Total')" style="width: 130px">
        <template #body="{ data }">
          <span class="font-medium">{{ formatCurrency(String(data.total)) }}</span>
        </template>
      </Column>
      <Column style="width: 60px">
        <template #body="{ index }">
          <Button
            icon="fa fa-trash"
            text
            rounded
            size="small"
            severity="danger"
            @click="removeItem(index)"
          />
        </template>
      </Column>
    </DataTable>
  </div>
</template>