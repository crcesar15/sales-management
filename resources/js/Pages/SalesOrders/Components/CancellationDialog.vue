<script setup lang="ts">
import { Button, Dialog, Textarea } from "primevue";
import { shallowRef } from "vue";
import { useI18n } from "vue-i18n";

defineProps<{ visible: boolean; processing?: boolean }>();
const emit = defineEmits<{ "update:visible": [visible: boolean]; confirm: [reason: string] }>();
const { t } = useI18n();
const reason = shallowRef("");

function confirm(): void {
  if (reason.value.trim()) emit("confirm", reason.value.trim());
}
</script>

<template>
  <Dialog :visible="visible" modal :header="t('Cancel Sales Order')" class="w-full max-w-lg" @update:visible="emit('update:visible', $event)">
    <div class="flex flex-col gap-2"><label for="cancellation-reason">{{ t("Cancellation Reason") }}</label><Textarea id="cancellation-reason" v-model="reason" rows="4" :placeholder="t('Provide a reason')" /></div>
    <template #footer>
      <Button :label="t('Close')" severity="secondary" text @click="emit('update:visible', false)" />
      <Button :label="t('Cancel Order')" severity="danger" :disabled="!reason.trim()" :loading="processing" @click="confirm" />
    </template>
  </Dialog>
</template>
