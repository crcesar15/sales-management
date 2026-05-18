<script setup lang="ts">
import { Dialog, Button, Select, InputText, FileUpload, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import { ref, computed } from "vue";
import type { PaymentMethod } from "@/Types/purchase-order-types";

const props = defineProps<{
  visible: boolean;
  purchaseOrderId: number;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const { t } = useI18n();
const toast = useToast();

const paymentMethod = ref<PaymentMethod | null>(null);
const referenceNumber = ref("");
const proofFile = ref<File | null>(null);
const submitting = ref(false);

const paymentMethodOptions = computed(() => [
  { label: t("Bank Transfer"), value: "bank_transfer" },
  { label: t("Cash"), value: "cash" },
  { label: t("Check"), value: "check" },
  { label: t("Credit Card"), value: "credit_card" },
]);

function onFileSelect(event: { files: File[] }) {
  proofFile.value = event.files?.[0] ?? null;
}

function onFileClear() {
  proofFile.value = null;
}

function confirmAction() {
  if (!paymentMethod.value) return;

  submitting.value = true;

  const formData = new FormData();
  formData.append("_method", "PATCH");
  formData.append("proof_of_payment_type", paymentMethod.value);
  formData.append("proof_of_payment_number", referenceNumber.value || "");
  if (proofFile.value) {
    formData.append("proof_of_payment_file", proofFile.value);
  }

  router.post(route("purchase-orders.pay", { purchaseOrder: props.purchaseOrderId }), formData, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Purchase order marked as paid"), life: 3000 });
      emit("update:visible", false);
      resetForm();
    },
    onError: (errors) => {
      toast.add({ severity: "error", summary: t("Error"), detail: t("Could not mark purchase order as paid"), life: 3000 });
      if (errors.proof_of_payment_type) paymentMethod.value = null;
      if (errors.proof_of_payment_number) referenceNumber.value = "";
    },
    onFinish: () => {
      submitting.value = false;
    },
  });
}

function resetForm() {
  paymentMethod.value = null;
  referenceNumber.value = "";
  proofFile.value = null;
}

function onVisibleChange(val: boolean) {
  if (!val) resetForm();
  emit("update:visible", val);
}
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="t('Mark as Paid')"
    :style="{ width: '500px' }"
    @update:visible="onVisibleChange"
  >
    <div class="flex flex-col gap-4">
      <p class="m-0 text-surface-500">{{ t("Provide payment details to mark this purchase order as paid.") }}</p>

      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium">{{ t("Payment Method") }} *</label>
        <Select
          v-model="paymentMethod"
          :options="paymentMethodOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('Select payment method')"
          class="w-full"
        />
      </div>

      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium">{{ t("Reference Number") }}</label>
        <InputText v-model="referenceNumber" :placeholder="t('Enter transaction or reference number')" class="w-full" />
      </div>

      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium">{{ t("Proof of Payment") }}</label>
        <FileUpload
          mode="basic"
          accept=".pdf,.jpg,.jpeg,.png"
          :max-file-size="10485760"
          :auto="false"
          choose-label="Choose File"
          :placeholder="t('Upload receipt or proof (PDF, JPG, PNG)')"
          @select="onFileSelect"
          @clear="onFileClear"
        />
        <span class="text-xs text-surface-400">{{ t("Max 10MB. PDF, JPG, PNG accepted.") }}</span>
      </div>
    </div>

    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" @click="emit('update:visible', false)" />
      <Button :label="t('Confirm Payment')" :loading="submitting" :disabled="!paymentMethod" @click="confirmAction" />
    </template>
  </Dialog>
</template>