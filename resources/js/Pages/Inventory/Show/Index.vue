<script setup lang="ts">
import { Button, Card, Select, Tab, TabList, TabPanel, TabPanels, Tabs } from "primevue";

import { Head, router } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import AppLayout from "@layouts/admin.vue";
import { useAuth } from "@composables/useAuth";
import type { InventoryProductDetail, InventoryVariantDetail, VariantNavigationItem } from "@app-types/inventory-variant-types";
import type { MeasurementUnitResponse } from "@app-types/measurement-unit-types";
import type { StockStoreBreakdown } from "@app-types/stock-overview-types";
import ImagesTab from "./Components/ImagesTab.vue";
import OverviewTab from "./Components/OverviewTab.vue";
import UnitsTab from "./Components/UnitsTab.vue";
import VariantContextPanel from "./Components/VariantContextPanel.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  product: InventoryProductDetail;
  variant: InventoryVariantDetail;
  siblings: VariantNavigationItem[];
  stores: StockStoreBreakdown[];
  measurementUnits: Pick<MeasurementUnitResponse, "id" | "name" | "abbreviation">[];
}>();
const { t } = useI18n();
const { can } = useAuth();

const activeTab = ref<"overview" | "units" | "images">("overview");
const overviewDirty = ref(false);
const imagesDirty = ref(false);
const canEdit = computed(() => can("inventory.edit"));
const canEditProduct = computed(() => can("product.edit"));
const isPageDirty = computed(() => overviewDirty.value || imagesDirty.value);
const currentSiblingIndex = computed(() => props.siblings.findIndex((sibling) => sibling.id === props.variant.id));
const previousSibling = computed(() => props.siblings[currentSiblingIndex.value - 1] ?? null);
const nextSibling = computed(() => props.siblings[currentSiblingIndex.value + 1] ?? null);

const visitVariant = (variantId: number) => {
  if (variantId === props.variant.id) return;

  router.visit(route("inventory.variants.show", { variant: variantId }));
};

const goBack = () => {
  router.visit(route("inventory.variants"));
};

const handleBeforeUnload = (event: BeforeUnloadEvent) => {
  if (!isPageDirty.value) return;

  event.preventDefault();
  event.returnValue = true;
};

let removeBeforeListener: (() => void) | undefined;

onMounted(() => {
  removeBeforeListener = router.on("before", (event) => {
    if (!isPageDirty.value || event.detail.visit.method !== "get") return;

    return window.confirm(t("You have unsaved changes. Leave this page without saving?"));
  });
  window.addEventListener("beforeunload", handleBeforeUnload);
});

onUnmounted(() => {
  removeBeforeListener?.();
  window.removeEventListener("beforeunload", handleBeforeUnload);
});
</script>

<template>
  <div>
    <Head :title="`${t('Configure Variant')} · ${product.name}`" />

    <header class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div class="flex min-w-0 items-center gap-2">
        <Button icon="fa fa-arrow-left" text rounded :aria-label="t('Back to inventory')" @click="goBack" />
        <div class="min-w-0">
          <h1 class="m-0 truncate text-2xl font-bold">{{ product.name }}</h1>
        </div>
      </div>

      <div v-if="siblings.length > 1" class="flex w-full items-center gap-2 sm:w-auto">
        <Button
          icon="fa fa-chevron-left"
          text
          rounded
          :aria-label="t('Previous variant')"
          :disabled="previousSibling === null"
          @click="previousSibling && visitVariant(previousSibling.id)"
        />
        <Select
          :model-value="variant.id"
          :options="siblings"
          option-label="label"
          option-value="id"
          class="min-w-0 flex-1 sm:w-72 sm:flex-none"
          :aria-label="t('Select variant')"
          @update:model-value="visitVariant"
        />
        <Button
          icon="fa fa-chevron-right"
          text
          rounded
          :aria-label="t('Next variant')"
          :disabled="nextSibling === null"
          @click="nextSibling && visitVariant(nextSibling.id)"
        />
        <span class="hidden text-sm text-surface-500 dark:text-surface-400 lg:inline">
          {{ t("Variant {current} of {total}", { current: currentSiblingIndex + 1, total: siblings.length }) }}
        </span>
      </div>
    </header>

    <div class="grid grid-cols-12 gap-4">
      <main class="col-span-12 lg:col-span-8" aria-label="Variant configuration">
        <Card>
          <template #content>
            <Tabs v-model:value="activeTab">
              <TabList>
                <Tab value="overview">
                  <span class="flex items-center gap-2">
                    {{ t("Overview") }}
                    <i v-if="overviewDirty" class="fa fa-circle text-[0.45rem] text-primary" :title="t('Unsaved changes')" aria-hidden="true" />
                  </span>
                </Tab>
                <Tab value="units">{{ t("Units") }}</Tab>
                <Tab v-if="product.has_variants" value="images">
                  <span class="flex items-center gap-2">
                    {{ t("Images") }}
                    <i v-if="imagesDirty" class="fa fa-circle text-[0.45rem] text-primary" :title="t('Unsaved changes')" aria-hidden="true" />
                  </span>
                </Tab>
              </TabList>
              <TabPanels>
                <TabPanel value="overview">
                  <OverviewTab :variant="variant" :can-edit="canEdit" @update:dirty="overviewDirty = $event" />
                </TabPanel>
                <TabPanel value="units">
                  <UnitsTab :product="product" :variant="variant" :measurement-units="measurementUnits" />
                </TabPanel>
                <TabPanel v-if="product.has_variants" value="images">
                  <ImagesTab
                    :product="product"
                    :variant="variant"
                    :can-edit="canEdit"
                    :can-edit-product="canEditProduct"
                    @update:dirty="imagesDirty = $event"
                  />
                </TabPanel>
              </TabPanels>
            </Tabs>
          </template>
        </Card>
      </main>

      <aside class="col-span-12 lg:col-span-4" :aria-label="t('Summary')">
        <VariantContextPanel :product="product" :variant="variant" :stores="stores" :can-edit-product="canEditProduct" />
      </aside>
    </div>
  </div>
</template>
