<script setup lang="ts">
import { Button, Card, Badge, Tag } from "primevue";

import AppLayout from "@layouts/admin.vue";
import { router, usePage } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import { route } from "ziggy-js";
import type { InventoryVariantDetail, InventoryProductDetail } from "@/Types/inventory-variant-types";
import type { StockStoreBreakdown } from "@/Types/stock-overview-types";
import { useAuth } from "@/Composables/useAuth";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import VariantDetails from "./Components/VariantDetails.vue";
import ImagesTab from "./Components/ImagesTab.vue";
import UnitsTab from "./Components/UnitsTab.vue";
import StockTab from "./Components/StockTab.vue";

defineOptions({ layout: AppLayout });
const props = defineProps<{
  product: InventoryProductDetail;
  variant: InventoryVariantDetail;
  stores: StockStoreBreakdown[];
}>();
const { t } = useI18n();
const page = usePage();
const { can } = useAuth();
const { formatCurrency } = useCurrencyFormatter();
const { formatDatetime } = useDatetimeFormatter();

const canEdit = computed(() => can("inventory.edit"));

const from = computed(() => (page.url.includes("from=product") ? "product" : "inventory"));

const goBack = () => {
  if (from.value === "product") {
    router.visit(route("products.edit", { product: props.product.id }));
  } else {
    router.visit(route("inventory.variants"));
  }
};

const variantDisplayName = computed(() => {
  if (props.variant.values?.length) {
    return props.variant.values.map((v) => v.value).join(" / ");
  }
  return props.variant.name || props.product.name;
});

const variantStatusSeverity = computed(() => {
  const map: Record<string, "success" | "warn" | "danger"> = {
    active: "success",
    inactive: "warn",
    archived: "danger",
  };
  return map[props.variant.status] ?? "info";
});

const stockClass = computed(() => (props.variant.stock <= 0 ? "text-red-500 font-bold" : "font-bold"));
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-center mb-3 gap-2">
      <div class="flex items-center gap-2">
        <Button icon="fa fa-arrow-left" text rounded @click="goBack" />
        <h2 class="text-2xl font-bold m-0 flex items-center flex-wrap gap-2">
          {{ product.name }}
          <Badge v-if="variant.values?.length" :value="variantDisplayName" severity="primary" />
        </h2>
      </div>
      <Button
        v-if="canEdit"
        :label="t('Edit product')"
        icon="fa fa-arrow-up-right-from-square"
        @click="router.visit(route('products.edit', { product: props.product.id }))"
      />
    </div>

    <!-- Content Grid -->
    <div class="grid grid-cols-12 gap-4">
      <!-- Main Column -->
      <div class="col-span-12 lg:col-span-8 flex flex-col gap-4">
        <Card>
          <template #title>{{ t("Details") }}</template>
          <template #content>
            <VariantDetails :product="product" :variant="variant" :can-edit="canEdit" />
          </template>
        </Card>

        <Card>
          <template #title>{{ t("Units") }}</template>
          <template #content>
            <UnitsTab :product="product" :variant="variant" />
          </template>
        </Card>

        <Card>
          <template #title>{{ t("Stock by Store") }}</template>
          <template #content>
            <StockTab :stores="stores" />
          </template>
        </Card>

        <Card v-if="variant.values?.length">
          <template #title>{{ t("Images") }}</template>
          <template #content>
            <ImagesTab :product="product" :variant="variant" />
          </template>
        </Card>
      </div>

      <!-- Summary Sidebar -->
      <div class="col-span-12 lg:col-span-4">
        <Card>
          <template #title>{{ t("Summary") }}</template>
          <template #content>
            <div class="flex flex-col gap-4 mb-3">
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Status") }}</span>
                <Tag
                  :value="t(variant.status.charAt(0).toUpperCase() + variant.status.slice(1))"
                  :severity="variantStatusSeverity"
                  rounded
                />
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Price") }}</span>
                <span class="font-bold">{{ formatCurrency(String(variant.price)) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Stock") }}</span>
                <span :class="stockClass">{{ variant.stock }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Brand") }}</span>
                <span class="font-medium">{{ product.brand?.name ?? "—" }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Measurement Unit") }}</span>
                <span class="font-medium">{{ product.measurement_unit?.name ?? "—" }}</span>
              </div>
              <div v-if="product.categories?.length">
                <span class="text-surface-500 block mb-2">{{ t("Categories") }}</span>
                <div class="flex flex-wrap gap-2">
                  <Badge
                    v-for="c in product.categories"
                    :key="c.id"
                    size="large"
                    severity="secondary"
                    class="!capitalize"
                    rounded
                  >
                    <i class="fa fa-tag mr-1.5" />
                    {{ c.name }}
                  </Badge>
                </div>
              </div>
              <div class="border-t border-surface-200 pt-3 flex justify-between">
                <span class="text-surface-500">{{ t("Created") }}</span>
                <span class="font-medium">{{ formatDatetime(variant.created_at) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Updated") }}</span>
                <span class="font-medium">{{ formatDatetime(variant.updated_at) }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>