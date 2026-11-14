<script setup lang="ts">
import { Button, useToast } from "primevue";
import { router } from "@inertiajs/vue3";
import { toTypedSchema } from "@vee-validate/yup";
import { useForm } from "vee-validate";
import { array, object, string, number } from "yup";
import { computed, nextTick, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import type { VariantLocationStore } from "@app-types/inventory-variant-types";
import StoreLocationInput from "./StoreLocationInput.vue";

const props = defineProps<{ variantId: number; stores: VariantLocationStore[]; canEdit: boolean; siblingCount: number }>();
const emit = defineEmits<{ (e: "update:dirty", value: boolean): void }>();
const { t } = useI18n();
const toast = useToast();
const saving = ref(false);
interface LocationForm { locations: Array<{ store_id: number; location_code: string }> }
const initial = () => ({ locations: props.stores.map((store) => ({ store_id: store.store_id, location_code: store.location_code ?? "" })) });
const schema = toTypedSchema(object({ locations: array().required().of(object({ store_id: number().required(), location_code: string().defined().max(50) }).required()) }));
const { values, errors, setFieldValue, setErrors, resetForm, handleSubmit, meta } = useForm<LocationForm>({ validationSchema: schema, initialValues: initial() });
function updateLocation(index: number, code: string): void {
  setFieldValue("locations", values.locations.map((item, i) => i === index ? { ...item, location_code: code } : item));
}
const isDirty = computed(() => meta.value.dirty);
watch(isDirty, (value) => emit("update:dirty", value), { immediate: true });
watch(() => props.variantId, () => resetForm({ values: initial() }));

const hasLocation = computed(() => values.locations.some((item) => item.location_code.trim() !== ""));

const save = (replicate: boolean) => handleSubmit((form) => {
  const changes = form.locations.filter((item) => {
    if (replicate) return true;
    const original = props.stores.find((store) => store.store_id === item.store_id)?.location_code ?? "";
    return item.location_code.trim().toUpperCase() !== original;
  }).map((item) => ({ ...item, location_code: item.location_code.trim().toUpperCase() || null }));
  if (!changes.length) { resetForm({ values: initial() }); return; }
  saving.value = true;
  router.put(route("inventory.variants.locations.update", { variant: props.variantId }), { locations: changes, replicate_locations: replicate }, {
    preserveScroll: true,
    onSuccess: () => { resetForm({ values: initial() }); toast.add({ severity: "success", summary: t("Success"), detail: t(replicate ? "Locations applied to all variants" : "Locations saved successfully"), life: 3000 }); },
    onError: (serverErrors) => {
      const mapped: Record<string, string> = {};
      for (const [key, message] of Object.entries(serverErrors)) {
        const match = /^locations\.(\d+)\.(.+)$/.exec(key);
        const storeId = match ? changes[Number(match[1])]?.store_id : undefined;
        const index = props.stores.findIndex((store) => store.store_id === storeId);
        mapped[index >= 0 && match ? `locations[${index}].${match[2]}` : key] = message;
      }
      setErrors(mapped);
      toast.add({ severity: "error", summary: t("Error"), detail: t("Please review the errors in the form"), life: 3000 });
      nextTick(() => document.querySelector<HTMLInputElement>(".p-invalid")?.focus());
    },
    onFinish: () => { saving.value = false; },
  });
});
const submit = save(false);
const replicateLocations = save(true);
</script>

<template>
  <form class="flex flex-col gap-6" @submit.prevent="submit">
    <div>
      <h2 class="m-0 text-lg font-semibold">{{ t("Locations by Store") }}</h2>
      <p class="mb-0 mt-1 text-sm text-surface-500 dark:text-surface-400">{{ t("Choose an existing location or enter a new code for each store.") }}</p>
    </div>
    <p v-if="!stores.length" class="m-0 text-surface-500 dark:text-surface-400">{{ t("No active stores available") }}</p>
    <div v-else class="flex flex-col divide-y divide-surface-200 border-y border-surface-200 dark:divide-surface-700 dark:border-surface-700">
      <div v-for="(store, index) in stores" :key="store.store_id" class="grid gap-2 py-4 sm:grid-cols-2 sm:items-start sm:gap-4">
        <label :for="`location-${store.store_id}`" class="font-medium">{{ store.store_name }} <span class="text-sm font-normal text-surface-500 dark:text-surface-400">({{ store.store_code }})</span></label>
        <StoreLocationInput
          :model-value="values.locations[index]?.location_code ?? ''"
          :store-id="store.store_id"
          :disabled="!canEdit || saving"
          :error="errors[`locations[${index}].location_code`]"
          @update:model-value="updateLocation(index, $event)"
        />
      </div>
    </div>
    <p v-if="canEdit && siblingCount > 0 && stores.length" class="m-0 text-sm text-surface-500 dark:text-surface-400">
      {{ t("Apply nonempty locations to {count} other variants, replacing their existing locations for those stores. Blank locations are ignored.", { count: siblingCount }) }}
    </p>
    <div v-if="canEdit && stores.length" class="flex flex-wrap justify-end gap-3">
      <Button v-if="siblingCount > 0" type="button" :label="t('Apply locations to all variants')" icon="fa fa-copy" outlined :loading="saving" :disabled="!hasLocation || saving" @click="replicateLocations" />
      <Button type="submit" :label="t('Save changes')" icon="fa fa-save" raised class="uppercase" :loading="saving" :disabled="!isDirty || saving" />
    </div>
  </form>
</template>
