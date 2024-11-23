<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Suppliers") }}
      </h2>
      <PButton
        :label="$t('Add Supplier')"
        style="text-transform: uppercase"
        icon="fa fa-add"
        size="small"
        raised
        class="ml-2"
        @click="$inertia.visit(route('suppliers.create'))"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="suppliers"
          resizable-columns
          lazy
          :total-records="pagination.total"
          :rows="pagination.rows"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="fullname"
          :sort-order="1"
          table-class="border border-surface"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ $t("No suppliers found") }}
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
                    label: $t('Inactive'),
                    value: 'inactive',
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
                  md:justify-content-end
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
              </div>
            </div>
          </template>
          <Column
            field="fullname"
            :header="$t('Full Name')"
            sortable
          />
          <Column
            field="status"
            :header="$t('Status')"
            sortable
          >
            <template #body="{ data }">
              <div
                style="height: 55px;"
                class="flex items-center"
              >
                <Tag
                  v-if="data.status === 'active'"
                  severity="success"
                >
                  {{ $t('Active') }}
                </Tag>
                <Tag
                  v-else-if="data.status === 'inactive'"
                  severity="warn"
                >
                  {{ $t('Inactive') }}
                </Tag>
              </div>
            </template>
          </Column>
          <Column
            field="phone"
            :header="$t('Phone')"
            sortable
          />
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
                <p-button
                  v-tooltip.top="$t('Products')"
                  icon="fa fa-table-list"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="showProducts(row.data.id)"
                />
                <p-button
                  v-tooltip.top="$t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editSupplier(row.data)"
                />
                <p-button
                  v-show="row.data.status !== 'archived'"
                  v-tooltip.top="$t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="deleteSupplier(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
<script>
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Card from "primevue/card";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import ConfirmDialog from "primevue/confirmdialog";
import SelectButton from "primevue/selectbutton";
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
  },
  layout: AppLayout,
  data() {
    return {
      suppliers: [],
      pagination: {
        total: 0,
        first: 0,
        rows: 10,
        page: 1,
        sortField: "fullname",
        sortOrder: 1,
        filter: "",
      },
      loading: false,
      status: "all",
    };
  },
  watch: {
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetchSuppliers();
      },
    },
    status() {
      this.pagination.page = 1;
      this.fetchSuppliers();
    },
  },
  mounted() {
    this.fetchSuppliers();
  },
  methods: {
    onPage(event) {
      this.pagination.page = event.page + 1;
      this.pagination.per_page = event.rows;
      this.fetchSuppliers();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchSuppliers();
    },
    fetchSuppliers() {
      this.loading = true;

      let url = `${route("api.suppliers")}?per_page=${this.pagination.rows}
        &page=${this.pagination.page}
        &order_by=${this.pagination.sortField}
        &status=${this.status}`;

      if (this.pagination.sortOrder === -1) {
        url += "&order_direction=desc";
      } else {
        url += "&order_direction=asc";
      }

      if (this.pagination.filter) {
        url += `&filter=${this.pagination.filter}`;
      }

      axios.get(url)
        .then((response) => {
          this.suppliers = response.data.data;
          this.pagination.total = response.data.meta.total;
          this.loading = false;
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: this.$t("Error"),
            detail: error.response.data.message,
            life: 3000,
          });
          this.loading = false;
        });
    },
    editSupplier(id) {
      this.$inertia.visit(route("suppliers.edit", id));
    },
    showProducts(id) {
      this.$inertia.visit(route("suppliers.products", id));
    },
    deleteSupplier(id) {
      this.$confirm.require({
        message: this.$t("Are you sure you want to delete this supplier?"),
        header: this.$t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: this.$t("Cancel"),
        acceptLabel: this.$t("Delete"),
        rejectClass: "p-button-secondary",
        accept: () => {
          axios.delete(route("api.suppliers.destroy", id))
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: "Success",
                detail: this.$t("Supplier deleted successfully"),
                life: 3000,
              });
              this.fetchSuppliers();
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
      });
    },
  },
};
</script>
