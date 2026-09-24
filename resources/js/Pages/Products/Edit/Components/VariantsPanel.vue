<script setup lang="ts">
import { Button, Card, Column, ConfirmDialog, DataTable, Tag, useConfirm } from "primevue";

import { router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import type { ProductOption, ProductVariantInline } from "@app-types/product-types";
import ManualVariantDialog from "./ManualVariantDialog.vue";

const props = withDefaults(
  defineProps<{
    productId: number;
    variants: ProductVariantInline[];
    options: ProductOption[];
    disabled?: boolean;
  }>(),
  {
    disabled: false,
  },
);
const confirm = useConfirm();
const { t } = useI18n();

const generating = ref(false);
const showCreateDialog = ref(false);

const canGenerateVariants = computed(() => {
  return props.options.length > 0 && props.variants.length === 1 && (!props.variants[0].values || props.variants[0].values.length === 0);
});

const isDefaultVariant = (variant: ProductVariantInline) => !variant.values || variant.values.length === 0;

const statusLabel = (status: string) => {
  const labels: Record<string, string> = {
    active: t("Active"),
    inactive: t("Inactive"),
    archived: t("Archived"),
  };

  return labels[status] ?? status;
};

const statusSeverity = (status: string): "success" | "warn" | "danger" | "info" => {
  const severities: Record<string, "success" | "warn" | "danger"> = {
    active: "success",
    inactive: "warn",
    archived: "danger",
  };

  return severities[status] ?? "info";
};

const onGenerateVariants = () => {
  generating.value = true;
  router.post(
    route("variant.generate", props.productId),
    {
      options: props.options.map((option) => ({
        name: option.name,
        values: option.values.map((value) => value.value),
      })),
    },
    {
      onFinish: () => {
        generating.value = false;
      },
    },
  );
};

const onDeleteVariant = (variant: ProductVariantInline) => {
  confirm.require({
    group: "variantDelete",
    message: t("This variant will be permanently deleted."),
    header: t("Delete Variant"),
    icon: "fa fa-triangle-exclamation",
    rejectProps: { label: t("Cancel"), severity: "secondary", outlined: true },
    acceptProps: { label: t("Delete"), severity: "danger" },
    accept: () => {
      router.delete(route("variant.destroy", { product: props.productId, variant: variant.id }));
    },
  });
};
</script>

<template>
  <Card>
    <template #title>
      <div class="flex flex-wrap items-center justify-between gap-2">
        <span>{{ t("Variants") }}</span>
        <div class="flex flex-wrap gap-2">
          <Button
            v-if="canGenerateVariants"
            v-can="'product.edit'"
            :label="t('Generate Variants')"
            icon="fa fa-wand-magic-sparkles"
            size="small"
            outlined
            :loading="generating"
            :disabled="disabled"
            @click="onGenerateVariants"
          />
          <Button
            v-if="options.length > 0"
            v-can="'product.edit'"
            :label="t('Add Variant')"
            icon="fa fa-plus"
            size="small"
            :disabled="disabled"
            @click="showCreateDialog = true"
          />
        </div>
      </div>
      <p v-if="disabled" class="mb-0 mt-2 text-sm text-orange-600 dark:text-orange-300">
        <i class="fa fa-lock mr-1" aria-hidden="true" />
        {{ t("Confirm options to manage variants") }}
      </p>
    </template>
    <template #content>
      <DataTable :value="variants" data-key="id">
        <Column :header="t('Options')">
          <template #body="{ data }: { data: ProductVariantInline }">
            <Tag v-if="isDefaultVariant(data)" :value="t('Default')" severity="secondary" />
            <div v-else class="flex flex-wrap gap-1">
              <Tag v-for="value in data.values" :key="value.id" :value="`${value.option_name}: ${value.value}`" severity="secondary" />
            </div>
          </template>
        </Column>
        <Column :header="t('Price')">
          <template #body="{ data }: { data: ProductVariantInline }">{{ data.price }}</template>
        </Column>
        <Column :header="t('Stock')">
          <template #body="{ data }: { data: ProductVariantInline }">{{ data.stock }}</template>
        </Column>
        <Column :header="t('Status')">
          <template #body="{ data }: { data: ProductVariantInline }">
            <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
          </template>
        </Column>
        <Column :header="t('Actions')" class="w-40">
          <template #body="{ data }: { data: ProductVariantInline }">
            <div class="flex items-center gap-1">
              <Button
                v-can="'inventory.view'"
                :label="t('Configure')"
                icon="fa fa-sliders"
                size="small"
                outlined
                @click="router.visit(route('inventory.variants.show', { variant: data.id }))"
              />
              <Button
                v-if="!isDefaultVariant(data)"
                v-can="'product.delete'"
                icon="fa fa-trash"
                text
                rounded
                severity="danger"
                :aria-label="t('Delete Variant')"
                @click="onDeleteVariant(data)"
              />
            </div>
          </template>
        </Column>
        <template #empty>
          <div class="py-4 text-center text-surface-500 dark:text-surface-400">{{ t("No variants yet. Add options and generate variants.") }}</div>
        </template>
      </DataTable>

      <ManualVariantDialog
        v-if="showCreateDialog"
        :product-id="productId"
        :options="options"
        :visible="showCreateDialog"
        @close="showCreateDialog = false"
      />
      <ConfirmDialog group="variantDelete" />
    </template>
  </Card>
</template>
