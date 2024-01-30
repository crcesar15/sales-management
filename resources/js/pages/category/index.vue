<template>
  <AppLayout>
    <ConfirmDialog />
    <Toast />
    <h1>Categories</h1>
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
      @page="onPage($event)"
      @sort="onSort($event)"
    >
      <template #header>
        <div class="grid">
          <div class="col-12 md:col-10">
            <input-text
              v-model="pagination.filter"
              type="text"
              placeholder="Search"
              class="w-full"
            />
          </div>
          <div class="col-12 md:col-2">
            <p-button
              label="Category"
              icon="fa fa-plus"
              class="w-full"
              @click="addCategory"
            />
          </div>
        </div>
      </template>
      <Column
        field="name"
        header="Name"
        sortable
      />
      <Column
        field="created_at"
        header="Created At"
        sortable
      />
      <Column
        field="updated_at"
        header="Updated At"
        sortable
      />
      <Column
        field="actions"
        header="Actions"
      >
        <template #body="row">
          <div class="flex justify-center">
            <p-button
              icon="fa fa-edit"
              text
              size="sm"
              @click="editCategory(row.data)"
            />
            <p-button
              icon="fa fa-trash"
              text
              size="sm"
              @click="deleteCategory(row.data.id)"
            />
          </div>
        </template>
      </Column>
    </DataTable>
    <CategoryEditor
      :category="selectedCategory"
      :show-dialog="editorToggle"
      @clearSelection="selectedCategory = {}; editorToggle = false;"
      @submitted="saveCategory"
    />
  </AppLayout>
</template>

<script>
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Toast from "primevue/toast";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import ConfirmDialog from "primevue/confirmdialog";
import AppLayout from "../../layouts/admin.vue";
import CategoryEditor from "./CategoryEditor.vue";

export default {
  components: {
    AppLayout,
    DataTable,
    Column,
    PButton,
    CategoryEditor,
    InputText,
    Toast,
    ConfirmDialog,
  },
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

      let url = `/categories?&per_page=${this.pagination.rows}&page=${this.pagination.page}&order_by=${this.pagination.sortField}`;

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
            summary: "Error",
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
        message: "Are you sure you want to delete this category?",
        header: "Delete Confirmation",
        icon: "fas fa-exclamation-triangle",
        accept: () => {
          axios.delete(`/categories/${category}`)
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: "Success",
                detail: "Category deleted successfully",
                life: 3000,
              });
              this.fetchCategories();
            })
            .catch((error) => {
              this.$toast.add({
                severity: "error",
                summary: "Error",
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
      axios.post("/categories", category)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "Category created successfully",
            life: 3000,
          });
          this.fetchCategories();
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response.data.message,
            life: 3000,
          });
        });
    },
    updateCategory(id, category) {
      axios.put(`/categories/${id}`, category)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "Category updated successfully",
            life: 3000,
          });
          this.fetchCategories();
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response.data.message,
            life: 3000,
          });
        });
    },
  },
};
</script>
