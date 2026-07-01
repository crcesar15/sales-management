<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { Button, Card, Select, Textarea, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/yup";
import { object, number, string } from "yup";
import { route } from "ziggy-js";
import { ref, computed, nextTick } from "vue";
import AppLayout from "@layouts/admin.vue";
import TransferLineItemsTable from "./Components/TransferLineItemsTable.vue";
import type { TransferLineItem } from "./Components/TransferLineItemsTable.vue";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  stores: Array<{ id: number; name: string; code: string }>;
}>();

const toast = useToast();
const { t } = useI18n();

const storeOptions = computed(() => props.stores.map((s) => ({ name: s.name, value: s.id })));

const schema = toTypedSchema(
  object({
    from_store_id: number().required().typeError(t("Source store is required")),
    to_store_id: number()
      .required()
      .typeError(t("Destination store is required"))
      .test("different-stores", t("Source store and destination store must be different"), (value, ctx) => {
        return value !== ctx.parent.from_store_id;
      }),
    notes: string().nullable().optional().max(1000),
  }),
);

const { handleSubmit, errors, defineField, setFieldValue, setErrors, values, submitCount, isSubmitting } = useForm({
  validationSchema: schema,
  validateOnMount: false,
  initialValues: {
    from_store_id: undefined as unknown as number,
    to_store_id: undefined as unknown as number,
    notes: "",
  },
});

const [notes, notesAttrs] = defineField("notes");

const lineItems = ref<TransferLineItem[]>([]);
const itemsError = ref("");

const selectedFromStoreName = computed(() => props.stores.find((s) => s.id === values.from_store_id)?.name ?? null);
const selectedToStoreName = computed(() => props.stores.find((s) => s.id === values.to_store_id)?.name ?? null);
const totalItems = computed(() => lineItems.value.length);
const totalQuantityRequested = computed(() => lineItems.value.reduce((sum, i) => sum + i.quantity_requested, 0));

const submit = handleSubmit((formValues) => {
  itemsError.value = "";
  if (lineItems.value.length === 0) {
    itemsError.value = t("At least one item is required");
    return;
  }

  const payload = {
    ...formValues,
    items: lineItems.value.map((item) => ({
      product_variant_id: item.product_variant_id,
      quantity_requested: item.quantity_requested,
    })),
  };

  router.post(route("stock-transfers.store"), payload, {
    onSuccess: () => {
      toast.add({ severity: "success", summary: t("Success"), detail: t("Transfer created successfully"), life: 3000 });
    },
    onError: (errs) => {
      setErrors(errs);
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: t("Please review the errors in the form"),
        life: 3000,
      });
      nextTick(() => {
        document.querySelector<HTMLInputElement>(".p-invalid")?.focus();
      });
    },
  });
});

function goBack() {
  router.visit(route("stock-transfers"));
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex items-center gap-3">
        <Button icon="fa fa-arrow-left" text rounded severity="secondary" :aria-label="t('Back')" @click="goBack" />
        <h2 class="text-2xl font-bold m-0">
          {{ t("Create Transfer") }}
        </h2>
      </div>
      <Button icon="fa fa-save" :label="t('Save')" raised class="uppercase" :loading="isSubmitting" @click="submit()" />
    </div>

    <div class="grid grid-cols-12 gap-4">
      <div class="lg:col-span-8 col-span-12">
        <Card class="mb-4">
          <template #title>{{ t("Transfer Details") }}</template>
          <template #content>
            <div class="grid grid-cols-12 gap-4">
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="from-store">
                    {{ t("From Store") }}
                    <span class="text-red-500">*</span>
                  </label>
                  <Select
                    id="from-store"
                    :model-value="values.from_store_id"
                    :options="storeOptions"
                    option-label="name"
                    option-value="value"
                    :placeholder="t('Select source store')"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.from_store_id }"
                    filter
                    @update:model-value="setFieldValue('from_store_id', $event)"
                  />
                  <small v-if="submitCount > 0 && errors.from_store_id" class="text-red-400 dark:text-red-300">
                    {{ errors.from_store_id }}
                  </small>
                </div>
              </div>
              <div class="md:col-span-6 col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="to-store">
                    {{ t("To Store") }}
                    <span class="text-red-500">*</span>
                  </label>
                  <Select
                    id="to-store"
                    :model-value="values.to_store_id"
                    :options="storeOptions"
                    option-label="name"
                    option-value="value"
                    :placeholder="t('Select destination store')"
                    :class="{ 'p-invalid': submitCount > 0 && !!errors.to_store_id }"
                    filter
                    @update:model-value="setFieldValue('to_store_id', $event)"
                  />
                  <small v-if="submitCount > 0 && errors.to_store_id" class="text-red-400 dark:text-red-300">
                    {{ errors.to_store_id }}
                  </small>
                </div>
              </div>
              <div class="col-span-12">
                <div class="flex flex-col gap-2 mb-3">
                  <label for="notes">{{ t("Notes") }}</label>
                  <Textarea id="notes" v-model="notes" v-bind="notesAttrs" :auto-resize="true" rows="3" />
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card>
          <template #title>{{ t("Transfer Items") }}</template>
          <template #content>
            <TransferLineItemsTable v-model="lineItems" />
            <small v-if="itemsError" class="text-red-400 dark:text-red-300 mt-2 block">{{ itemsError }}</small>
          </template>
        </Card>
      </div>

      <div class="lg:col-span-4 col-span-12">
        <Card>
          <template #title>{{ t("Summary") }}</template>
          <template #content>
            <div class="flex flex-col gap-4">
              <div>
                <span class="text-sm text-surface-500 block">{{ t("From Store") }}</span>
                <span v-if="selectedFromStoreName" class="font-medium">{{ selectedFromStoreName }}</span>
                <span v-else class="text-surface-400">---</span>
              </div>

              <div>
                <span class="text-sm text-surface-500 block">{{ t("To Store") }}</span>
                <span v-if="selectedToStoreName" class="font-medium">{{ selectedToStoreName }}</span>
                <span v-else class="text-surface-400">---</span>
              </div>

              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Total Items") }}</span>
                <span class="font-bold">{{ totalItems }}</span>
              </div>

              <div class="flex justify-between">
                <span class="text-surface-500">{{ t("Total Requested") }}</span>
                <span class="font-bold">{{ totalQuantityRequested }}</span>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>
