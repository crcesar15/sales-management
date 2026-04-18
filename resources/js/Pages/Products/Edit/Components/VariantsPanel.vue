<script setup lang="ts">
import { Badge, Button, Card, Chip, Column, ConfirmDialog, DataTable, Tag, useConfirm, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { computed, ref } from "vue";
import { route } from "ziggy-js";
import type { ProductMedia, ProductOption, ProductVariantInline } from "@app-types/product-types";
import ManualVariantDialog from "./ManualVariantDialog.vue";
import EditVariantImageDialog from "./EditVariantImageDialog.vue";

const props = withDefaults(
  defineProps<{
    productId: number;
    variants: ProductVariantInline[];
    options: ProductOption[];
    disabled?: boolean;
    productMedia?: ProductMedia[];
  }>(),
  {
    disabled: false,
    productMedia: () => [],
  },
);
const _toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

const generating = ref(false);
const showManualDialog = ref(false);
const imageDialogVisible = ref(false);
const editingVariant = ref<ProductVariantInline | null>(null);
const editingVariantData = ref<ProductVariantInline | null>(null);

// Can generate variants: only when there's exactly 1 variant with no option values
const canGenerateVariants = computed(() => {
  return props.options.length > 0 && props.variants.length === 1 && (!props.variants[0].values || props.variants[0].values.length === 0);
});

const isDefaultVariant = (data: ProductVariantInline) => {
  return !data.values || data.values.length === 0;
};

const rowClass = (data: ProductVariantInline) => {
  return isDefaultVariant(data) ? "bg-blue-50 dark:bg-blue-900/20" : "";
};

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("es-BO", { style: "currency", currency: "BOB" }).format(value);
};

const statusLabel = (status: string) => {
  const map: Record<string, string> = {
    active: t("Active"),
    inactive: t("Inactive"),
    archived: t("Archived"),
  };
  return map[status] ?? status;
};

const statusSeverity = (status: string) => {
  const map: Record<string, "success" | "warn" | "danger"> = {
    active: "success",
    inactive: "warn",
    archived: "danger",
  };
  return map[status] ?? "info";
};

// Open variant image dialog
const openImageDialog = (variant: ProductVariantInline) => {
  if (props.disabled) return;
  editingVariant.value = variant;
  imageDialogVisible.value = true;
};

// Generate variants from all options
const onGenerateVariants = () => {
  generating.value = true;
  const optionsData = props.options.map((o) => ({
    name: o.name,
    values: o.values.map((v) => v.value),
  }));

  router.post(
    route("variant.generate", props.productId),
    {
      options: optionsData,
    },
    {
      onFinish: () => {
        generating.value = false;
      },
    },
  );
};

// Open edit dialog for a variant
const openEditDialog = (variant: ProductVariantInline) => {
  editingVariantData.value = variant;
  showManualDialog.value = true;
};

// Delete variant with confirmation
const onDeleteVariant = (data: ProductVariantInline) => {
  confirm.require({
    group: "variantDelete",
    message: t("This variant will be permanently deleted."),
    header: t("Delete Variant"),
    icon: "fa fa-triangle-exclamation",
    rejectProps: { label: t("Cancel"), severity: "secondary", outlined: true },
    acceptProps: { label: t("Delete"), severity: "danger" },
    accept: () => {
      router.delete(route("variant.destroy", { product: props.productId, variant: data.id }));
    },
  });
};
</script>

<template>
  <Card>
    <template #title>
      <div class="flex items-center justify-between">
        <span>{{ t("Variants") }}</span>
        <div class="flex gap-2">
          <Button
            v-if="canGenerateVariants"
            :label="t('Generate Variants')"
            icon="fa fa-wand-magic-sparkles"
            size="small"
            outlined
            :loading="generating"
            :disabled="disabled"
            @click="onGenerateVariants"
          />
          <Button
            v-if="props.options.length > 0"
            :label="t('Add Variant')"
            icon="fa fa-plus"
            size="small"
            outlined
            :disabled="disabled"
            @click="
              editingVariantData = null;
              showManualDialog = true;
            "
          />
        </div>
      </div>
      <div v-if="disabled" class="text-orange-500 text-sm mt-2">
        <i class="fa fa-lock mr-1" />
        {{ t("Confirm options to manage variants") }}
      </div>
    </template>
    <template #content>
      <DataTable :value="props.variants" data-key="id" :row-class="rowClass">
        <!-- Option Values Column -->
        <Column :header="t('Options')">
          <template #body="{ data }">
            <div v-if="isDefaultVariant(data)" class="flex items-center gap-1">
              <Tag :value="t('Default')" />
            </div>
            <div v-else class="flex flex-wrap gap-1">
              <Chip v-for="val in data.values" :key="val.id" :label="`${val.option_name}: ${val.value}`" />
            </div>
          </template>
        </Column>

        <!-- Images Column -->
        <Column :header="t('Images')" class="w-32">
          <template #body="{ data }">
            <div class="flex items-center gap-1 cursor-pointer" @click="openImageDialog(data)">
              <img
                v-for="img in (data.images ?? []).slice(0, 1)"
                :key="img.id"
                :src="img.thumb_url"
                class="h-[75px] w-[75px] rounded-md border-2 border-surface-500 object-cover dark:border-surface-400"
              />
              <Badge v-if="(data.images ?? []).length > 1" class="text-xs text-gray-500">+{{ data.images.length - 1 }}</Badge>
              <div
                v-if="(data.images ?? []).length === 0"
                class="h-[75px] w-[75px] rounded-md border-dashed border-2 border-surface-400 dark:border-surface-500 flex items-center justify-center p-6"
              >
                <i class="fa fa-image text-surface-400 dark:text-surface-500" />
              </div>
            </div>
          </template>
        </Column>

        <!-- Price Column -->
        <Column field="price" :header="t('Price')">
          <template #body="{ data }">
            {{ formatCurrency(data.price) }}
          </template>
        </Column>

        <!-- Stock Column (read-only) -->
        <Column field="stock" :header="t('Stock')">
          <template #body="{ data }">
            <span>{{ data.stock }}</span>
          </template>
        </Column>

        <!-- Status Column -->
        <Column field="status" :header="t('Status')">
          <template #body="{ data }">
            <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
          </template>
        </Column>

        <!-- Actions Column -->
        <Column :header="t('Actions')" class="w-24">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="fa fa-pen" text rounded v-tooltip.top="t('Edit')" :disabled="disabled" @click="openEditDialog(data)" />
              <Button
                icon="fa fa-trash"
                text
                rounded
                v-tooltip.top="t('Delete')"
                :disabled="disabled || isDefaultVariant(data)"
                @click="onDeleteVariant(data)"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center text-gray-500 py-4">
            {{ t("No variants yet. Add options and generate variants.") }}
          </div>
        </template>
      </DataTable>

      <!-- Manual Variant Dialog -->
      <ManualVariantDialog
        v-if="showManualDialog"
        :product-id="props.productId"
        :options="props.options"
        :visible="showManualDialog"
        :variant="editingVariantData ?? undefined"
        @close="showManualDialog = false"
      />

      <!-- Variant Image Dialog -->
      <EditVariantImageDialog
        v-if="imageDialogVisible && editingVariant"
        :visible="imageDialogVisible"
        :product-id="props.productId"
        :variant="editingVariant"
        :product-media="props.productMedia"
        @close="imageDialogVisible = false"
      />

      <!-- Delete Confirmation -->
      <ConfirmDialog group="variantDelete" />
    </template>
  </Card>
</template>
