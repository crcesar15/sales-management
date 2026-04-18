<script setup lang="ts">
import { Button, InputText, AutoComplete } from "primevue";

import { useI18n } from "vue-i18n";
import { computed, ref, watch } from "vue";

const props = withDefaults(
  defineProps<{
    modelValue?: LocalOption[];
    confirmed?: boolean;
  }>(),
  {
    modelValue: () => [],
    confirmed: false,
  },
);

const emit = defineEmits<{
  (e: "update:modelValue", value: LocalOption[]): void;
  (e: "update:confirmed", value: boolean): void;
  (e: "options-unlocked"): void;
}>();

const { t } = useI18n();

interface LocalOption {
  name: string;
  values: string[];
  saved: boolean;
}

const options = ref<LocalOption[]>([...props.modelValue]);

watch(
  options,
  (val) => {
    emit("update:modelValue", val);
  },
  { deep: true },
);

const canConfirm = computed(() => {
  return (
    options.value.length > 0 &&
    options.value.every((o) => o.name.length > 0 && o.values.length > 0) &&
    !options.value.some((o, i) => hasDuplicateName(i))
  );
});

const addOption = () => {
  options.value.push({ name: "", values: [], saved: false });
};

const deleteOption = (index: number) => {
  options.value.splice(index, 1);
};

const onValueChange = () => {
  emit("update:modelValue", options.value);
};

const hasDuplicateName = (index: number) => {
  const current = options.value[index]?.name;
  if (!current) return false;
  return options.value.some((o, i) => i !== index && o.name === current);
};

const confirmOptions = () => {
  if (canConfirm.value) {
    emit("update:confirmed", true);
  }
};

const unlockOptions = () => {
  emit("update:confirmed", false);
  emit("options-unlocked");
};
</script>

<template>
  <div>
    <!-- Empty state -->
    <div v-if="options.length === 0">
      <div class="flex">
        <Button icon="fa fa-plus" text :label="t('Add options like size or color')" @click="addOption" />
      </div>
    </div>

    <!-- Options list -->
    <div v-else>
      <div v-for="(option, index) in options" :key="`local-${index}`" class="grid grid-cols-12 mb-3 gap-3">
        <!-- Delete button -->
        <div class="col-span-12 flex flex-wrap flex-row justify-end" style="margin-bottom: -35px">
          <Button v-tooltip.left="t('Delete')" icon="fa fa-trash" variant="text" rounded v-if="!confirmed" @click="deleteOption(index)" />
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
            :disabled="confirmed"
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
              :disabled="option.name.length === 0 || confirmed"
              class="w-full block"
              @option-select="onValueChange"
              @option-unselect="onValueChange"
            />
          </div>
        </div>
      </div>

      <div v-if="!confirmed" class="flex">
        <Button icon="fa fa-plus" text :label="t('Add another option')" @click="addOption" />
      </div>
    </div>

    <!-- Confirm / Edit buttons -->
    <div v-if="options.length > 0" class="flex justify-end mt-3">
      <Button v-if="!confirmed" :label="t('Confirm Options')" icon="fa fa-lock" :disabled="!canConfirm" @click="confirmOptions" />
      <Button v-if="confirmed" :label="t('Edit Options')" icon="fa fa-pen" severity="secondary" outlined @click="unlockOptions" />
    </div>
  </div>
</template>
