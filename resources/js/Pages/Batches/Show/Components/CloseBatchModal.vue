<script setup lang="ts">
import { Dialog, Button, Textarea } from "primevue";
import { useI18n } from "vue-i18n";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
  visible: boolean;
  batchId: number;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const { t } = useI18n();
const notes = ref("");
const loading = ref(false);

function close() {
  emit("update:visible", false);
  notes.value = "";
}

function confirm() {
  loading.value = true;
  router.patch(
    route("batches.close", { batch: props.batchId }),
    { notes: notes.value },
    {
      preserveScroll: true,
      onSuccess: () => close(),
      onFinish: () => {
        loading.value = false;
      },
    },
  );
}
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="t('Close Batch')"
    :style="{ width: '450px' }"
    @update:visible="emit('update:visible', $event)"
  >
    <div class="flex flex-col gap-4">
      <p class="m-0 text-surface-600 dark:text-surface-400">
        {{ t("Are you sure you want to close this batch?") }}
      </p>
      <div>
        <label class="text-sm font-medium mb-1 block">{{ t("Notes (optional)") }}</label>
        <Textarea v-model="notes" :auto-resize="true" rows="3" class="w-full" />
      </div>
    </div>
    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" outlined @click="close" />
      <Button :label="t('Close Batch')" severity="danger" :loading="loading" @click="confirm" />
    </template>
  </Dialog>
</template>
