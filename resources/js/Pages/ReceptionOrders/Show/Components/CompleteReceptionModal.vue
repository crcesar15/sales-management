<script setup lang="ts">
import { Dialog, Button, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";

const props = defineProps<{
  visible: boolean;
  receptionOrderId: number;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const { t } = useI18n();
const toast = useToast();

function confirmComplete() {
  router.patch(
    route("reception-orders.complete", { receptionOrder: props.receptionOrderId }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.add({ severity: "success", summary: t("Success"), detail: t("Reception order completed successfully"), life: 3000 });
        emit("update:visible", false);
      },
      onError: () => {
        toast.add({ severity: "error", summary: t("Error"), detail: t("Could not complete reception order"), life: 3000 });
      },
    },
  );
}
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="t('Complete Reception')"
    :style="{ width: '450px' }"
    @update:visible="emit('update:visible', $event)"
  >
    <div class="flex flex-col gap-3">
      <p>{{ t("Are you sure you want to complete this reception order?") }}</p>
      <div class="p-3 bg-orange-50 dark:bg-orange-900/30 rounded-border border border-orange-200 dark:border-orange-800">
        <div class="flex items-start gap-2">
          <i class="fa fa-triangle-exclamation text-orange-500 mt-0.5"></i>
          <p class="text-sm text-orange-700 dark:text-orange-300">
            {{ t("Completing this reception order will update stock and create batches. This action cannot be undone.") }}
          </p>
        </div>
      </div>
    </div>
    <template #footer>
      <Button :label="t('No')" severity="secondary" @click="emit('update:visible', false)" />
      <Button :label="t('Yes, Complete')" severity="warning" @click="confirmComplete" />
    </template>
  </Dialog>
</template>
