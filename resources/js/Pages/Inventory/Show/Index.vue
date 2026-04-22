<script setup lang="ts">
import { Button, Card, Badge } from "primevue";

import AppLayout from "@layouts/admin.vue";
import { router, usePage } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import { route } from "ziggy-js";
import type { InventoryVariantDetail, InventoryProductDetail } from "@/Types/inventory-variant-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import VariantDetails from "./Components/VariantDetails.vue";
import ImagesTab from "./Components/ImagesTab.vue";
import UnitsTab from "./Components/UnitsTab.vue";

defineOptions({ layout: AppLayout });
const props = defineProps<{
  product: InventoryProductDetail;
  variant: InventoryVariantDetail;
}>();
const { t } = useI18n();
const page = usePage();
const { formatCurrency } = useCurrencyFormatter();

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
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex justify-between mb-3">
      <div class="flex items-center">
        <Button icon="fa fa-arrow-left" text severity="secondary" class="hover:shadow-md mr-2" @click="goBack" />
        <div>
          <h4 class="text-2xl font-bold flex items-center m-0">
            {{ product.name }}
            <Badge v-if="variant.values?.length" :value="variantDisplayName" severity="primary" class="ml-2" />
          </h4>
        </div>
      </div>
      <Button
        :label="t('Edit product')"
        icon="fa fa-arrow-up-right-from-square"
        variant="text"
        @click="router.visit(route('products.edit', { product: props.product.id }))"
      />
    </div>

    <!-- Content Cards -->
    <div class="flex flex-col gap-4">
      <Card>
        <template #title>
          <div class="flex items-center justify-between">
            <div>
              {{ t("Details") }}
            </div>
            <div class="flex items-center gap-2">
              <div class="flex items-center gap-2">
                <Badge size="large" v-tooltip.top="t('Brand')">
                  <template #default>
                    <div class="flex items-center gap-2">
                      <i class="fa fa-building"></i>
                      <span>{{ product.brand?.name }}</span>
                    </div>
                  </template>
                </Badge>
              </div>
              <div class="flex items-center gap-2">
                <Badge v-for="c in product.categories" :key="c.id" size="large" v-tooltip.top="t('Category')">
                  <template #default>
                    <div class="flex items-center gap-2">
                      <i class="fa fa-tag"></i>
                      <span>{{ c.name }}</span>
                    </div>
                  </template>
                </Badge>
              </div>
            </div>
          </div>
        </template>
        <template #content>
          <VariantDetails :product="product" :variant="variant" />
        </template>
      </Card>

      <Card>
        <template #title>{{ t("Images") }}</template>
        <template #content>
          <ImagesTab :product="product" :variant="variant" />
        </template>
      </Card>

      <Card>
        <template #title>{{ t("Units") }}</template>
        <template #content>
          <UnitsTab :product="product" :variant="variant" />
        </template>
      </Card>
    </div>
  </div>
</template>
