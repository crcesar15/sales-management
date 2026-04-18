<script setup lang="ts">
import { Dialog, Galleria, Button, Tag, Badge, DataTable, Column, Divider } from "primevue";
import { computed } from "vue";
import type { ProductListResponse } from "@/Types/product-types";
import { useCurrencyFormatter } from "@/Composables/useCurrencyFormatter";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
  product: ProductListResponse | null;
}>();

const showDialog = defineModel<boolean>("showDialog", {
  default: false,
});

const { t } = useI18n();
const { formatCurrency } = useCurrencyFormatter();

const productMedia = computed(() => props.product?.media ?? []);

const galleriaResponsiveOptions = [
  { breakpoint: "850px", numVisible: 3 },
  { breakpoint: "500px", numVisible: 2 },
];

const formatDate = (dateStr?: string): string => {
  if (!dateStr) return "—";
  return new Date(dateStr).toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const goToEdit = () => {
  if (props.product) {
    router.visit(route("products.edit", { product: props.product.id }));
  }
};

const closeModal = () => {
  showDialog.value = false;
};
</script>

<template>
  <Dialog
    v-model:visible="showDialog"
    modal
    :header="product?.name"
    :style="{ width: '50vw' }"
    :breakpoints="{ '1100px': '75vw', '850px': '85vw', '500px': '95vw' }"
    @hide="closeModal"
  >
    <div class="grid grid-cols-12 gap-6">
      <!-- Image Gallery -->
      <div class="lg:col-span-4 col-span-12">
        <Galleria
          v-if="productMedia.length > 1"
          :value="productMedia"
          :num-visible="5"
          :show-item-navigators="true"
          :show-item-navigators-on-hover="true"
          :circular="true"
          container-style="max-width: 100%"
          :responsive-options="galleriaResponsiveOptions"
        >
          <template #item="slotProps">
            <img
              :src="slotProps.item.full_url"
              :alt="product?.name"
              class="w-full rounded-xl border border-surface-200 dark:border-surface-700"
              style="max-height: 350px; object-fit: contain"
            />
          </template>
          <template #thumbnail="slotProps">
            <img
              :src="slotProps.item.thumb_url"
              :alt="product?.name"
              class="rounded border border-surface-200 dark:border-surface-700"
              style="height: 50px; width: 50px; object-fit: cover"
            />
          </template>
        </Galleria>
        <div v-else-if="productMedia.length === 1">
          <img
            :src="productMedia[0].full_url"
            :alt="product?.name"
            class="w-full rounded-xl border border-surface-200 dark:border-surface-700"
            style="max-height: 350px; object-fit: contain"
          />
        </div>
        <div v-else>
          <div
            class="bg-surface-50 dark:bg-surface-900 flex items-center justify-center rounded-xl border border-surface-200 dark:border-surface-700"
            style="height: 300px"
          >
            <div class="text-center text-muted-color">
              <i class="fa fa-image text-4xl mb-2 block" />
              <span>{{ t("No Image") }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Product Info -->
      <div class="lg:col-span-8 col-span-12 flex flex-col gap-4">
        <!-- Status & Brand -->
        <div class="flex items-center gap-3 flex-wrap">
          <Tag v-if="product?.status === 'active'" severity="success" :value="t('Active')" />
          <Tag v-else-if="product?.status === 'inactive'" severity="warn" :value="t('Inactive')" />
          <Tag v-else severity="danger" :value="t('Archived')" />
          <span v-if="product?.brand" class="text-muted-color text-sm">
            <i class="fa fa-tag mr-1" />
            {{ product.brand.name }}
          </span>
        </div>

        <!-- Detail Fields Grid -->
        <div class="grid grid-cols-2 gap-4 bg-surface-50 dark:bg-surface-900 rounded-xl p-4">
          <div>
            <span class="text-muted-color text-sm block mb-1">{{ t("Price") }}</span>
            <span class="text-lg font-semibold">
              <span v-if="product && product.price_min !== product.price_max">
                {{ formatCurrency(String(product.price_min)) }} – {{ formatCurrency(String(product.price_max)) }}
              </span>
              <span v-else>
                {{ formatCurrency(String(product?.price_min ?? 0)) }}
              </span>
            </span>
          </div>
          <div>
            <span class="text-muted-color text-sm block mb-1">{{ t("Stock") }}</span>
            <div class="flex items-center gap-2">
              <span class="text-lg font-semibold">
                <span v-if="product && product.variants.length > 1">
                  {{ t("variants stock", { stock: product.stock, counter: product.variants.length }) }}
                </span>
                <span v-else>
                  {{ t("variant stock", { stock: product?.stock ?? 0 }) }}
                </span>
              </span>
              <Badge :value="product?.stock ?? 0" :severity="(product?.stock ?? 0) > 0 ? 'success' : 'danger'" />
            </div>
          </div>
          <div class="col-span-2">
            <span class="text-muted-color text-sm block mb-1">{{ t("Category", 2) }}</span>
            <div class="flex flex-wrap gap-2">
              <Tag v-for="category in product?.categories ?? []" :key="category.id" :value="category.name" severity="secondary" rounded />
              <span v-if="!product?.categories?.length" class="text-muted-color">—</span>
            </div>
          </div>
          <div class="col-span-2">
            <span class="text-muted-color text-sm block mb-1">{{ t("Created") }}</span>
            <span>{{ formatDate(product?.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Description -->
      <template v-if="product?.description">
        <div class="col-span-12">
          <Divider align="left">
            <span class="text-sm font-semibold">{{ t("Description") }}</span>
          </Divider>
          <p class="text-color m-0 leading-relaxed bg-surface-50 dark:bg-surface-900 rounded-xl p-4">
            {{ product.description }}
          </p>
        </div>
      </template>

      <!-- Variants Table -->
      <template v-if="product && product.variants.length > 1">
        <div class="col-span-12">
          <Divider align="left">
            <span class="text-sm font-semibold">{{ t("Variants") }} ({{ product.variants.length }})</span>
          </Divider>
          <DataTable :value="product.variants" size="small" show-gridlines class="bg-surface-50 dark:bg-surface-900 rounded-xl p-4">
            <Column field="identifier" :header="t('Identifier')" style="width: 140px">
              <template #body="{ data: variant }">
                <span class="text-muted-color">{{ variant.identifier ?? "—" }}</span>
              </template>
            </Column>
            <Column :header="t('Variant')">
              <template #body="{ data: variant }">
                <template v-if="variant.option_values.length">
                  <Tag
                    v-for="ov in variant.option_values"
                    :key="ov.option_name + ov.value"
                    :value="`${ov.option_name}: ${ov.value}`"
                    severity="info"
                    rounded
                    class="mr-1 mb-1"
                  />
                </template>
                <Tag v-else :value="t('Default')" severity="secondary" />
              </template>
            </Column>
            <Column field="status" :header="t('Status')" style="width: 100px">
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
            <Column field="stock" :header="t('Stock')" style="width: 80px" />
          </DataTable>
        </div>
      </template>
    </div>

    <template #footer>
      <Button :label="t('Close')" icon="fa fa-times" severity="secondary" outlined @click="closeModal" />
      <Button :label="t('Edit')" icon="fa fa-edit" @click="goToEdit" />
    </template>
  </Dialog>
</template>
