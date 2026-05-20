<script setup lang="ts">
import {
  Dialog,
  Galleria,
  Button,
  Tag,
  DataTable,
  Column,
  Card,
} from "primevue";
import { computed } from "vue";
import type { ProductListResponse } from "@/Types/product-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import useDatetimeFormatter from "@/Composables/useDatetimeFormatter";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useAuth } from "@/Composables/useAuth";

const props = defineProps<{
  product: ProductListResponse | null;
}>();

const showDialog = defineModel<boolean>("showDialog", {
  default: false,
});

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();
const { getSetting } = useAuth();

const productMedia = computed(() => props.product?.media ?? []);

const formatDate = (dateStr?: string | null): string => {
  if (!dateStr) return "—";
  return useDatetimeFormatter(dateStr, getSetting("general", "date_format") ?? "YYYY-MM-DD");
};

const goToEdit = () => {
  if (props.product) {
    router.visit(route("products.edit", { product: props.product.id }));
  }
};

const closeModal = () => {
  showDialog.value = false;
};

const statusSeverity = computed(() => {
  if (props.product?.status === "active") return "success";
  if (props.product?.status === "inactive") return "warn";
  return "danger";
});

const statusLabel = computed(() => {
  if (props.product?.status === "active") return t("Active");
  if (props.product?.status === "inactive") return t("Inactive");
  return t("Archived");
});

</script>

