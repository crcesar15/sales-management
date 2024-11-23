<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Product Catalog") }}
      </h2>
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="variants"
          resizable-columns
          lazy
          :total-records="pagination.total"
          :rows="pagination.rows"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ $t("No products found") }}
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div
                class="
                  flex
                  xl:col-span-3
                  lg:col-span-4
                  md:col-span-6
                  col-span-12
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
                    label: $t('Inactive'),
                    value: 'inactive',
                  }, {
                    label: $t('Archived'),
                    value: 'archived',
                  }]"
                  option-label="label"
                  option-value="value"
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
                    class="w-full"
                  />
                </IconField>
                <PButton
                  v-tooltip.top="$t('Filter')"
                  icon="fa-solid fa-filter"
                  class="ml-2"
                  raised
                  @click="toggleFilter"
                />
              </div>
            </div>
          </template>
          <Column
            field="name"
            :header="$t('Name')"
            sortable
          />
          <Column
            field="media"
            :header="$t('Image')"
            style="padding: 4px 12px; margin: 0px;"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="{ data }">
              <div class="flex justify-center">
                <img
                  v-if="data.media.length"
                  :src="data.media[0].url"
                  alt="Product Image"
                  class="
                    rounded-xl
                    border-slate-300
                    dark:border-slate-700
                    shadow-md
                  "
                  style="height: 55px; width: 55px;"
                >
                <div
                  v-else
                  class="
                    bg-surface-50
                    dark:bg-surface-950
                    rounded-xl
                    justify-center
                    items-center
                    flex
                    border-slate-300
                    dark:border-slate-700
                    shadow-md
                  "
                  style="height: 55px; width: 55px;"
                >
                  <p style="font-size: 18px; font-weight: bold;">
                    {{ data.name.substring(0, 2).toUpperCase() }}
                  </p>
                </div>
              </div>
            </template>
          </Column>
          <Column
            field="status"
            :header="$t('Status')"
            header-class="flex justify-center"
            class="flex justify-center"
          >
            <template #body="{ data }">
              <div
                style="height: 55px;"
                class="flex items-center"
              >
                <Tag
                  v-if="data.status === 'active'"
                  severity="success"
                  :value="$t('Active')"
                />
                <Tag
                  v-else-if="data.status === 'inactive'"
                  severity="warn"
                  :value="$t('Inactive')"
                />
                <Tag
                  v-else
                  severity="danger"
                  :value="$t('Archived')"
                />
              </div>
            </template>
          </Column>
          <Column
            field="brand"
            :header="$t('Brand')"
          />
          <Column
            field="categories"
            :header="$t('Category')"
          />
          <Column
            :header="$t('Actions')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template #body="{ data }">
              <span class="flex justify-center gap-2">
                <p-button
                  v-tooltip.top="$t('View Suppliers')"
                  icon="fa-solid fa-eye"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="viewSuppliers(data.id)"
                />
                <p-button
                  v-tooltip.top="$t('Edit Suppliers')"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editSuppliers(data.id)"
                />
              </span>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <Popover ref="filters">
      <div class="bg-surface-50 dark:bg-surface-950 p-4 border-rounded">
        <label>
          {{ $t("Filter By Supplier") }}
        </label>
        <Select
          v-model="selectedSupplier"
          class="w-full mt-2"
          :options="suppliers"
          :placeholder="$t('Supplier')"
          option-label="fullname"
          option-value="id"
          filter
          show-clear
          :loading="suppliersLoading"
          @filter="searchSuppliers"
        />
      </div>
    </Popover>
    <Dialog
      v-model:visible="showSuppliers"
      modal
      :header="$t('Suppliers')"
      :style="{ width: '50rem' }"
      :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
      <DataTable
        :value="productSuppliers"
        resizable-columns
        :rows="10"
        table-class="border-t"
      >
        <template #empty>
          <div class="flex w-full justify-between">
            <p class="m-0 flex items-center">
              {{ $t("No suppliers found") }}
            </p>
            <PButton
              icon="fa fa-plus"
              :label="$t('Add Supplier')"

              @click="editSuppliers(selectedVariantId)"
            />
          </div>
        </template>
        <Column
          field="fullname"
          :header="$t('Supplier')"
        />
        <Column
          field="email"
          :header="$t('Email')"
        />
        <Column
          field="phone"
          :header="$t('Phone')"
        />
        <Column
          field="price"
          :header="$t('Price')"
        />
        <Column
          field="payment_terms"
          :header="$t('Payment Term')"
        >
          <template #body="{ data }">
            <span v-if="data.payment_terms === 'debit'">
              {{ $t('Cash') }}
            </span>
            <span v-else-if="data.payment_terms === 'credit'">
              {{ $t('Credit') }}
            </span>
          </template>
        </Column>
        <Column
          field="status"
          :header="$t('Status')"
          header-class="flex justify-center"
          class="flex justify-center"
        >
          <template #body="{ data }">
            <div
              style="height: 55px;"
              class="flex items-center"
            >
              <Tag
                v-if="data.status === 'active'"
                severity="success"
                :value="$t('Active')"
              />
              <Tag
                v-else-if="data.status === 'inactive'"
                severity="warn"
                :value="$t('Inactive')"
              />
              <Tag
                v-else
                severity="danger"
                :value="$t('Archived')"
              />
            </div>
          </template>
        </Column>
        <Column
          :header="$t('Actions')"
          :pt="{columnHeaderContent: 'justify-center'}"
        >
          <template #body="{ data }">
            <span class="p-buttonset flex justify-center">
              <p-button
                v-tooltip.top="$t('Edit')"
                icon="fa fa-edit"
                text
                size="sm"
                @click="$inertia.visit(route('suppliers.edit', {supplier: data.id}))"
              />
            </span>
          </template>
        </Column>
      </DataTable>
    </Dialog>
  </div>
