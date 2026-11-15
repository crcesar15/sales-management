<script setup lang="ts">
import { Button, InputNumber, InputText, Message, Select, ToggleSwitch, useToast } from "primevue";

import { computed, nextTick, ref, useTemplateRef, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { toTypedSchema } from "@vee-validate/yup";
import { useForm } from "vee-validate";
import { boolean, number, object, string } from "yup";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import type { InventoryVariantDetail, VariantOverviewPayload, VariantOverviewUpdatePayload } from "@app-types/inventory-variant-types";

const props = defineProps<{
  variant: InventoryVariantDetail;
  canEdit: boolean;
  siblingCount: number;
}>();
const emit = defineEmits<{
  (e: "update:dirty", value: boolean): void;
}>();
const { t } = useI18n();
const toast = useToast();
const formElement = useTemplateRef<HTMLFormElement>("overviewForm");
const replicateInventoryPolicy = ref(false);
const isSaving = ref(false);

const schema = toTypedSchema(
  object({
    identifier: string().nullable().optional().max(50),
    barcode: string().nullable().optional().max(100),
    minimum_stock_level: number().nullable().optional().min(0),
    has_expiration: boolean().required(),
    status: string().required().oneOf(["active", "inactive", "archived"]),
  }),
);

const initialValues = (): VariantOverviewPayload => ({
  identifier: props.variant.identifier,
  barcode: props.variant.barcode,
  minimum_stock_level: props.variant.minimum_stock_level,
  has_expiration: props.variant.has_expiration,
  status: props.variant.status,
});

const { handleSubmit, errors, defineField, setErrors, resetForm, meta, submitCount, isSubmitting } = useForm<VariantOverviewPayload>({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: initialValues(),
});

const [identifier, identifierAttrs] = defineField("identifier");
const [barcode, barcodeAttrs] = defineField("barcode");
const [minimumStockLevel, minimumStockLevelAttrs] = defineField("minimum_stock_level");
const [hasExpiration, hasExpirationAttrs] = defineField("has_expiration");
const [status, statusAttrs] = defineField("status");

const statusOptions = [
  { name: t("Active"), value: "active" },
  { name: t("Inactive"), value: "inactive" },
  { name: t("Archived"), value: "archived" },
];

const isDirty = computed(() => meta.value.dirty || replicateInventoryPolicy.value);

const clearReplication = () => {
  replicateInventoryPolicy.value = false;
};

watch(isDirty, (value) => emit("update:dirty", value), { immediate: true });

watch(
  () => props.variant,
  (variant, previousVariant) => {
    if (variant.id === previousVariant?.id && isDirty.value) return;

    resetForm({ values: initialValues() });
    clearReplication();
  },
);

const onSubmit = handleSubmit((values) => {
  if (isSaving.value) return;

  isSaving.value = true;
  const payload: VariantOverviewUpdatePayload = {
    ...values,
    section: "overview",
    replicate_inventory_policy: replicateInventoryPolicy.value,
  };

  router.put(
    route("inventory.variant.update", { variant: props.variant.id }),
    { ...payload },
    {
      preserveScroll: true,
      onSuccess: () => {
        resetForm({ values: initialValues() });
        const replicated = replicateInventoryPolicy.value;
        clearReplication();
        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: replicated ? t("Variant configuration applied to all variants") : t("Variant updated successfully"),
          life: 3000,
        });
      },
      onError: (serverErrors) => {
        setErrors(serverErrors);
        toast.add({ severity: "error", summary: t("Error"), detail: t("Please review the errors in the form"), life: 3000 });
        nextTick(() => formElement.value?.querySelector<HTMLInputElement>(".p-invalid")?.focus());
      },
      onFinish: () => {
        isSaving.value = false;
      },
    },
  );
});
</script>

