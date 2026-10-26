<script setup lang="ts">
import { Button, Card, Column, ConfirmDialog, DataTable, IconField, InputIcon, InputText, SelectButton, Tag, useConfirm, useToast } from "primevue";
import type { DataTablePageEvent, DataTableSortEvent } from "primevue";
import { computed, onUnmounted, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { useI18n } from "vue-i18n";
import type { CustomerResponse } from "@/Types/customer-types";

const props = defineProps<{
  customers: { data: CustomerResponse[]; meta: { current_page: number; last_page: number; per_page: number; total: number } };
  filters: { filter?: string | null; status?: string; order_by?: string; order_direction?: string; per_page?: number };
  routes: { index: string; create: string; edit: (id: number) => string; destroy: (id: number) => string };
  toastGroup?: string;
}>();
const emit = defineEmits<{ deleted: [customerId: number] }>();

const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();
const { formatDatetime } = useDatetimeFormatter();
const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "active");
const sortField = ref(props.filters.order_by ?? "tax_id_name");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);
let filterTimer: ReturnType<typeof setTimeout> | undefined;

const customers = computed(() =>
  props.customers.data.map((item) => ({ ...item, created_at: item.created_at ? formatDatetime(item.created_at) : null, updated_at: item.updated_at ? formatDatetime(item.updated_at) : null })),
);

function visit(data: Record<string, string | number>): void {
  router.visit(props.routes.index, { data, preserveState: true, preserveScroll: true, replace: true });
}

function currentFilters(extra: Record<string, string | number> = {}): Record<string, string | number> {
  return {
    filter: filter.value,
    status: status.value,
    order_by: sortField.value,
    order_direction: sortOrder.value === -1 ? "desc" : "asc",
    ...extra,
  };
}

watch(filter, (value) => {
  if (filterTimer !== undefined) window.clearTimeout(filterTimer);
  filterTimer = window.setTimeout(() => visit(currentFilters({ filter: value, page: 1 })), 300);
});

watch(status, (value) => visit(currentFilters({ status: value, page: 1 })));

onUnmounted(() => {
  if (filterTimer !== undefined) window.clearTimeout(filterTimer);
});

function onPage(event: DataTablePageEvent): void {
  visit(currentFilters({ page: event.page + 1, per_page: event.rows }));
}

function onSort(event: DataTableSortEvent): void {
  sortField.value = typeof event.sortField === "string" ? event.sortField : "tax_id_name";
  sortOrder.value = event.sortOrder ?? 1;
  visit(currentFilters({ page: 1 }));
}

function deleteCustomer(customer: CustomerResponse): void {
  confirm.require({
    message: t("Are you sure you want to delete this customer?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectProps: { severity: "secondary" },
    acceptProps: { severity: "danger" },
    accept: () => {
      const form = useForm({});
      form.delete(props.routes.destroy(customer.id), {
        preserveScroll: true,
        onSuccess: () => {
          emit("deleted", customer.id);
          toast.add({ severity: "success", summary: t("Success"), detail: t("Customer deleted successfully"), life: 3000, group: props.toastGroup });
        },
        onError: (errors) => toast.add({ severity: "error", summary: t("Error"), detail: errors.customer ?? t("Could not delete customer"), life: 3000, group: props.toastGroup }),
      });
    },
  });
}
</script>

<template>
  <div>
    <div class="mb-3 flex flex-row justify-between">
      <h2 class="m-0 flex items-end text-2xl font-bold">{{ t("Customers") }}</h2>
      <Button v-can="'customer.create'" :label="t('Add Customer')" icon="fa fa-add" raised class="ml-2 uppercase" @click="router.visit(routes.create)" />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="customers"
          resizable-columns
          lazy
          :total-records="props.customers.meta.total"
          :rows="props.customers.meta.per_page"
          :first="(props.customers.meta.current_page - 1) * props.customers.meta.per_page"
          paginator
          :sort-field="sortField"
          :sort-order="sortOrder"
          @page="onPage"
          @sort="onSort"
        >
          <template #empty><div class="flex flex-col items-center py-8 text-surface-400"><i class="fa fa-folder-open mb-3 text-4xl" /><span>{{ t("No customers found") }}</span></div></template>
          <template #header>
            <div class="grid grid-cols-12">
              <div class="col-span-12 flex justify-center md:col-span-6 md:justify-start">
                <SelectButton v-model="status" :allow-empty="false" :options="[{ label: t('All'), value: 'all' }, { label: t('Active'), value: 'active' }, { label: t('Inactive'), value: 'inactive' }]" option-label="label" option-value="value" />
              </div>
              <div class="col-span-12 flex justify-center md:col-span-6 md:col-start-7 md:justify-end lg:col-span-4 lg:col-start-9 xl:col-span-3 xl:col-start-10">
                <IconField icon-position="left" class="w-full"><InputIcon class="fa fa-search" /><InputText v-model="filter" :placeholder="t('Search')" fluid /></IconField>
              </div>
            </div>
          </template>
          <Column field="tax_id" :header="t('Tax ID')" sortable><template #body="{ data }"><span :class="{ 'opacity-50': data.status === 'inactive' }">{{ data.tax_id || "-" }}</span></template></Column>
          <Column field="tax_id_name" :header="t('Tax ID Name')" sortable><template #body="{ data }"><span :class="{ 'opacity-50': data.status === 'inactive' }">{{ data.tax_id_name || "-" }}</span></template></Column>
          <Column field="first_name" :header="t('First Name')" sortable><template #body="{ data }"><span :class="{ 'opacity-50': data.status === 'inactive' }">{{ data.first_name || "-" }}</span></template></Column>
          <Column field="last_name" :header="t('Last Name')" sortable><template #body="{ data }"><span :class="{ 'opacity-50': data.status === 'inactive' }">{{ data.last_name || "-" }}</span></template></Column>
          <Column field="email" :header="t('Email')" sortable><template #body="{ data }"><span :class="{ 'opacity-50': data.status === 'inactive' }">{{ data.email || "-" }}</span></template></Column>
          <Column field="phone" :header="t('Phone')" sortable><template #body="{ data }"><span :class="{ 'opacity-50': data.status === 'inactive' }">{{ data.phone || "-" }}</span></template></Column>
          <Column field="status" :header="t('Status')" sortable><template #body="{ data }"><Tag v-if="data.status === 'active'" severity="success" :value="t('Active')" rounded /><Tag v-else severity="warn" :value="t('Inactive')" rounded /></template></Column>
          <Column field="sales_orders_count" :header="t('Sales Orders')" :pt="{ columnHeaderContent: 'justify-center' }"><template #body="{ data }"><div class="flex justify-center"><span class="inline-flex items-center justify-center rounded-full bg-surface-100 px-3 py-1 text-sm dark:bg-surface-700">{{ data.sales_orders_count ?? 0 }}</span></div></template></Column>
          <Column field="actions" :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }"><template #body="{ data }"><div class="flex justify-center gap-2"><Button v-can="'customer.edit'" v-tooltip.top="t('Edit')" icon="fa fa-edit" text size="large" rounded @click="router.visit(routes.edit(data.id))" /><Button v-can="'customer.delete'" v-tooltip.top="t('Delete')" icon="fa fa-trash" text size="large" rounded @click="deleteCustomer(data)" /></div></template></Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
