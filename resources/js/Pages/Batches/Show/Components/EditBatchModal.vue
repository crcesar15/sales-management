<script setup lang="ts">
import { Dialog, Button, InputText, DatePicker, useToast } from "primevue";
import { useI18n } from "vue-i18n";
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";

const props = defineProps<{
  visible: boolean;
  batchId: number;
  batchIdentifier: string | null;
  expiryDate: string | null;
  hasExpiration?: boolean;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const { t } = useI18n();
const { parseDateOnly, toDateOnly, datePickerFormat } = useDatetimeFormatter();
const toast = useToast();
const loading = ref(false);
const fieldErrors = ref<Record<string, string>>({});

const batchIdentifier = ref(props.batchIdentifier ?? "");
const expiryDate = ref<Date | null>(parseDateOnly(props.expiryDate));

const isDirty = computed(() => {
  const identifierChanged = (batchIdentifier.value || null) !== props.batchIdentifier;
  const dateChanged = toDateOnly(expiryDate.value) !== (props.expiryDate ?? null);
  return identifierChanged || dateChanged;
});

watch(
  () => props.batchIdentifier,
  (val) => {
    batchIdentifier.value = val ?? "";
  },
);
watch(
  () => props.expiryDate,
  (val) => {
    expiryDate.value = parseDateOnly(val);
  },
);

function close() {
  emit("update:visible", false);
}

function handleDismiss(value: boolean) {
  if (!value && isDirty.value) {
    if (!window.confirm(t("You have unsaved changes. Are you sure you want to close?"))) {
      return;
    }
  }
  emit("update:visible", value);
  if (!value) {
    batchIdentifier.value = props.batchIdentifier ?? "";
    expiryDate.value = parseDateOnly(props.expiryDate);
  }
}

function submit() {
  if (props.hasExpiration && !expiryDate.value) {
    fieldErrors.value = { expiry_date: t("The expiry date is required for this product variant.") };
    toast.add({ severity: "error", summary: t("Error"), detail: t("The expiry date is required for this product variant."), life: 3000 });
    return;
  }

  loading.value = true;
  router.put(
    route("batches.update", { batch: props.batchId }),
    {
      batch_identifier: batchIdentifier.value || null,
      expiry_date: toDateOnly(expiryDate.value),
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        close();
        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("Batch updated successfully"),
          life: 3000,
        });
      },
      onError: (errs) => {
        fieldErrors.value = errs as Record<string, string>;
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t(Object.values(errs)[0] ?? "An error occurred"),
          life: 3000,
        });
      },
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
    :closable="false"
    :header="t('Edit Batch')"
    :style="{ width: '450px' }"
    @update:visible="handleDismiss($event)"
  >
    <div class="flex flex-col gap-4">
      <div>
        <label for="batch-identifier" class="text-sm font-medium mb-1 block">{{ t("Batch Identifier") }}</label>
        <InputText
          id="batch-identifier"
          v-model="batchIdentifier"
          :placeholder="t('Enter batch identifier (optional)')"
          class="w-full"
          :class="{ 'p-invalid': fieldErrors.batch_identifier }"
        />
        <small v-if="fieldErrors.batch_identifier" class="text-red-500">{{ fieldErrors.batch_identifier }}</small>
      </div>
      <div>
        <label for="expiry-date" class="text-sm font-medium mb-1 block">
          {{ t("Expiry Date") }}
          <span v-if="hasExpiration" class="text-red-400">*</span>
        </label>
        <DatePicker id="expiry-date" v-model="expiryDate" show-icon class="w-full" :class="{ 'p-invalid': fieldErrors.expiry_date }" :date-format="datePickerFormat" />
        <small v-if="fieldErrors.expiry_date" class="text-red-500">{{ fieldErrors.expiry_date }}</small>
        <small v-else class="text-surface-500">{{ t("Updating the expiry date will recalculate the expiry status") }}</small>
      </div>
    </div>
    <template #footer>
      <Button :label="t('Cancel')" severity="secondary" outlined @click="handleDismiss(false)" />
      <Button :label="t('Save')" :loading="loading" :disabled="!isDirty" @click="submit" />
    </template>
  </Dialog>
</template>
