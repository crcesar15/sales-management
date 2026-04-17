<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Categories") }}
      </h2>
      <Button v-can="'category.create'" :label="t('Add Category')" icon="fa fa-add" raised class="ml-2 uppercase" @click="addCategory" />
    </div>
    <ConfirmDialog />
    <Toast />
    <Card>
      <template #content>
        <DataTable
          :value="categories"
          resizable-columns
          lazy
          :total-records="props.categories.meta.total"
          :rows="props.categories.meta.per_page"
          :first="(props.categories.meta.current_page - 1) * props.categories.meta.per_page"
          paginator
          sort-field="name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            <div class="flex flex-col items-center py-8 text-surface-400">
              <i class="fa fa-folder-open text-4xl mb-3"></i>
              <span>{{ t("No categories found") }}</span>
            </div>
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div class="md:col-span-6 col-span-12 flex md:justify-start justify-center">
                <SelectButton
                  v-model="status"
                  :allow-empty="false"
                  :options="[
                    {
                      label: t('Active'),
                      value: 'active',
                    },
                    {
                      label: t('Archived'),
                      value: 'archived',
                    },
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
          <Column field="name" :header="t('Name')" sortable />
          <Column field="products_count" :header="t('Products')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="row">
              <div class="flex justify-center">
                <Tag rounded severity="secondary" :value="row.data.products_count" />
              </div>
            </template>
          </Column>
          <Column field="created_at" :header="t('Created At')" sortable />
          <Column field="updated_at" :header="t('Updated At')" sortable />
          <Column field="actions" :header="t('Actions')" :pt="{ columnHeaderContent: 'justify-center' }">
            <template #body="row">
              <div class="flex justify-center gap-2">
                <Button
                  v-if="status !== 'archived'"
                  v-can="'category.edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="large"
                  rounded
                  @click="editCategory(row.data)"
                />
                <Button
                  v-if="status === 'archived'"
                  v-can="'category.restore'"
                  v-tooltip.top="t('Restore')"
                  icon="fa fa-trash-arrow-up"
                  text
                  size="large"
                  rounded
                  @click="restoreCategory(row.data.id)"
                />
                <Button
                  v-if="status !== 'archived'"
                  v-can="'category.delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  size="large"
                  rounded
                  @click="deleteCategory(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <CategoryEditor v-model:show-modal="showModal" :category="selectedCategory" />
  </div>
</template>

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
import CategoryEditor from "@pages/Categories/List/ItemEditor.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { type CategoryResponse } from "@/Types/category-types";
import { useI18n } from "vue-i18n";

// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Set Layout
defineOptions({ layout: AppLayout });

// Props from Inertia
const props = defineProps<{
  categories: {
    data: CategoryResponse[];
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

// Local filter/sort state
const filter = ref(props.filters.filter ?? "");
const status = ref(props.filters.status ?? "active");
const sortField = ref(props.filters.order_by ?? "name");
const sortOrder = ref(props.filters.order_direction === "desc" ? -1 : 1);

// Formatted rows
const categories = computed(() =>
  props.categories.data.map((item) => ({
    ...item,
    created_at: useDatetimeFormatter(item.created_at),
    updated_at: useDatetimeFormatter(item.updated_at),
  })),
);

// Debounced filter watch
let filterTimer: ReturnType<typeof setTimeout>;
watch(filter, (val) => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(() => {
    router.visit(route("categories"), {
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
  router.visit(route("categories"), {
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
  router.visit(route("categories"), {
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
  sortField.value = typeof event.sortField === "string" ? event.sortField : "name";
  sortOrder.value = event.sortOrder ?? 1;
  router.visit(route("categories"), {
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

// Create/Edit Categories
let showModal = ref(false);
let selectedCategory = ref<CategoryResponse | null>(null);

const addCategory = () => {
  selectedCategory.value = null;
  showModal.value = true;
};

const editCategory = (category: CategoryResponse) => {
  selectedCategory.value = category;
  showModal.value = true;
};

// Delete Category
const deleteCategory = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to delete this category?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.delete(route("categories.destroy", id), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Category deleted successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not delete category"), life: 3000 });
        },
      });
    },
  });
};

// Restore Category
const restoreCategory = (id: number) => {
  confirm.require({
    message: t("Are you sure you want to restore this category?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Restore"),
    rejectClass: "p-button-secondary",
    accept: () => {
      const form = useForm({});
      form.put(route("categories.restore", id), {
        onSuccess: () => {
          toast.add({ severity: "success", summary: t("Success"), detail: t("Category restored successfully"), life: 3000 });
        },
        onError: () => {
          toast.add({ severity: "error", summary: t("Error"), detail: t("Could not restore category"), life: 3000 });
        },
      });
    },
  });
};
</script>
