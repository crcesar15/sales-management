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
import {
  ref, watch,
} from "vue";
import {
  useToast, useConfirm, DataTable, Card, Column, ConfirmDialog, Button, InputText, IconField, InputIcon, Tag,
} from "primevue";
import { useI18n } from "vue-i18n";
import AppLayout from "../../Layouts/admin";
import BrandEditor from "./List/ItemEditor";
import useDatetimeFormatter from "../../Composables/useDatetimeFormatter";

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

let brands = [];
const loading = ref(false);

function fetchBrands() {
  loading.value = true;

  const params = new URLSearchParams();

  params.append("per_page", pagination.value.perPage);
  params.append("page", pagination.value.page);
  params.append("order_by", pagination.value.sortField);
  params.append("order_direction", pagination.value.sortOrder === -1 ? "desc" : "asc");

  if (pagination.value.filter) {
    params.append("filter", pagination.value.filter);
  }

  const url = `${route("api.brands")}?${params.toString()}`;

  axios.get(url)
    .then((response) => {
      brands = response.data.data.map((item) => ({
        ...item,
        created_at: useDatetimeFormatter(item.created_at),
        updated_at: useDatetimeFormatter(item.updated_at),
      }));
      pagination.value.total = response.data.meta.total;
      loading.value = false;
    })
    .catch((error) => {
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: error.response.data.message,
        life: 3000,
      });
      loading.value = false;
    });
}

const onPage = (event) => {
  pagination.value.page = event.page + 1;
  pagination.value.perPage = event.rows;
  fetchBrands();
};
const onSort = (event) => {
  pagination.value.sortField = event.sortField;
  pagination.value.sortOrder = event.sortOrder;
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
const selectedBrand = ref(null);
const showModal = ref(false);

const addBrand = () => {
  showModal.value = true;
  selectedBrand.value = null;
};
const editBrand = (brand) => {
  showModal.value = true;
  selectedBrand.value = brand;
};

const createBrand = (brand) => {
  axios.post(route("api.brands.store"), brand)
    .then(() => {
      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("Brand created successfully"),
        life: 3000,
      });
      fetchBrands();
    })
    .catch((error) => {
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: error.response.data.message,
        life: 3000,
      });
    });
};

const updateBrand = (brand) => {
  axios.put(`${route("api.brands.update", brand.id)}`, brand)
    .then(() => {
      toast.add({
        severity: "success",
        summary: t("Success"),
        detail: t("Brand updated successfully"),
        life: 3000,
      });
      fetchBrands();
    })
    .catch((error) => {
      toast.add({
        severity: "error",
        summary: t("Error"),
        detail: error.response.data.message,
        life: 3000,
      });
    });
};

const saveBrand = (brand) => {
  if (brand?.id) {
    updateBrand(brand);
  } else {
    createBrand(brand);
  }
};

const deleteBrand = (id) => {
  confirm.require({
    message: t("Are you sure you want to delete this brand?"),
    header: t("Confirm"),
    icon: "fas fa-exclamation-triangle",
    rejectLabel: t("Cancel"),
    acceptLabel: t("Delete"),
    rejectClass: "p-button-secondary",
    accept: () => {
      axios.delete(`${route("api.brands.destroy", id)}`)
        .then(() => {
          toast.add({
            severity: "success",
            summary: t("Success"),
            detail: t("Brand deleted successfully"),
            life: 3000,
          });
          fetchBrands();
        })
        .catch((error) => {
          toast.add({
            severity: "error",
            summary: t("Error"),
            detail: error.response.data.message,
            life: 3000,
          });
        });
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
