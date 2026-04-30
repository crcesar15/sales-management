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
  useToast,
  useConfirm,
  type DataTablePageEvent,
  type DataTableSortEvent,
} from "primevue";

import AppLayout from "@layouts/admin.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";

import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { VendorResponse } from "@/Types/vendor-types";
import { useI18n } from "vue-i18n";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  vendors: {
    data: VendorResponse[];
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
const status = ref(props.filters.status ?? "all");
const sortField = ref(props.filters.order_by ?? "fullname");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);

const vendors = computed(() =>
  props.vendors.data.map((item) => ({
    ...item,
    created_at: useDatetimeFormatter(item.created_at),
    updated_at: useDatetimeFormatter(item.updated_at),
  })),
);

let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, (val) => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    router.visit(route("vendors"), {
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
  router.visit(route("vendors"), {
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
  router.visit(route("vendors"), {
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
  sortField.value = typeof event.sortField === "string" ? event.sortField : "fullname";
  sortOrder.value = event.sortOrder ?? 1;
  router.visit(route("vendors"), {
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

const editVendor = (vendor: VendorResponse) => {
  router.visit(route("vendors.edit", vendor.id));
};

const showProducts = (vendor: VendorResponse) => {
  router.visit(route("vendors.products", vendor.id));
};

const deleteVendor = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to delete this vendor?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("vendors.destroy", id), {
        onSuccess: () => {
          toast.add({
            severity: "success",
            summary: t("Success"),
            detail: t("Vendor deleted successfully"),
            life: 3000,
          });
        },
        onError: () => {
          toast.add({
            severity: "error",
            summary: t("Error"),
            detail: t("Could not delete vendor"),
            life: 3000,
          });
        },
      });
    },
  });
};
</script>

<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Vendors") }}
      </h2>
      <Button
        v-can="'vendor.create'"
        :label="t('Add Vendor')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="$inertia.visit(route('vendors.create'))"
      />
    </div>
    <ConfirmDialog />
    <Toast />
    <Card>
      <template #content>
        <DataTable
          :value="vendors"
          resizable-columns
          lazy
          :total-records="props.vendors.meta.total"
          :rows="props.vendors.meta.per_page"
          :first="(props.vendors.meta.current_page - 1) * props.vendors.meta.per_page"
          paginator
          sort-field="fullname"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No vendors found") }}</span>
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
                    { label: t('Archived'), value: 'archived' },
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
          <Column field="fullname" :header="t('Full Name')" sortable>
            <template #body="{ data }">
              <span :class="{ 'opacity-50': data.status === 'archived' }">
                {{ data.fullname }}
              </span>
            </template>
          </Column>
          <Column field="email" :header="t('Email')" sortable>
            <template #body="{ data }">
              <span :class="{ 'opacity-50': data.status === 'archived' }">
                {{ data.email ?? "—" }}
              </span>
            </template>
          </Column>
          <Column field="phone" :header="t('Phone')" sortable>
            <template #body="{ data }">
              <span :class="{ 'opacity-50': data.status === 'archived' }">
                {{ data.phone ?? "—" }}
              </span>
            </template>
          </Column>
          <Column field="status" :header="t('Status')" sortable>
            <template #body="{ data }">
              <Tag v-if="data.status === 'active'" severity="success" :value="t('Active')" rounded />
              <Tag v-else-if="data.status === 'inactive'" severity="warn" :value="t('Inactive')" rounded />
              <Tag v-else-if="data.status === 'archived'" severity="secondary" :value="t('Archived')" rounded />
            </template>
          </Column>
          <Column field="variants_count" :header="t('Catalog Items')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Tag rounded severity="secondary" :value="data.variants_count ?? 0" />
              </div>
            </template>
          </Column>
          <Column field="purchase_orders_count" :header="t('Purchase Orders')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center">
                <Tag rounded severity="secondary" :value="data.purchase_orders_count ?? 0" />
              </div>
            </template>
          </Column>
          <Column field="actions" :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="{ data }">
              <div class="flex justify-center gap-2">
                <Button
                  v-can="'vendor.edit'"
                  v-tooltip.top="t('Products')"
                  icon="fa fa-table-list"
                  text
                  size="large"
                  rounded
                  @click="showProducts(data)"
                />
                <Button
                  v-can="'vendor.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="large"
                  rounded
                  @click="editVendor(data)"
                />
                <Button
                  v-if="data.status !== 'archived'"
                  v-can="'vendor.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  size="large"
                  rounded
                  @click="deleteVendor(data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
