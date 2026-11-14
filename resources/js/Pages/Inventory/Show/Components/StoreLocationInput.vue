<script setup lang="ts">
import { AutoComplete, Button } from "primevue";
import { onScopeDispose, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useVariantLocationClient } from "@composables/useVariantLocationClient";

const props = defineProps<{ storeId: number; disabled: boolean; error?: string }>();
const model = defineModel<string>({ required: true });
const { t } = useI18n();
const { searchLocationCodes } = useVariantLocationClient();
const suggestions = ref<string[]>([]);
const loading = ref(false);
const failed = ref(false);
let controller: AbortController | null = null;
let lastQuery = "";

async function search(query: string): Promise<void> {
  controller?.abort();
  const current = new AbortController();
  controller = current;
  lastQuery = query;
  loading.value = true;
  failed.value = false;
  try {
    const result = await searchLocationCodes(props.storeId, query.trim().toUpperCase(), current.signal);
    if (controller === current) suggestions.value = result;
  } catch {
    if (!current.signal.aborted) failed.value = true;
  } finally {
    if (controller === current) loading.value = false;
  }
}

onScopeDispose(() => controller?.abort());
</script>

<template>
  <div class="flex flex-col gap-1">
    <AutoComplete
      :input-id="`location-${storeId}`"
      v-model="model"
      :suggestions="suggestions"
      :disabled="disabled"
      :invalid="!!error"
      :loading="loading"
      :delay="300"
      :min-length="0"
      dropdown
      dropdown-mode="blank"
      fluid
      @complete="search($event.query)"
      @blur="model = model.trim().toUpperCase()"
    />
    <small v-if="error" class="text-red-500 dark:text-red-300">{{ error }}</small>
    <div v-if="failed" class="flex items-center gap-2 text-sm" role="alert">
      <span>{{ t("Unable to load location suggestions") }}</span>
      <Button type="button" :label="t('Retry')" text size="small" @click="search(lastQuery)" />
    </div>
  </div>
</template>
