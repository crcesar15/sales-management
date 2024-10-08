<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Catalog") }}
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
          table-class="border border-surface"
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
                  lg:col-span-4
                  md:col-span-6
                  col-span-12
                  md:justify-start
                  justify-center
                "
              >
                <SelectButton
                  v-model="status"
                  class="mt-2"
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
                  flex-wrap
                  lg:col-span-4
                  lg:col-start-5
                  md:col-span-6
                  md:col-start-7
                  col-span-12
                  lg:content-start
                  md:content-end
                  content-center"
              >
                <AutoComplete
                  v-model="selectedSupplier"
                  dropdown
                  class="mx-8 mt-2 md:mx-0 lg:mx-8 w-full"
                  force-selection
                  :suggestions="suppliers"
                  :placeholder="$t('Supplier')"
                  option-label="fullname"
                  :loading="suppliersLoading"
                  :fluid="true"
                  @complete="searchSuppliers"
                />
              </div>
              <div
                class="
                  flex
                  lg:col-span-4
                  lg:col-start-9
                  md:col-span-12
                  col-span-12
                  justify-center
                "
              >
                <IconField
                  icon-position="left"
                  class="w-full mt-2"
                >
                  <InputIcon class="fa fa-search" />
                  <InputText
                    v-model="pagination.filter"
                    :placeholder="$t('Search')"
                    class="w-full"
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
                  class="border-round"
                  style="height: 55px; width: 55px;"
                >
                <div
                  v-else
                  class="bg-gray-200 rounded-border justify-center items-center flex"
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
              <span class="p-buttonset flex justify-center">
                <p-button
                  v-tooltip.top="$t('View Suppliers')"
                  icon="fa-solid fa-eye"
                  text
                  size="sm"
                  @click="viewSuppliers(data.id)"
                />
                <p-button
                  v-tooltip.top="$t('Edit Suppliers')"
                  icon="fa fa-edit"
                  text
                  size="sm"
                  @click="editSuppliers(data.id)"
                />
              </span>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
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
import AutoComplete from "primevue/autocomplete";
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
    AutoComplete,
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
      suppliers: [],
      suppliersLoading: false,
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
      this.expandedRows = [];
      this.pagination.page = 1;
      this.fetchVariants();
    },
  },
  mounted() {
    this.fetchVariants();
  },
  methods: {
    fetchVariants() {
      this.loading = true;

      const params = {
        per_page: this.pagination.rows,
        page: this.pagination.page,
        sortField: this.pagination.sortField,
        status: this.status,
        includes: "product",
      };

      if (this.pagination.filter) {
        params.filter = this.pagination.filter;
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
    searchSuppliers(event) {
      this.suppliersLoading = true;

      axios
        .get(route("api.suppliers"), {
          params: {
            per_page: 10,
            page: 1,
            order_by: "fullname",
            order_direction: "asc",
            filter: event.query.toLowerCase(),
          },
        })
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
  },
};
</script>