<template>
  <form ref="overviewForm" class="flex flex-col gap-8" @submit.prevent="onSubmit">
    <section class="flex flex-col gap-4" aria-labelledby="variant-identification-heading">
      <div>
        <h2 id="variant-identification-heading" class="m-0 text-lg font-semibold">{{ t("Identification") }}</h2>
      </div>
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-6 flex flex-col gap-2">
          <label for="identifier">{{ t("Identifier") }}</label>
          <InputText
            id="identifier"
            v-model="identifier"
            v-bind="identifierAttrs"
            autocomplete="off"
            :disabled="!canEdit || isSaving"
            :invalid="submitCount > 0 && !!errors.identifier"
          />
          <small v-if="submitCount > 0 && errors.identifier" class="text-red-500 dark:text-red-300">{{ errors.identifier }}</small>
        </div>
        <div class="col-span-12 md:col-span-6 flex flex-col gap-2">
          <label for="barcode">{{ t("Barcode") }}</label>
          <InputText
            id="barcode"
            v-model="barcode"
            v-bind="barcodeAttrs"
            autocomplete="off"
            :disabled="!canEdit || isSaving"
            :invalid="submitCount > 0 && !!errors.barcode"
          />
          <small v-if="submitCount > 0 && errors.barcode" class="text-red-500 dark:text-red-300">{{ errors.barcode }}</small>
        </div>
      </div>
    </section>
    <hr class="m-0" />
    <section class="flex flex-col gap-4" aria-labelledby="variant-policy-heading">
      <div>
        <h2 id="variant-policy-heading" class="m-0 text-lg font-semibold">{{ t("Inventory Policy") }}</h2>
      </div>
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-4 flex flex-col gap-2">
          <label for="variant-status">{{ t("Status") }}</label>
          <Select
            input-id="variant-status"
            v-model="status"
            v-bind="statusAttrs"
            :options="statusOptions"
            option-label="name"
            option-value="value"
            :disabled="!canEdit || isSaving"
            :invalid="submitCount > 0 && !!errors.status"
          />
          <small v-if="submitCount > 0 && errors.status" class="text-red-500 dark:text-red-300">{{ errors.status }}</small>
        </div>
        <div class="col-span-12 md:col-span-4 flex flex-col gap-2">
          <label for="minimum-stock-level">{{ t("Minimum Stock Level") }}</label>
          <InputNumber
            input-id="minimum-stock-level"
            v-model="minimumStockLevel"
            v-bind="minimumStockLevelAttrs"
            :min="0"
            :disabled="!canEdit || isSaving"
            :invalid="submitCount > 0 && !!errors.minimum_stock_level"
            placeholder="—"
            autocomplete="off"
          />
          <small v-if="submitCount > 0 && errors.minimum_stock_level" class="text-red-500 dark:text-red-300">
            {{ errors.minimum_stock_level }}
          </small>
        </div>
        <div class="col-span-12 md:col-span-4 flex flex-col gap-2">
          <div class="flex items-center gap-2">
            <label for="has-expiration">{{ t("Requires Expiration Date") }}</label>
            <i
              v-tooltip.top="t('When enabled, expiry date will be required when receiving or editing batches')"
              class="fa fa-circle-question cursor-help text-sm text-surface-500 dark:text-surface-400"
              :aria-label="t('When enabled, expiry date will be required when receiving or editing batches')"
              role="img"
              tabindex="0"
            />
          </div>
          <div class="flex min-h-10 items-center gap-3">
            <ToggleSwitch input-id="has-expiration" v-model="hasExpiration" v-bind="hasExpirationAttrs" :disabled="!canEdit || isSaving" />
            <span class="text-sm text-surface-600 dark:text-surface-300">{{ hasExpiration ? t("Yes") : t("No") }}</span>
          </div>
        </div>
      </div>
      <div v-if="siblingCount > 0 && canEdit" class="flex flex-wrap items-center gap-3 pt-4">
        <div class="flex items-center gap-3">
          <ToggleSwitch input-id="replicate-inventory-policy" v-model="replicateInventoryPolicy" :disabled="isSaving" />
          <label for="replicate-inventory-policy" class="font-medium">{{ t("Apply to all variants") }}</label>
        </div>
        <Message v-if="replicateInventoryPolicy" severity="warn" icon="fa fa-triangle-exclamation" :closable="false" class="m-0 flex-1">
          {{ t("Copies stock policy to {count} variants. Statuses stay unchanged.", { count: siblingCount }) }}
        </Message>
      </div>
    </section>
    <div v-if="canEdit" class="flex justify-end border-t border-surface-200 pt-4 dark:border-surface-700">
      <Button
        type="submit"
        :label="t('Save changes')"
        icon="fa fa-save"
        raised
        class="uppercase"
        :loading="isSaving || isSubmitting"
        :disabled="!isDirty || isSaving"
      />
    </div>
  </form>
</template>
