<script setup lang="ts">
import { Button, InputText, AutoComplete, useToast } from "primevue";

import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { computed, ref, watch } from "vue";
import { route } from "ziggy-js";
import type { ProductOption } from "@app-types/product-types";

const props = withDefaults(
  defineProps<{
    productId: number;
    modelValue?: LocalOption[];
    serverOptions?: ProductOption[];
    locked?: boolean;
  }>(),
  {
    modelValue: () => [],
    serverOptions: () => [],
    locked: true,
  },
);
const emit = defineEmits<{
  (e: "update:modelValue", value: LocalOption[]): void;
  (e: "option-deleted", option: LocalOption): void;
  (e: "update:locked", value: boolean): void;
  (e: "options-unlocked"): void;
}>();
const toast = useToast();
const { t } = useI18n();

interface LocalOption {
  name: string;
  values: string[];
  saved: boolean;
  id?: number;
}

const options = ref<LocalOption[]>([...props.modelValue]);
const deletingIndex = ref<number | null>(null);
const savingIndex = ref<number | null>(null);

// Single emission point: sync local options to parent on any change
watch(
  options,
  (val) => {
    emit("update:modelValue", val);
  },
  { deep: true },
);

// Display: merge server options + unsaved local options
const displayOptions = computed(() => {
  const server = props.serverOptions.map((o) => ({
    id: o.id,
    name: o.name,
    values: o.values.map((v) => v.value),
    saved: true,
  }));
  // Append unsaved local options (no id, not yet persisted)
  const unsaved = options.value.filter((o) => !o.saved);
  return [...server, ...unsaved];
});

const canLock = computed(() => {
  return (
    displayOptions.value.length > 0 &&
    displayOptions.value.every((o) => o.name.length > 0 && o.values.length > 0) &&
    !displayOptions.value.some((o, i) => hasDuplicateName(i))
  );
});

const addOption = () => {
  options.value.push({ name: "", values: [], saved: false });
};

const isSavedServerOption = (option: LocalOption) => {
  return option.saved && !!option.id;
};

const isUnsavedOption = (option: LocalOption) => {
  return !option.saved || !option.id;
};

const hasDuplicateName = (index: number) => {
  const current = displayOptions.value[index]?.name;
  if (!current) return false;
  return displayOptions.value.some((o, i) => i !== index && o.name === current);
};

const unlockOptions = () => {
  emit("update:locked", false);
  emit("options-unlocked");
};

const lockOptions = () => {
  if (canLock.value) {
    emit("update:locked", true);
  }
};

// Save a new option to the server
const saveOption = (index: number) => {
  const option = displayOptions.value[index];
  if (!option.name || option.values.length === 0) return;

  savingIndex.value = index;
  router.post(
    route("option.store", props.productId),
    {
      name: option.name,
      values: option.values,
    },
    {
      onSuccess: () => {
        // Remove from local unsaved list after server persistence
        const localIdx = options.value.findIndex((o) => !o.saved && o.name === option.name);
        if (localIdx !== -1) options.value.splice(localIdx, 1);
        toast.add({ severity: "success", summary: t("Success"), detail: t("Option saved"), life: 3000 });
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
        savingIndex.value = null;
      },
    },
  );
};

// Delete option
const deleteOption = (index: number) => {
  const option = displayOptions.value[index];

  if (option.id) {
    deletingIndex.value = index;
    router.delete(route("option.destroy", { product: props.productId, option: option.id }), {
      onFinish: () => {
        deletingIndex.value = null;
      },
    });
  } else {
    emit("option-deleted", option);
    const localIdx = options.value.indexOf(option);
    if (localIdx !== -1) options.value.splice(localIdx, 1);
  }
};
</script>

<template>
  <div>
    <!-- Empty state -->
    <div v-if="displayOptions.length === 0">
      <div class="flex">
        <Button icon="fa fa-plus" text :label="t('Add options like size or color')" @click="addOption" />
      </div>
    </div>

    <!-- Options list -->
    <div v-else>
      <div v-for="(option, index) in displayOptions" :key="option.id ?? `local-${index}`" class="grid grid-cols-12 mb-3 gap-3">
        <!-- Save / Delete buttons -->
        <div class="col-span-12 flex flex-wrap flex-row justify-end" style="margin-bottom: -35px">
          <Button
            v-if="isUnsavedOption(option) && !locked"
            v-tooltip.left="t('Save')"
            icon="fa fa-floppy-disk"
            variant="text"
            rounded
            :disabled="option.name.length === 0 || option.values.length === 0"
            :loading="savingIndex === index"
            @click="saveOption(index)"
          />
          <Button
            v-tooltip.left="t('Delete')"
            icon="fa fa-trash"
            variant="text"
            rounded
            :disabled="locked"
            :loading="deletingIndex === index"
            @click="deleteOption(index)"
          />
        </div>

        <!-- Option Name -->
        <div class="md:col-span-6 col-span-12 flex flex-col gap-3">
          <label :for="`option-${index}`">{{ t("Option Name") }}</label>
          <InputText
            :id="`option-${index}`"
            v-model="option.name"
            class="w-full"
            :invalid="hasDuplicateName(index)"
            autocomplete="off"
            :disabled="locked || isSavedServerOption(option)"
          />
        </div>

        <!-- Option Values -->
        <div class="md:col-span-6 col-span-12 flex flex-col gap-3">
          <label :for="`values-${index}`">{{ t("Option Values") }}</label>
          <div class="flex flex-row">
            <AutoComplete
              v-model="option.values"
              multiple
              :typeahead="false"
              :input-id="`values-${index}`"
              :disabled="locked || option.name.length === 0"
              class="w-full block"
              @option-select="() => {}"
              @option-unselect="() => {}"
            />
          </div>
        </div>
      </div>

      <div v-if="!locked" class="flex">
        <Button icon="fa fa-plus" text :label="t('Add another option')" @click="addOption" />
      </div>
    </div>

    <!-- Lock / Unlock buttons -->
    <div v-if="displayOptions.length > 0" class="flex justify-end mt-3">
      <Button v-if="!locked" :label="t('Confirm Options')" icon="fa fa-lock" :disabled="!canLock" @click="lockOptions" />
      <Button v-if="locked" :label="t('Edit Options')" icon="fa fa-pen" severity="secondary" outlined @click="unlockOptions" />
    </div>
  </div>
</template>
