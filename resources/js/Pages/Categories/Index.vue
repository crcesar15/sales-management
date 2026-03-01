<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Categories") }}
      </h2>
      <Button
        v-can="'category.create'"
        :label="$t('Add Category')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="addCategory"
      />
    </div>
    <ConfirmDialog />
    <Toast />
    <Card>
      <template #content>
        <DataTable
          :value="categories"
          resizable-columns
          lazy
          :total-records="pagination.total"
          :rows="pagination.perPage"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ $t('No categories found') }}
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div
                class="
                  md:col-span-6
                  col-span-12
                  flex
                  md:justify-start
                  justify-center
                "
              >
                <SelectButton
                  v-model="status"
                  :allow-empty="false"
                  :options="[{
                    label: $t('All'),
                    value: 'all',
                  }, {
                    label: $t('Active'),
                    value: 'active',
                  }, {
                    label: $t('Archived'),
                    value: 'archived',
                  }]"
                  optionLabel="label"
                  optionValue="value"
                  aria-labelledby="basic"
                />
              </div>
              <div
                class="
                  flex
                  xl:col-span-3
                  xl:col-start-10
                  lg:col-span-4
                  lg:col-start-9
                  md:col-span-6
                  md:col-start-7
                  col-span-12
                  md:justify-end
                  justify-center
                "
              >
                <IconField
                  icon-position="left"
                  class="w-full"
                >
                  <InputIcon class="fa fa-search" />
                  <InputText
                    v-model="pagination.filter"
                    :placeholder="$t('Search')"
                    fluid
                  />
                </IconField>
              </div>
            </div>
          </template>
          <Column
            field="name"
            :header="$t('Name')"
            sortable
          />
          <Column
            field="products_count"
            :header="$t('Products')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template
              #body="row"
            >
              <div class="flex justify-center">
                <Tag
                  rounded
                  severity="secondary"
                  :value="row.data.products_count"
                />
              </div>
            </template>
          </Column>
          <Column
            field="created_at"
            :header="$t('Created At')"
            sortable
          />
          <Column
            field="updated_at"
            :header="$t('Updated At')"
            sortable
          />
          <Column
            field="actions"
            :header="$t('Actions')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="row">
              <div class="flex justify-center gap-2">
                <Button
                  v-show="status !== 'archived'"
                  v-can="'category.edit'"
                  v-tooltip.top="$t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editCategory(row.data)"
                />
                <Button
                  v-show="status === 'archived'"
                  v-can="'category.restore'"
                  v-tooltip.top="$t('Restore')"
                  icon="fa fa-trash-arrow-up"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="restoreCategory(row.data)"
                />
                <Button
                  v-show="status !== 'archived'"
                  v-can="'category.delete'"
                  v-tooltip.top="$t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="deleteCategory(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <CategoryEditor
      v-model:show-modal="showModal"
      :category="selectedCategory"
      @submitted="saveCategory"
    />
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
  DataTablePageEvent,
  DataTableSortEvent
} from "primevue"

import AppLayout from "@layouts/admin.vue";
import CategoryEditor from "@pages/Categories/List/ItemEditor.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";
import { ref, watch } from "vue";
import { Category } from "@app-types/category-types";
import { useCategoryClient } from "@/Composables/useCategoryClient";
import { useI18n } from "vue-i18n";

// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Set Layout
defineOptions({
  layout: AppLayout
});

// List Categories
const categories = ref<Category[]>([]);
const status = ref("all");
const pagination = ref({
  total: 0,
  first: 0,
  page: 1,
  perPage: 10,
  sortField: "name",
  sortOrder: 1,
  filter: "",
});

const {loading, fetchCategoriesApi} = useCategoryClient();

