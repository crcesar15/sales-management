<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ t("Brands") }}
      </h2>
      <Button
        v-can="'brands-create'"
        :label="t('Add Brand')"
        style="text-transform: uppercase"
        icon="fa fa-add"
        raised
        class="ml-2"
        @click="addBrand"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="brands"
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
            {{ t('No brands found') }}
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div
                class="
                  flex
                  lg:col-span-3
                  lg:col-start-10
                  md:col-span-4
                  md:col-start-9
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
                    :placeholder="t('Search')"
                    class="w-full"
                  />
                </IconField>
              </div>
            </div>
          </template>
          <Column
            field="name"
            :header="t('Name')"
            sortable
          />
          <Column
            field="products_count"
            :header="t('Products')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template
              #body="row"
            >
              <div class="flex justify-center">
                <Tag
                  severity="secondary"
                  :value="row.data.products_count"
                  rounded
                />
              </div>
            </template>
          </Column>
          <Column
            field="created_at"
            :header="t('Created At')"
            sortable
          />
          <Column
            field="updated_at"
            :header="t('Updated At')"
            sortable
          />
          <Column
            field="actions"
            :header="t('Actions')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="row">
              <div class="flex justify-center gap-2">
                <Button
                  v-can="'brands-edit'"
                  v-tooltip.top="t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editBrand(row.data)"
                />
                <Button
                  v-can="'brands-delete'"
                  v-tooltip.top="t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="deleteBrand(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <BrandEditor
      v-model:show-modal="showModal"
      :brand="selectedBrand"
      @submitted="saveBrand"
    />
  </div>
</template>

<script setup lang="ts">
import AppLayout from '@layouts/admin.vue';
import BrandEditor from "@pages/Brands/List/ItemEditor.vue";
import useDatetimeFormatter from "@composables/useDatetimeFormatter";

import {
  Ref,
  ref,
  watch,
} from "vue";

import {
  useToast,
  useConfirm,
  DataTable,
  Card,
  Column,
  ConfirmDialog,
  Button,
  InputText,
  IconField,
  InputIcon,
  Tag,
  DataTablePageEvent,
} from "primevue";

import { useI18n } from "vue-i18n";
import { useBrandsAPI } from "@composables/useBrandsApi";
import { Brand } from '@app-types/brand-types';

// Set composables
const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// Layout
defineOptions({
  layout: AppLayout,
});

// List brands
const pagination = ref({
  total: 0,
  first: 0,
  page: 1,
  perPage: 10,
  sortField: "name",
  sortOrder: 1,
  filter: "",
});

const { loading, fetchBrandsAPI } = useBrandsAPI();

let brands = ref<Brand[]>();

const fetchBrands = async () => {
  const params = new URLSearchParams();

  params.append("per_page", pagination.value.perPage.toString());
  params.append("page", pagination.value.page.toString());
  params.append("order_by", pagination.value.sortField);
  params.append("order_direction", pagination.value.sortOrder === -1 ? "desc" : "asc");

  if (pagination.value.filter) {
    params.append("filter", pagination.value.filter);
  }

  try {
    const response = await fetchBrandsAPI(params.toString());

    brands.value = response.data.data.map((item:Brand) => ({
      ...item,
      created_at: useDatetimeFormatter(item.created_at),
      updated_at: useDatetimeFormatter(item.updated_at),
    }));
    pagination.value.total = response.data.meta.total;
  } catch (error:any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: error.response.data.message,
      life: 3000,
    });
  }
};

const onPage = (event:DataTablePageEvent ) => {
  pagination.value.page = event.page + 1;
  pagination.value.perPage = event.rows;
  fetchBrands();
};
const onSort = (event:DataTablePageEvent) => {
  pagination.value.sortField = typeof event.sortField === 'string' ? event.sortField : 'name';
  pagination.value.sortOrder = event.sortOrder ?? 0;
  fetchBrands();
};

watch(
  () => pagination.value.filter,
  () => {
    pagination.value.page = 1;
    fetchBrands();
  },
  {
    immediate: true,
    deep: true,
  },
);

// Add/Edit Brand
const selectedBrand = ref<Brand | null>(null);
const showModal = ref(false);

const addBrand = () => {
  showModal.value = true;
  selectedBrand.value = null;
};
const editBrand = (brand: Brand) => {
  showModal.value = true;
  selectedBrand.value = brand;
};


const createBrand = async (brand:Pick<Brand, 'name'>) => {
  const {storeBrandAPI} = useBrandsAPI();

  try {
    await storeBrandAPI(brand);
    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("Brand created successfully"),
      life: 3000,
    });
    fetchBrands();
  } catch (error:any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: error.response.data.message,
      life: 3000,
    });
  }
};

const updateBrand = async (brand: Brand) => {
  const {updateBrandAPI} = useBrandsAPI();

  try {
    await updateBrandAPI(brand.id, brand);
    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("Brand updated successfully"),
      life: 3000,
    });
    fetchBrands();
  } catch (error:any) {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: error?.response?.data?.message ?? error,
      life: 3000,
    });
  }
};

const saveBrand = (brand:Brand | Pick<Brand, 'id' | 'name' >) => {
  if (brand?.id !== undefined) {
    updateBrand(brand as Brand);
  } else {
    createBrand(brand);
  }
};

const deleteBrand = (id:number) => {
  const {destroyBrandAPI} = useBrandsAPI();

  confirm.require({
    message: t("Are you sure you want to delete this brand?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: async () => {
      try {
        await destroyBrandAPI(id);
        toast.add({
          severity: "success",
          summary: t("Success"),
          detail: t("Brand deleted successfully"),
          life: 3000,
        });
        fetchBrands();
      } catch (error: any) {
        toast.add({
          severity: "error",
          summary: t("Error"),
          detail: error.response.data.message,
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