</template>
<script>
import DataTable from "primevue/datatable";
import Card from "primevue/card";
import Column from "primevue/column";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import ConfirmDialog from "primevue/confirmdialog";
import SelectButton from "primevue/selectbutton";
import Select from "primevue/select";
import Popover from "primevue/popover";
import Dialog from "primevue/dialog";
import Tag from "primevue/tag";
import AppLayout from "../../layouts/admin.vue";

export default {
  components: {
    DataTable,
    Column,
    Card,
    PButton,
    InputText,
    IconField,
    InputIcon,
    ConfirmDialog,
    SelectButton,
    Tag,
    Select,
    Popover,
    Dialog,
  },
  layout: AppLayout,
  data() {
    return {
      variants: [],
      pagination: {
        total: 0,
        first: 0,
        rows: 10,
        page: 1,
        sortField: "name",
        sortOrder: 1,
        filter: "",
      },
      loading: false,
      status: "all",
      selectedSupplier: null,
      selectedVariantId: null,
      suppliers: [],
      suppliersLoading: false,
      showSuppliers: false,
      productSuppliers: [],
    };
  },
  watch: {
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetchVariants();
      },
    },
    status() {
      this.pagination.page = 1;
      this.fetchVariants();
    },
    selectedSupplier() {
      this.pagination.page = 1;
      this.fetchVariants();
    },
  },
  mounted() {
    this.fetchVariants();
    this.searchSuppliers();
  },
  methods: {
    fetchVariants() {
      this.loading = true;

      const params = {
        per_page: this.pagination.rows,
        page: this.pagination.page,
        sortField: this.pagination.sortField,
        status: this.status,
        includes: "product,suppliers",
      };

      if (this.pagination.filter) {
        params.filter = this.pagination.filter;
      }

      if (this.selectedSupplier) {
        params.supplier = this.selectedSupplier;
      }

      if (this.pagination.sortOrder === -1) {
        params.order_direction = "desc";
      } else {
        params.order_direction = "asc";
      }

      axios.get(route("api.variants"), { params })
        .then((response) => {
          this.variants = response.data.data;
          this.pagination.total = response.data.meta.total;
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: this.$t("Error"),
            detail: error.response.data.message,
            life: 3000,
          });
        })
        .finally(() => {
          this.loading = false;
        });
    },
    searchSuppliers(event = null) {
      this.suppliersLoading = true;

      const body = {
        params: {
          per_page: 10,
          page: 1,
          order_by: "fullname",
          order_direction: "asc",
        },
      };

      if (event) {
        body.params.filter = event.value.toLowerCase();
      }

      axios
        .get(route("api.suppliers"), body)
        .then((response) => {
          this.suppliers = response.data.data;
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: this.$t("Error"),
            detail: error.response.data.message,
            life: 3000,
          });
        })
        .finally(() => {
          this.suppliersLoading = false;
        });
    },
    onPage(event) {
      this.pagination.page = event.page + 1;
      this.pagination.per_page = event.rows;
      this.fetchVariants();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchVariants();
    },
    editSuppliers(id) {
      this.$inertia.visit(route("catalog.edit", id));
    },
    toggleFilter(event) {
      this.$refs.filters.toggle(event);
    },
    viewSuppliers(id) {
      this.selectedVariantId = id;
      this.showSuppliers = true;
      this.productSuppliers = [];

      axios.get(route("api.variants.suppliers", id))
        .then((response) => {
          this.productSuppliers = response.data.data.map((supplier) => ({
            id: supplier.id,
            fullname: supplier.fullname,
            email: supplier.email,
            phone: supplier.phone,
            price: supplier.pivot.price,
            payment_terms: supplier.pivot.payment_terms,
            status: supplier.status,
          }));
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: this.$t("Error"),
            detail: error.response.data.message,
            life: 3000,
          });
        });
    },
  },
};
</script>