const fetchCategories = async () => {
  const params = new URLSearchParams();

  params.append("per_page", pagination.value.perPage.toString());
  params.append("page", pagination.value.page.toString());
  params.append("order_by", pagination.value.sortField);
  params.append("order_direction", pagination.value.sortOrder === -1 ? "desc" : "asc");

  if (status.value !== "all") {
    params.append("status", status.value);
  }

  if (pagination.value.filter) {
    params.append("filter", pagination.value.filter);
  }

  try {
    const response = await fetchCategoriesApi(params.toString());
    categories.value = response.data.data.map((item: Category) => ({
      ...item,
      created_at: useDatetimeFormatter(item.created_at),
      updated_at: useDatetimeFormatter(item.updated_at),
    }));
    pagination.value.total = response.data.meta.total;
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error?.response?.data?.message ?? error),
      life: 3000,
    });
  }
};

const onPage = (event: DataTablePageEvent) => {
  pagination.value.page = event.page + 1;
  pagination.value.perPage = event.rows;
  fetchCategories();
}

const onSort = (event: DataTableSortEvent) => {
  pagination.value.sortField = typeof event.sortField === "string" ? event.sortField : "name";
  pagination.value.sortOrder = event.sortOrder ?? 0;
  fetchCategories();
}

watch(
  () => pagination.value.filter,
  () => {
    pagination.value.page = 1;
    fetchCategories();
  },
  {
    immediate: true,
    deep: true,
  },
);

watch(
  status,
  () => {
    pagination.value.first = 0;
    pagination.value.page = 1;
    fetchCategories();
  },
);

// Create/Edit Categories
let showModal = ref(false);
let selectedCategory = ref<Category | null>(null);

const addCategory = () => {
  selectedCategory.value = null;
  showModal.value = true;
};

const editCategory = (category: Category) => {
  selectedCategory.value = category;
  showModal.value = true;
};

const createCategory = async (category: Category) => {
  const { storeCategoryApi } = useCategoryClient();

  try {
    await storeCategoryApi(category);

    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("Category created successfully"),
      life: 3000,
    });

    fetchCategories();
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error?.response?.data?.message ?? error),
      life: 3000,
    });
  }
};

const updateCategory = async (id: number, category: Category) => {
  const { updateCategoryApi } = useCategoryClient();

  try {
    await updateCategoryApi(id, category);

    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("Category updated successfully"),
      life: 3000,
    });

    fetchCategories();
  } catch (error: any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t(error?.response?.data?.message ?? error),
      life: 3000,
    });
  }
};

const saveCategory = (category: Category) => {
  if (selectedCategory.value === null) {
    createCategory(category);
  } else {
    updateCategory(selectedCategory.value.id, category);
  }
};

// Delete Categories
const deleteCategory = async (id: number) => {
  const { destroyCategoryApi } = useCategoryClient();

  confirm.require({
    message: t("Are you sure you want to delete this category?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: async () => {
      try {
        await destroyCategoryApi(id);

        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("Category deleted successfully"),
          life: 3000,
        });

        fetchCategories();
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: t(error?.response?.data?.message ?? error),
          life: 3000,
        });
      }
    },
  });
};

// Restore Categories
const restoreCategory = (category: Category) => {
  confirm.require({
    message: t("Are you sure you want to restore this category?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Restore"),
    rejectClass: "p-button-secondary",
    accept: async () => {
      const { restoreCategoryApi } = useCategoryClient();
      try {
        await restoreCategoryApi(category.id);
        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("Category restored successfully"),
          life: 3000,
        });
        fetchCategories();
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: error?.response?.data?.message ?? error,
          life: 3000,
        });
      }
    },
  });
};
</script>
<style>
.sortable-column [data-pc-section="sort"] {
  padding-left: 0.2rem;
}

.sortable-column [data-pc-section="headercontent"] {
  display: flex;
  align-items: center;
}
.sortable-column th:hover {
  cursor: pointer;
  background-color: #ccc;
}
.p-datatable .p-datatable-tbody>tr.no-expander>td .p-row-toggler {
  display: none;
}
</style>
