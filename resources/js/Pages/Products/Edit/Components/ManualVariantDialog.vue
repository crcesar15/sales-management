<script setup lang="ts">
import { Button, Dialog, InputNumber, InputText, Select, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { reactive, ref, computed, watch } from "vue";
import { route } from "ziggy-js";
import type { ProductOption, ProductVariantInline } from "@app-types/product-types";
import { useAuth } from "@/Composables/useAuth";

const props = defineProps<{
  productId: number;
  options: ProductOption[];
  visible: boolean;
  variant?: ProductVariantInline;
}>();
const emit = defineEmits<{
  (e: "close"): void;
}>();
const toast = useToast();
const { t } = useI18n();
const { getSetting } = useAuth();
const currency = getSetting("finance", "currency") ?? "USD";

const form = reactive({
  price: 0,
  stock: 0,
  identifier: "" as string | null,
  status: "active",
});

const selectedValues = reactive<Record<number, number | null>>({});
const submitting = ref(false);
const isEditing = computed(() => !!props.variant);

const initForm = () => {
  props.options.forEach((o) => {
    const match = props.variant?.values?.find((v) => v.option_name === o.name);
    selectedValues[o.id] = match?.id ?? null;
  });
  form.price = props.variant?.price ?? 0;
  form.stock = props.variant?.stock ?? 0;
  form.identifier = props.variant?.identifier ?? "";
  form.status = props.variant?.status ?? "active";
};

watch(
  () => props.visible,
  (val) => {
    if (val) initForm();
  },
  { immediate: true },
);

const onSubmit = () => {
  const optionValueIds = Object.values(selectedValues).filter((v): v is number => v !== null);

  if (!isEditing.value && optionValueIds.length === 0) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Select at least one option value"), life: 3000 });
    return;
  }

  if (form.price === null || form.price === undefined) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Price is required"), life: 3000 });
    return;
  }

  submitting.value = true;

  if (isEditing.value) {
    router.put(
      route("variant.update", { product: props.productId, variant: props.variant!.id }),
      {
        price: form.price,
        status: form.status,
        identifier: form.identifier || null,
      },
      {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Variant updated successfully"), life: 3000 });
          emit("close");
        },
        onError: (errs) => {
          toast.add({
            severity: "error",
            summary: t("Error"),
            detail: t(Object.values(errs)[0] ?? "An error occurred"),
            life: 3000,
          });
        },
        onFinish: () => {
          submitting.value = false;
        },
      },
    );
    return;
  }

  router.post(
    route("variant.store", props.productId),
    {
      option_value_ids: optionValueIds,
      price: form.price,
      stock: form.stock,
      identifier: form.identifier || null,
      status: form.status,
    },
    {
      onSuccess: () => {
        toast.add({ severity: "success", summary: t("Success"), detail: t("Variant created successfully"), life: 3000 });
        emit("close");
      },
      onError: (errs) => {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t(Object.values(errs)[0] ?? "An error occurred"),
          life: 3000,
        });
      },
      onFinish: () => {
        submitting.value = false;
      },
    },
  );
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
      <div v-for="option in options" :key="option.id" class="flex flex-col gap-2">
        <label :for="`option-${option.id}`">
          {{ option.name }}
          <span class="text-red-400">*</span>
        </label>
        <Select
          :id="`option-${option.id}`"
          v-model="selectedValues[option.id]"
          :options="option.values"
          option-label="value"
          option-value="id"
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
        <InputNumber id="variant-price" v-model="form.price" mode="currency" :currency="currency" :min="0" fluid />
      </div>

      <!-- Stock -->
      <div class="flex flex-col gap-2">
        <label for="variant-stock">
          {{ t("Stock") }}
          <span class="text-red-400">*</span>
        </label>
        <InputNumber id="variant-stock" v-model="form.stock" :min="0" :use-grouping="false" fluid />
      </div>

      <!-- Identifier -->
      <div class="flex flex-col gap-2">
        <label for="variant-identifier">{{ t("Identifier") }}</label>
        <InputText id="variant-identifier" v-model="form.identifier" autocomplete="off" fluid />
      </div>

      <!-- Status -->
      <div class="flex flex-col gap-2">
        <label for="variant-status">{{ t("Status") }}</label>
        <Select
          id="variant-status"
          v-model="form.status"
          :options="[
            { name: t('Active'), value: 'active' },
            { name: t('Inactive'), value: 'inactive' },
          ]"
          option-label="name"
          option-value="value"
          fluid
        />
      </div>
    </div>

    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" outlined @click="$emit('close')" />
      <Button :label="isEditing ? t('Save') : t('Add Variant')" :loading="submitting" @click="onSubmit" />
    </template>
  </Dialog>
</template>
