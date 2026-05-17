<script setup lang="ts">
import { Dialog, Button, Textarea, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import { ref } from "vue";

const props = defineProps<{
  visible: boolean;
  purchaseOrderId: number;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const { t } = useI18n();
const toast = useToast();
const reason = ref("");

function confirmCancel() {
  router.patch(
    route("purchase-orders.cancel", { purchaseOrder: props.purchaseOrderId }),
    {
      reason: reason.value || null,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.add({ severity: "success", summary: t("Success"), detail: t("Purchase order cancelled"), life: 3000 });
        emit("update:visible", false);
        reason.value = "";
      },
      onError: () => {
        toast.add({ severity: "error", summary: t("Error"), detail: t("Could not cancel purchase order"), life: 3000 });
      },
    },
  );
}
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="t('Cancel Purchase Order')"
    :style="{ width: '450px' }"
    @update:visible="emit('update:visible', $event)"
  >
    <p>{{ t("Are you sure you want to cancel this purchase order?") }}</p>
    <div class="flex flex-col gap-2 mt-3">
      <label for="cancel-reason">{{ t("Cancellation Reason") }}</label>
      <Textarea id="cancel-reason" v-model="reason" rows="3" :placeholder="t('Optional reason for cancellation')" />
    </div>
    <template #footer>
      <Button :label="t('No')" severity="secondary" @click="emit('update:visible', false)" />
      <Button :label="t('Yes, Cancel')" @click="confirmCancel" />
    </template>
  </Dialog>
</template>
