<script setup lang="ts">
import { Button, IconField, InputIcon, InputText, Popover, Select, ToggleSwitch } from "primevue";
import { shallowRef, useTemplateRef, watch } from "vue";
import { useI18n } from "vue-i18n";
import type { ProductGalleryFilterOptions, ProductGalleryFilters } from "@/Types/product-gallery-types";

const props = defineProps<{
  filters: ProductGalleryFilters;
  options: ProductGalleryFilterOptions;
}>();

const emit = defineEmits<{
  apply: [filters: ProductGalleryFilters];
}>();

const { t } = useI18n();
const filter = shallowRef(props.filters.filter);
const brandId = shallowRef<number | null>(props.filters.brand_id);
const categoryId = shallowRef<number | null>(props.filters.category_id);
const available = shallowRef(props.filters.available);
const mobileFilterPopover = useTemplateRef<{ toggle: (event: Event) => void }>("mobileFilterPopover");

watch(
  () => props.filters,
  (filters) => {
    filter.value = filters.filter;
    brandId.value = filters.brand_id;
    categoryId.value = filters.category_id;
    available.value = filters.available;
  },
);

function apply(): void {
  emit("apply", {
    filter: filter.value.trim(),
    brand_id: brandId.value,
    category_id: categoryId.value,
    available: available.value,
  });
}

function clear(): void {
  filter.value = "";
  brandId.value = null;
  categoryId.value = null;
  available.value = false;
  apply();
}

function toggleMobileFilters(event: Event): void {
  mobileFilterPopover.value?.toggle(event);
}
</script>

<template>
  <form
    id="product-gallery-filters"
    class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(15rem,2fr)_minmax(10rem,1fr)_minmax(10rem,1fr)_auto] xl:items-end"
    @reset.prevent="clear"
    @submit.prevent="apply"
  >
    <div class="flex items-end gap-2 sm:contents">
      <div class="min-w-0 flex-1">
        <label for="product-gallery-search" class="mb-1.5 block text-sm font-medium">{{ t("Search") }}</label>
        <IconField>
          <InputIcon class="fa fa-search" />
          <InputText
            id="product-gallery-search"
            v-model="filter"
            :placeholder="t('Search products, variants or barcode')"
            autocomplete="off"
            class="w-full"
          />
        </IconField>
      </div>

      <Button
        type="button"
        icon="fa fa-sliders"
        severity="secondary"
        outlined
        class="min-h-11 shrink-0 sm:!hidden"
        :aria-label="t('Filters')"
        aria-controls="product-gallery-mobile-filters"
        aria-haspopup="dialog"
        @click="toggleMobileFilters"
      />
    </div>

    <div class="hidden sm:block">
      <label for="product-gallery-brand" class="mb-1.5 block text-sm font-medium">{{ t("Brand") }}</label>
      <Select
        id="product-gallery-brand"
        v-model="brandId"
        :options="options.brands"
        option-label="name"
        option-value="id"
        :placeholder="t('All brands')"
        show-clear
        filter
        class="w-full"
      />
    </div>

    <div class="hidden sm:block">
      <label for="product-gallery-category" class="mb-1.5 block text-sm font-medium">{{ t("Category") }}</label>
      <Select
        id="product-gallery-category"
        v-model="categoryId"
        :options="options.categories"
        option-label="name"
        option-value="id"
        :placeholder="t('All categories')"
        show-clear
        filter
        class="w-full"
      />
    </div>

    <div class="hidden min-h-11 items-center gap-2 sm:flex">
      <ToggleSwitch id="product-gallery-available" v-model="available" />
      <label for="product-gallery-available" class="text-sm font-medium">{{ t("In stock only") }}</label>
    </div>
  </form>

  <Popover id="product-gallery-mobile-filters" ref="mobileFilterPopover" class="sm:hidden">
    <div class="w-[calc(100vw-2rem)] max-w-sm space-y-4 p-4">
      <div>
        <label for="product-gallery-brand-mobile" class="mb-1.5 block text-sm font-medium">{{ t("Brand") }}</label>
        <Select
          id="product-gallery-brand-mobile"
          v-model="brandId"
          :options="options.brands"
          option-label="name"
          option-value="id"
          :placeholder="t('All brands')"
          show-clear
          filter
          class="w-full"
        />
      </div>

      <div>
        <label for="product-gallery-category-mobile" class="mb-1.5 block text-sm font-medium">{{ t("Category") }}</label>
        <Select
          id="product-gallery-category-mobile"
          v-model="categoryId"
          :options="options.categories"
          option-label="name"
          option-value="id"
          :placeholder="t('All categories')"
          show-clear
          filter
          class="w-full"
        />
      </div>

      <div class="flex min-h-11 items-center gap-2">
        <ToggleSwitch id="product-gallery-available-mobile" v-model="available" />
        <label for="product-gallery-available-mobile" class="text-sm font-medium">{{ t("In stock only") }}</label>
      </div>
    </div>
  </Popover>
</template>
