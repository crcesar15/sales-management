<template>
  <div>
    <div class="flex mb-2">
      <div class="col-2 flex align-items-center">
        <h2 class="text-2xl font-bold m-0">
          {{ $t("Categories") }}
        </h2>
      </div>
      <div class="col-10 flex justify-content-end">
        <p-button
          :label="$t('Add Category')"
          class="ml-2"
          @click="addCategory"
        />
      </div>
    </div>
    <ConfirmDialog />
    <Toast />
    <Card>
      <template #content>
        <DataTable
          :value="categories"
          lazy
          :total-records="pagination.total"
          :rows="pagination.rows"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="name"
          :sort-order="1"
          :row-class="rowClass"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ $t('No categories found') }}
          </template>
          <template #header>
            <div class="grid">
              <div
                class="
                  flex
                  col-12
                  md:justify-content-end
                  justify-content-center
                "
              >
                <IconField icon-position="left">
                  <InputIcon class="fa fa-search" />
                  <InputText
                    v-model="pagination.filter"
                    :placeholder="$t('Search')"
                  />
                </IconField>
              </div>
            </div>
          </template>
          <Column
            field="name"
            :header="$t('Name')"
            header-class="surface-100"
            sortable
          />
          <Column
            field="products_count"
            :header="$t('Products')"
            header-class="surface-100"
          >
            <template
              #body="row"
            >
              <Badge
                :value="row.data.products_count"
              />
            </template>
          </Column>
          <Column
            field="created_at"
            :header="$t('Created At')"
            header-class="surface-100"
            sortable
          />
          <Column
            field="updated_at"
            :header="$t('Updated At')"
            header-class="surface-100"
            sortable
          />
          <Column
            field="actions"
            :header="$t('Actions')"
            header-class="surface-100"
          >
            <template #body="row">
              <div class="flex justify-center">
                <p-button
                  v-tooltip.top="$t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="sm"
                  @click="editCategory(row.data)"
                />
                <p-button
                  v-tooltip.top="$t('Delete')"
                  icon="fa fa-trash"
                  text
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
      :category="selectedCategory"
      :show-dialog="editorToggle"
      @clearSelection="selectedCategory = {}; editorToggle = false;"
      @submitted="saveCategory"
    />
  </div>
</template>

<script>
import DataTable from "primevue/datatable";
import Card from "primevue/card";
import Column from "primevue/column";
import Toast from "primevue/toast";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import ConfirmDialog from "primevue/confirmdialog";
import Badge from "primevue/badge";
import AppLayout from "../../layouts/admin.vue";
import CategoryEditor from "./CategoryEditor.vue";

export default {
  components: {
    DataTable,
    Column,
    PButton,
    CategoryEditor,
    InputText,
    Toast,
    ConfirmDialog,
    Card,
    IconField,
    InputIcon,
    Badge,
  },
  layout: AppLayout,
  data() {
    return {
      categories: [],
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
      selectedCategory: {},
      editorToggle: false,
    };
  },
  watch: {
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetchCategories();
      },
    },
  },
  mounted() {
    this.fetchCategories();
  },
  methods: {
    fetchCategories() {
      this.loading = true;

      let url = `${route("api.categories")}?
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
          this.categories = response.data.data;
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
      this.fetchCategories();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchCategories();
    },
    addCategory() {
      this.editorToggle = true;
      this.selectedCategory = {};
    },
    editCategory(category) {
      this.editorToggle = true;
      this.selectedCategory = category;
    },
    deleteCategory(category) {
      this.$confirm.require({
        message: this.$t("Are you sure you want to delete this category?"),
        header: this.$t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: this.$t("Cancel"),
        acceptLabel: this.$t("Delete"),
        accept: () => {
          axios.delete(`${route("api.categories.destroy", category)}`)
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: this.$t("Success"),
                detail: this.$t("Category deleted successfully"),
                life: 3000,
              });
              this.fetchCategories();
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
    saveCategory(id, category) {
      if (id) {
        this.updateCategory(id, category);
      } else {
        this.createCategory(category);
      }
    },
    createCategory(category) {
      axios.post(route("api.categories.store"), category)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Category created successfully"),
            life: 3000,
          });
          this.fetchCategories();
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
    updateCategory(id, category) {
      axios.put(`${route("api.categories.update", id)}`, category)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Category updated successfully"),
            life: 3000,
          });
          this.fetchCategories();
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
