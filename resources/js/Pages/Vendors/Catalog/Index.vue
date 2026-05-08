<script setup lang="ts">
import {
  DataTable,
  Card,
  Column,
  Toast,
  Button,
  InputText,
  IconField,
  InputIcon,
  ConfirmDialog,
  SelectButton,
  Tag,
  Badge,
  useToast,
  useConfirm,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";
import AppLayout from "@layouts/admin.vue";
import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import type { VendorResponse } from "@/Types/vendor-types";
import type { CatalogResponse } from "@/Types/catalog-types";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  vendor: VendorResponse;
  catalog: {
    data: CatalogResponse[];
    meta: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
  filters: {
    filter?: string | null;
    status?: string;
    order_by?: string;
    order_direction?: string;
    per_page?: number;
  };
}>();

const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "active");
const sortField = ref(props.filters.order_by ?? "created_at");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);

const catalogItems = computed(() => props.catalog.data);

let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, (val) => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    router.visit(route("vendors.catalog", props.vendor.id), {
      data: {
        filter: val,
        status: status.value,
        order_by: sortField.value,
        order_direction: sortOrder.value === -1 ? "desc" : "asc",
      },
      preserveState: true,
      replace: true,
    });
  }, 300);
});

watch(status, (val) => {
  router.visit(route("vendors.catalog", props.vendor.id), {
    data: {
      status: val,
      filter: filter.value,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
    },
    preserveState: true,
    replace: true,
  });
});

const onPage = (event: DataTablePageEvent) => {
  router.visit(route("vendors.catalog", props.vendor.id), {
    data: {
      page: event.page + 1,
      per_page: event.rows,
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      filter: filter.value,
      status: status.value,
    },
    preserveState: true,
    replace: true,
  });
};

const onSort = (event: DataTableSortEvent) => {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "created_at";
  sortOrder.value = event.sortOrder ?? 1;
  router.visit(route("vendors.catalog", props.vendor.id), {
    data: {
      order_by: sortField.value,
      order_direction: sortOrder.value === -1 ? "desc" : "asc",
      filter: filter.value,
      status: status.value,
    },
    preserveState: true,
    replace: true,
  });
};

const addEntry = () => {
  router.visit(route("vendors.catalog.create", props.vendor.id));
};

const editEntry = (entry: CatalogResponse) => {
  router.visit(route("vendors.catalog.edit", [props.vendor.id, entry.id]));
};

const deleteEntry = (entry: CatalogResponse) => {
  confirm.require({
    message: t("Are you sure you want to delete this catalog entry?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("vendors.catalog.destroy", [props.vendor.id, entry.id]), {
        onSuccess: () => {
          toast.add({
            severity: "success",
            summary: t("Success"),
            detail: t("Catalog entry deleted successfully"),
            life: 3000,
          });
        },
        onError: () => {
          toast.add({
            severity: "error",
            summary: t("Error"),
            detail: t("Could not delete catalog entry"),
            life: 3000,
          });
        },
      });
    },
  });
};

const goBack = () => {
  router.visit(route("vendors"));
};
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <div class="flex">
        <Button icon="fa fa-arrow-left" text severity="secondary" class="hover:shadow-md mr-2" @click="goBack" />
        <h2 class="text-2xl font-bold flex items-center m-0">{{ vendor.fullname }} — {{ t("Catalog") }}</h2>
      </div>
      <div class="flex flex-col justify-center">
        <Button v-can="'catalog.create'" :label="t('Add Entry')" icon="fa fa-plus" raised class="uppercase" @click="addEntry" />
      </div>
    </div>

    <ConfirmDialog />
    <Toast />

    <Card>
      <template #content>
        <DataTable
          :value="catalogItems"
          resizable-columns
          lazy
          :total-records="props.catalog.meta.total"
          :rows="props.catalog.meta.per_page"
          :first="(props.catalog.meta.current_page - 1) * props.catalog.meta.per_page"
          paginator
          sort-field="created_at"
          :sort-order="-1"
          @page="onPage"
          @sort="onSort"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No catalog entries found") }}</span>
            </div>
          </template>

          <template #header>
            <div class="grid grid-cols-12">
              <div class="md:col-span-6 col-span-12 flex md:justify-start justify-center">
                <SelectButton
                  v-model="status"
                  :allow-empty="false"
                  :options="[
                    { label: t('All'), value: 'all' },
                    { label: t('Active'), value: 'active' },
                    { label: t('Inactive'), value: 'inactive' },
                  ]"
                  option-label="label"
                  option-value="value"
                />
              </div>
              <div
                class="flex xl:col-span-3 xl:col-start-10 lg:col-span-4 lg:col-start-9 md:col-span-6 md:col-start-7 col-span-12 md:justify-end justify-center"
              >
                <IconField icon-position="left" class="w-full">
                  <InputIcon class="fa fa-search" />
                  <InputText v-model="filter" :placeholder="t('Search')" fluid />
                </IconField>
              </div>
            </div>
          </template>

          <Column field="product_name" :header="t('Product')" sortable>
            <template #body="{ data }">
              <div class="flex flex-row gap-1">
                <span class="font-bold">{{ data.product_variant?.product?.name ?? "—" }}</span>
                <div v-if="data.product_variant?.values?.length" class="flex flex-wrap gap-1">
                  <Badge v-for="opt in data.product_variant.values" :key="opt.option_name" :value="`${opt.value}`" />
                </div>
              </div>
            </template>
          </Column>

          <Column field="purchase_unit.name" :header="t('Purchase Unit')">
            <template #body="{ data }">
              <span>{{ data.purchase_unit?.name ?? t("Base unit") }}</span>
            </template>
          </Column>

          <Column field="price" :header="t('Price')" sortable>
            <template #body="{ data }">
              <span>BOB {{ data.price.toFixed(2) }}</span>
            </template>
          </Column>

          <Column field="status" :header="t('Status')" sortable>
            <template #body="{ data }">
              <Tag v-if="data.status === 'active'" severity="success" :value="t('Active')" rounded />
              <Tag v-else-if="data.status === 'inactive'" severity="warn" :value="t('Inactive')" rounded />
              <Tag v-else severity="secondary" :value="t('Archived')" rounded />
            </template>
          </Column>

          <Column field="minimum_order_quantity" :header="t('MOQ')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Tag v-if="data.minimum_order_quantity" rounded severity="secondary" :value="String(data.minimum_order_quantity)" />
                <span v-else class="text-surface-400">—</span>
              </div>
            </template>
          </Column>

          <Column field="lead_time_days" :header="t('Lead Time')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Tag v-if="data.lead_time_days" rounded severity="secondary" :value="`${data.lead_time_days}d`" />
                <span v-else class="text-surface-400">—</span>
              </div>
            </template>
          </Column>

          <Column :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center gap-2">
                <Button
                  v-can="'catalog.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  size="large"
                  @click="editEntry(data)"
                />
                <Button
                  v-can="'catalog.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  size="large"
                  class="btn-danger"
                  @click="deleteEntry(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
