<script setup lang="ts">
import { Badge, Button, Card, Chip, Column, ConfirmDialog, DataTable, useConfirm, useToast } from "primevue";

import { useI18n } from "vue-i18n";
import { ref } from "vue";
import type { CreateVariant } from "@app-types/product-types";
import ManualVariantDialog from "./ManualVariantDialog.vue";
import VariantImageDialog from "./VariantImageDialog.vue";

const props = withDefaults(
  defineProps<{
    options: Array<{ name: string; values: string[] }>;
    variants: CreateVariant[];
    pendingMedia?: MediaItem[];
  }>(),
  {
    pendingMedia: () => [],
  },
);
const emit = defineEmits<{
  (e: "update:variants", value: CreateVariant[]): void;
}>();
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

interface MediaItem {
  id: number;
  thumb_url: string;
  full_url: string;
}

const showDialog = ref(false);
const editingVariant = ref<CreateVariant | null>(null);
const imageDialogVisible = ref(false);
const editingImageVariant = ref<CreateVariant | null>(null);
const selectedImageIds = ref<number[]>([]);

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("es-BO", { style: "currency", currency: "BOB" }).format(value);
};

const getMediaThumb = (mediaId: number) => {
  return props.pendingMedia.find((m) => m.id === mediaId)?.thumb_url ?? "";
};

const openCreateDialog = () => {
  editingVariant.value = null;
  showDialog.value = true;
};

const openEditDialog = (variant: CreateVariant) => {
  editingVariant.value = variant;
  showDialog.value = true;
};

const openImageDialog = (variant: CreateVariant) => {
  editingImageVariant.value = variant;
  selectedImageIds.value = [...(variant.pending_media_ids ?? [])];
  imageDialogVisible.value = true;
};

const onImageSave = (ids: number[]) => {
  if (!editingImageVariant.value) return;

  const updated = props.variants.map((v) => {
    if (v.key === editingImageVariant.value?.key) {
      return { ...v, pending_media_ids: ids };
    }
    return { ...v, pending_media_ids: v.pending_media_ids ?? [] };
  });
  emit("update:variants", updated);
  imageDialogVisible.value = false;
};

const onManualUpdate = (variant: CreateVariant) => {
  const updated = props.variants.map((v) => {
    if (v.key === editingVariant.value?.key) {
      return variant;
    }
    return { ...v, pending_media_ids: v.pending_media_ids ?? [] };
  });
  emit("update:variants", updated);
  showDialog.value = false;
};

const buildKey = (values: Record<string, string>): string => {
  return Object.entries(values)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([k, v]) => `${k}:${v}`)
    .join("/");
};

const cartesianProduct = (arrays: string[][]): string[][] => {
  let result: string[][] = [[]];

  for (const values of arrays) {
    const append: string[][] = [];
    for (const product of result) {
      for (const value of values) {
        append.push([...product, value]);
      }
    }
    result = append;
  }

  return result;
};

const onGenerateAll = () => {
  const totalCombinations = props.options.reduce((acc, o) => acc * o.values.length, 1);

  if (totalCombinations > 100) {
    toast.add({
      severity: "warn",
      summary: t("Warning"),
      detail: t("Too many combinations (:count). Add variants manually.", { count: totalCombinations }),
      life: 5000,
    });
    return;
  }

  const optionNames = props.options.map((o) => o.name);
  const valueArrays = props.options.map((o) => o.values);
  const combinations = cartesianProduct(valueArrays);

  const newVariants: CreateVariant[] = combinations.map((combo) => {
    const optionValues: Record<string, string> = {};
    optionNames.forEach((name, i) => {
      optionValues[name] = combo[i];
    });

    return {
      key: buildKey(optionValues),
      option_values: optionValues,
      price: 0,
      stock: 0,
      barcode: null,
      pending_media_ids: [],
    };
  });

  emit("update:variants", newVariants);
};

const onManualCreate = (variant: CreateVariant) => {
  emit("update:variants", [...props.variants, { ...variant, pending_media_ids: variant.pending_media_ids ?? [] }]);
  showDialog.value = false;
};

const onDeleteVariant = (data: CreateVariant) => {
  confirm.require({
    group: "variantDelete",
    message: t("This variant will be permanently deleted."),
    header: t("Delete Variant"),
    icon: "fa fa-triangle-exclamation",
    rejectProps: { label: t("Cancel"), severity: "secondary", outlined: true },
    acceptProps: { label: t("Delete"), severity: "danger" },
    accept: () => {
      emit(
        "update:variants",
        props.variants.filter((v) => v.key !== data.key).map((v) => ({ ...v, pending_media_ids: v.pending_media_ids ?? [] })),
      );
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
          <Button :label="t('Generate All Variants')" icon="fa fa-wand-magic-sparkles" size="small" outlined @click="onGenerateAll" />
          <Button :label="t('Add Variant')" icon="fa fa-plus" size="small" outlined @click="openCreateDialog" />
        </div>
      </div>
    </template>
    <template #content>
      <DataTable :value="props.variants" data-key="key">
        <!-- Options Column -->
        <Column :header="t('Options')">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <Chip v-for="(value, name) in data.option_values" :key="`${name}-${value}`" :label="`${name}: ${value}`" />
            </div>
          </template>
        </Column>

        <!-- Images Column -->
        <Column :header="t('Images')" class="w-32">
          <template #body="{ data }">
            <div class="flex items-center gap-1 cursor-pointer" @click="openImageDialog(data)">
              <img
                v-for="mediaId in (data.pending_media_ids ?? []).slice(0, 1)"
                :key="mediaId"
                :src="getMediaThumb(mediaId)"
                class="h-[75px] w-[75px] rounded-md border-2 border-surface-500 object-cover dark:border-surface-400"
              />
              <Badge v-if="(data.pending_media_ids ?? []).length > 1" class="text-xs text-gray-500">
                +{{ data.pending_media_ids.length - 1 }}
              </Badge>
              <div
                v-if="(data.pending_media_ids ?? []).length === 0"
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

        <!-- Stock Column -->
        <Column field="stock" :header="t('Stock')">
          <template #body="{ data }">
            <span>{{ data.stock }}</span>
          </template>
        </Column>

        <!-- Barcode Column -->
        <Column field="barcode" :header="t('Barcode')">
          <template #body="{ data }">
            <span>{{ data.barcode ?? "—" }}</span>
          </template>
        </Column>

        <!-- Actions Column -->
        <Column :header="t('Actions')" class="w-20">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="fa fa-pen" rounded variant="text" @click="openEditDialog(data)" v-tooltip.top="t('Edit')" />
              <Button icon="fa fa-trash" rounded variant="text" @click="onDeleteVariant(data)" v-tooltip.top="t('Delete')" />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center text-gray-500 py-4">
            {{ t("No variants yet. Generate all combinations or add them manually.") }}
          </div>
        </template>
      </DataTable>

      <!-- Manual Variant Dialog (create only) -->
      <ManualVariantDialog
        v-if="showDialog"
        :options="props.options"
        :existing-variants="props.variants"
        :visible="showDialog"
        :variant="editingVariant ?? undefined"
        @close="showDialog = false"
        @create="onManualCreate"
        @update="onManualUpdate"
      />

      <!-- Delete Confirmation -->
      <ConfirmDialog group="variantDelete" />

      <!-- Variant Image Dialog -->
      <VariantImageDialog
        :visible="imageDialogVisible"
        :pending-media="props.pendingMedia"
        :selected-ids="selectedImageIds"
        @close="imageDialogVisible = false"
        @save="onImageSave"
      />
    </template>
  </Card>
</template>