<template>
  <Dialog
    v-model:visible="showDialog"
    modal
    :closable="false"
    :dismissable-mask="true"
    :style="{ width: '920px', maxWidth: '95vw' }"
    :breakpoints="{ '768px': '98vw' }"
    @hide="closeModal"
  >
    <template #header>
      <div v-if="product" class="flex items-center justify-between w-full">
        <span class="text-lg font-semibold truncate">{{ product.name }}</span>
        <Tag :severity="statusSeverity" :value="statusLabel" class="shrink-0" />
      </div>
    </template>

    <div v-if="product" class="flex flex-col lg:flex-row gap-5">
      <!-- Image Column -->
      <div class="shrink-0 flex flex-col items-center lg:self-start">
        <Galleria
          v-if="productMedia.length > 1"
          :value="productMedia"
          :show-item-navigators="true"
          :show-item-navigators-on-hover="true"
          :show-thumbnails="false"
          :show-indicators="true"
          :circular="true"
          container-style="max-width: 250px"
        >
          <template #item="slotProps">
            <img
              :src="slotProps.item.full_url"
              :alt="product.name"
              class="rounded-lg"
              style="width: 250px; height: 250px; object-fit: cover"
            />
          </template>
        </Galleria>
        <img
          v-else-if="productMedia.length === 1"
          :src="productMedia[0].full_url"
          :alt="product.name"
          class="rounded-lg border border-surface-200 dark:border-surface-700"
          style="width: 250px; height: 250px; object-fit: cover"
        />
        <div
          v-else
          class="rounded-lg bg-surface-100 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 flex items-center justify-center"
          style="width: 250px; height: 250px"
        >
          <span class="text-3xl font-bold text-muted-color">{{ product.name.substring(0, 2).toUpperCase() }}</span>
        </div>
      </div>

      <!-- Details Column -->
      <div class="flex-1 min-w-0 flex flex-col gap-4">
        <!-- Pricing & Inventory -->
        <Card :pt="{ root: { class: '' }, body: { class: 'bg-surface-50 dark:bg-surface-900' } }">
          <template #title>{{ t("Pricing & Inventory") }}</template>
          <template #content>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-surface-500 text-sm block mb-1">{{ t("Price") }}</span>
                <span class="font-semibold">
                  <template v-if="product.price_min !== product.price_max">
                    {{ formatCurrency(String(product.price_min)) }} &ndash; {{ formatCurrency(String(product.price_max)) }}
                  </template>
                  <template v-else>
                    {{ formatCurrency(String(product.price_min ?? 0)) }}
                  </template>
                </span>
              </div>
              <div>
                <span class="text-surface-500 text-sm block mb-1">{{ t("Stock") }}</span>
                <span class="font-semibold">
                  <template v-if="product.variants.length > 1">
                    {{ t("variants stock", { stock: product.stock, counter: product.variants.length }) }}
                  </template>
                  <template v-else>
                    {{ t("variant stock", { stock: product.stock }) }}
                  </template>
                </span>
              </div>
              <div>
                <span class="text-surface-500 text-sm block mb-1">{{ t("Brand") }}</span>
                <span class="font-medium">{{ product.brand?.name ?? "—" }}</span>
              </div>
              <div>
                <span class="text-surface-500 text-sm block mb-1">{{ t("Category", 2) }}</span>
                <div v-if="product.categories.length" class="flex flex-wrap gap-1.5">
                  <Tag
                    v-for="category in product.categories"
                    :key="category.id"
                    :value="category.name"
                    severity="secondary"
                    rounded
                  />
                </div>
                <span v-else class="text-muted-color">&mdash;</span>
              </div>
            </div>
          </template>
        </Card>

        <!-- Details -->
        <Card :pt="{ root: { class: '' }, body: { class: 'bg-surface-50 dark:bg-surface-900' } }">
          <template #title>{{ t("Details") }}</template>
          <template #content>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-surface-500 text-sm block mb-1">{{ t("Created") }}</span>
                <span class="font-medium">{{ formatDate(product.created_at) }}</span>
              </div>
              <div v-if="product.deleted_at">
                <span class="text-surface-500 text-sm block mb-1">{{ t("Deleted") }}</span>
                <span class="font-medium">{{ formatDate(product.deleted_at) }}</span>
              </div>
            </div>
          </template>
        </Card>

        <!-- Description -->
        <Card v-if="product.description" :pt="{ root: { class: '' }, body: { class: 'bg-surface-50 dark:bg-surface-900' } }">
          <template #title>{{ t("Description") }}</template>
          <template #content>
            <p class="m-0 leading-relaxed whitespace-pre-line">{{ product.description }}</p>
          </template>
        </Card>

        <!-- Variants -->
        <Card v-if="product.variants.length > 1" :pt="{ root: { class: '' }, body: { class: 'bg-surface-50 dark:bg-surface-900' } }">
          <template #title>{{ t("Variants") }} ({{ product.variants.length }})</template>
          <template #content>
            <DataTable :value="product.variants" size="small" striped-rows>
              <Column :header="t('Variant')">
                <template #body="{ data: variant }">
                  <template v-if="variant.option_values.length">
                    <div class="flex flex-wrap gap-1">
                      <Tag
                        v-for="ov in variant.option_values"
                        :key="ov.option_name + ov.value"
                        :value="ov.value"
                        severity="primary"
                        rounded
                        class="text-xs"
                      />
                    </div>
                  </template>
                  <Tag v-else :value="t('Default')" severity="secondary" />
                </template>
              </Column>
              <Column field="status" :header="t('Status')" style="width: 90px">
                <template #body="{ data: variant }">
                  <Tag v-if="variant.status === 'active'" severity="success" :value="t('Active')" class="text-xs" />
                  <Tag v-else-if="variant.status === 'inactive'" severity="warn" :value="t('Inactive')" class="text-xs" />
                  <Tag v-else severity="danger" :value="t('Archived')" class="text-xs" />
                </template>
              </Column>
              <Column field="price" :header="t('Price')">
                <template #body="{ data: variant }">
                  {{ formatCurrency(String(variant.price)) }}
                </template>
              </Column>
              <Column field="stock" :header="t('Stock')" style="width: 70px" />
            </DataTable>
          </template>
        </Card>
      </div>
    </div>

    <template #footer>
      <Button :label="t('Close')" severity="secondary" @click="closeModal" />
      <Button :label="t('Edit')" @click="goToEdit" />
    </template>
  </Dialog>
</template>