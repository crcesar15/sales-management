<script setup lang="ts">
import { Dialog, Button, Textarea } from "primevue";
import { useI18n } from "vue-i18n";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
  visible: boolean;
  transferId: number;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const { t } = useI18n();
const reason = ref("");
const loading = ref(false);

function close() {
  emit("update:visible", false);
  reason.value = "";
}

function confirm() {
  loading.value = true;
  router.patch(
    route("stock-transfers.cancel", { stock_transfer: props.transferId }),
    { reason: reason.value },
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
    :header="t('Cancel Transfer')"
    :style="{ width: '450px' }"
    @update:visible="emit('update:visible', $event)"
  >
    <div class="flex flex-col gap-4">
      <p class="m-0 text-surface-600 dark:text-surface-400">
        {{ t("Are you sure you want to cancel this transfer?") }}
      </p>
      <div>
        <label class="text-sm font-medium mb-1 block">{{ t("Cancellation Reason") }}</label>
        <Textarea v-model="reason" :auto-resize="true" rows="3" class="w-full" />
      </div>
    </div>
    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" outlined @click="close" />
      <Button :label="t('Cancel Transfer')" severity="danger" :loading="loading" @click="confirm" />
    </template>
  </Dialog>
</template>
