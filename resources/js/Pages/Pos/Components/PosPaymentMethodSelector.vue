<script setup lang="ts">
import { Button } from "primevue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import type { PosPaymentMode } from "@/Types/pos";

const paymentMode = defineModel<PosPaymentMode>({ required: true });
const { t } = useI18n();

const options = computed(() => [
  { value: "cash" as const, label: t("Cash"), icon: "fa fa-money-bill-wave" },
  { value: "qr" as const, label: t("QR"), icon: "fa fa-qrcode" },
  { value: "split" as const, label: t("Cash + QR"), icon: "fa fa-code-compare" },
]);
</script>

<template>
  <fieldset>
    <legend class="mb-3 font-semibold">{{ t("Payment method") }}</legend>
    <div class="grid grid-cols-3 gap-3">
      <Button
        v-for="option in options"
        :key="option.value"
        type="button"
        :label="option.label"
        :icon="option.icon"
        :outlined="paymentMode !== option.value"
        :severity="paymentMode === option.value ? 'primary' : 'secondary'"
        class="min-h-16 flex-col gap-2"
        :aria-pressed="paymentMode === option.value"
        @click="paymentMode = option.value"
      />
    </div>
  </fieldset>
</template>
