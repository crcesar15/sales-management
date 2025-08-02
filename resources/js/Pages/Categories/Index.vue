<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Categories") }}
      </h2>
      <p-button
        v-can="'categories-create'"
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
            {{ $t('No categories found') }}
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
                  md:justify-end
                  col-span-12
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
                <p-button
                  v-can="'categories-edit'"
                  v-tooltip.top="$t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editCategory(row.data)"
                />
                <p-button
                  v-can="'categories-delete'"
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
import Tag from "primevue/tag";
import AppLayout from "../../Layouts/admin.vue";
import CategoryEditor from "./List/ItemEditor.vue";
import useDatetimeFormatter from "../../Composables/useDatetimeFormatter";

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
    Tag,
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
        perPage: 10,
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

      const params = new URLSearchParams();

      params.append("per_page", this.pagination.perPage);
      params.append("page", this.pagination.page);
      params.append("order_by", this.pagination.sortField);
      params.append("order_direction", this.pagination.sortOrder === -1 ? "desc" : "asc");

      if (this.pagination.filter) {
        params.append("filter", this.pagination.filter);
      }

      const url = `${route("api.categories")}?${params.toString()}`;

      axios.get(url)
        .then((response) => {
          this.categories = response.data.data.map((item) => ({
            ...item,
            created_at: useDatetimeFormatter(item.created_at),
            updated_at: useDatetimeFormatter(item.updated_at),
          }));
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
      this.pagination.perPage = event.rows;
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
        rejectClass: "p-button-secondary",
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
