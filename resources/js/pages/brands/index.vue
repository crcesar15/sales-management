<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Brands") }}
      </h2>
      <p-button
        :label="$t('Add Brand')"
        class="ml-2"
        @click="addBrand"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="brands"
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
            {{ $t('No brands found') }}
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
            field="products_count"
            :header="$t('Products')"
          >
            <template
              #body="row"
            >
              <Tag
                :value="row.data.products_count"
                rounded
              />
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
          >
            <template #body="row">
              <div class="flex justify-center">
                <p-button
                  v-tooltip.top="$t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="sm"
                  @click="editBrand(row.data)"
                />
                <p-button
                  v-tooltip.top="$t('Delete')"
                  icon="fa fa-trash"
                  text
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
      :brand="selectedBrand"
      :show-dialog="editorToggle"
      @clearSelection="selectedBrand = {}; editorToggle = false;"
      @submitted="saveBrand"
    />
  </div>
</template>

<script>
import DataTable from "primevue/datatable";
import Card from "primevue/card";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Tag from "primevue/tag";
import AppLayout from "../../layouts/admin.vue";
import BrandEditor from "./BrandEditor.vue";

export default {
  components: {
    DataTable,
    Column,
    PButton,
    BrandEditor,
    InputText,
    ConfirmDialog,
    Card,
    IconField,
    InputIcon,
    Tag,
  },
  layout: AppLayout,
  data() {
    return {
      brands: [],
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
      selectedBrand: {},
      editorToggle: false,
    };
  },
  watch: {
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetchBrands();
      },
    },
  },
  mounted() {
    this.fetchBrands();
  },
  methods: {
    fetchBrands() {
      this.loading = true;

      let url = `${route("api.brands")}?
        &per_page=${this.pagination.rows}
        &page=${this.pagination.page}
        &order_by=${this.pagination.sortField}`;

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
          this.brands = response.data.data;
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
    onPage(event) {
      this.pagination.page = event.page + 1;
      this.pagination.per_page = event.rows;
      this.fetchBrands();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchBrands();
    },
    addBrand() {
      this.editorToggle = true;
      this.selectedBrand = {};
    },
    editBrand(brand) {
      this.editorToggle = true;
      this.selectedBrand = brand;
    },
    deleteBrand(id) {
      this.$confirm.require({
        message: this.$t("Are you sure you want to delete this brand?"),
        header: this.$t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: this.$t("Cancel"),
        acceptLabel: this.$t("Delete"),
        rejectClass: "p-button-secondary",
        accept: () => {
          axios.delete(`${route("api.brands.destroy", id)}`)
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: this.$t("Success"),
                detail: this.$t("Brand deleted successfully"),
                life: 3000,
              });
              this.fetchBrands();
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
    saveBrand(id, brand) {
      if (id) {
        this.updateBrand(id, brand);
      } else {
        this.createBrand(brand);
      }
    },
    createBrand(brand) {
      axios.post(route("api.brands.store"), brand)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Brand created successfully"),
            life: 3000,
          });
          this.fetchBrands();
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
    updateBrand(id, brand) {
      axios.put(`${route("api.brands.update", id)}`, brand)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Brand updated successfully"),
            life: 3000,
          });
          this.fetchBrands();
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
