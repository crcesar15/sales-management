<script setup lang="ts">
import { Button, Dialog, Select, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { reactive, ref, watch } from "vue";
import { route } from "ziggy-js";
import type { ProductOption } from "@app-types/product-types";

const props = defineProps<{
  productId: number;
  options: ProductOption[];
  visible: boolean;
}>();
const emit = defineEmits<{
  (e: "close"): void;
}>();
const toast = useToast();
const { t } = useI18n();

const selectedValues = reactive<Record<number, number | null>>({});
const submitting = ref(false);

const initForm = () => {
  props.options.forEach((o) => {
    selectedValues[o.id] = null;
  });
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

  if (optionValueIds.length !== props.options.length) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Select a value for each option"), life: 3000 });
    return;
  }

  submitting.value = true;

  router.post(
    route("variant.store", props.productId),
    {
      option_value_ids: optionValueIds,
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
    :header="t('Add Variant')"
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
          :input-id="`option-${option.id}`"
          v-model="selectedValues[option.id]"
          :options="option.values"
          option-label="value"
          option-value="id"
          :placeholder="t('Select {name}', { name: option.name })"
          fluid
        />
      </div>

    </div>

    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" outlined @click="$emit('close')" />
      <Button :label="t('Add Variant')" :loading="submitting" @click="onSubmit" />
    </template>
  </Dialog>
</template>
