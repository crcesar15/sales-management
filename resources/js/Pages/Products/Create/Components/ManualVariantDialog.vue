<script setup lang="ts">
import { Button, Dialog, InputNumber, InputText, Select, useToast } from "primevue";

import { useI18n } from "vue-i18n";
import { reactive, watch, computed } from "vue";
import type { CreateVariant } from "@app-types/product-types";

const props = defineProps<{
  options: Array<{ name: string; values: string[] }>;
  existingVariants: CreateVariant[];
  visible: boolean;
  variant?: CreateVariant;
}>();
const emit = defineEmits<{
  (e: "close"): void;
  (e: "create", variant: CreateVariant): void;
  (e: "update", variant: CreateVariant): void;
}>();
const toast = useToast();
const { t } = useI18n();

const isEditing = computed(() => !!props.variant);

const form = reactive({
  price: 0 as number,
  stock: 0 as number,
  barcode: "" as string | null,
});

const selectedValues = reactive<Record<string, string | null>>({});

const initForm = () => {
  props.options.forEach((o) => {
    selectedValues[o.name] = props.variant?.option_values[o.name] ?? null;
  });
  form.price = props.variant?.price ?? 0;
  form.stock = props.variant?.stock ?? 0;
  form.barcode = props.variant?.barcode ?? "";
};

watch(
  () => props.visible,
  (val) => {
    if (val) initForm();
  },
  { immediate: true },
);

const buildKey = (values: Record<string, string>): string => {
  return Object.entries(values)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([k, v]) => `${k}:${v}`)
    .join("/");
};

const onSubmit = () => {
  const optionValues: Record<string, string> = {};
  for (const option of props.options) {
    const val = selectedValues[option.name];
    if (!val) {
      toast.add({ severity: "warn", summary: t("Warning"), detail: t("Select a value for :name", { name: option.name }), life: 3000 });
      return;
    }
    optionValues[option.name] = val;
  }

  if (form.price === null || form.price === undefined) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Price is required"), life: 3000 });
    return;
  }

  const key = buildKey(optionValues);

  if (isEditing.value) {
    emit("update", {
      key,
      option_values: optionValues,
      price: form.price,
      stock: form.stock,
      barcode: form.barcode || null,
      pending_media_ids: props.variant?.pending_media_ids ?? [],
    });
    return;
  }

  if (props.existingVariants.some((v) => v.key === key)) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("This variant combination already exists"), life: 3000 });
    return;
  }

  emit("create", {
    key,
    option_values: optionValues,
    price: form.price,
    stock: form.stock,
    barcode: form.barcode || null,
    pending_media_ids: [],
  });
};
</script>

<template>
  <Dialog
    :visible="visible"
    :header="isEditing ? t('Edit Variant') : t('Add Variant')"
    modal
    :style="{ width: '450px' }"
    @update:visible="$emit('close')"
  >
    <div class="flex flex-col gap-4">
      <!-- One dropdown per option -->
      <div v-for="option in options" :key="option.name" class="flex flex-col gap-2">
        <label :for="`option-${option.name}`">
          {{ option.name }}
          <span class="text-red-400">*</span>
        </label>
        <Select
          :id="`option-${option.name}`"
          v-model="selectedValues[option.name]"
          :options="option.values"
          :placeholder="t('Select {name}', { name: option.name })"
          :disabled="isEditing"
          fluid
        />
      </div>

      <!-- Price -->
      <div class="flex flex-col gap-2">
        <label for="variant-price">
          {{ t("Price") }}
          <span class="text-red-400">*</span>
        </label>
        <InputNumber id="variant-price" v-model="form.price" mode="currency" currency="BOB" :min="0" fluid />
      </div>

      <!-- Stock -->
      <div class="flex flex-col gap-2">
        <label for="variant-stock">
          {{ t("Stock") }}
          <span class="text-red-400">*</span>
        </label>
        <InputNumber id="variant-stock" v-model="form.stock" :min="0" :use-grouping="false" fluid />
      </div>

      <!-- Barcode -->
      <div class="flex flex-col gap-2">
        <label for="variant-barcode">{{ t("Barcode") }}</label>
        <InputText id="variant-barcode" v-model="form.barcode" autocomplete="off" fluid />
      </div>
    </div>

    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" outlined @click="$emit('close')" />
      <Button :label="isEditing ? t('Save') : t('Add Variant')" @click="onSubmit" />
    </template>
  </Dialog>
</template>
