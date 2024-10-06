<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Roles") }}
      </h2>
      <p-button
        :label="$t('Add Role')"
        style="text-transform: uppercase"
        icon="fa fa-add"
        class="ml-2"
        @click="addRole"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="roles"
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
            {{ $t('No roles found') }}
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
                  col-12
                  md:justify-content-end
                  justify-content-center
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
            field="description"
            :header="$t('Description')"
            sortable
          />
          <Column
            field="users_count"
            :header="$t('Users')"
            :pt="{columnHeaderContent: 'justify-center'}"
          >
            <template
              #body="row"
            >
              <div
                class="flex justify-center"
              >
                <Tag
                  rounded
                  :value="row.data.users_count"
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
              <div class="flex justify-center">
                <p-button
                  v-tooltip.top="$t('Edit')"
                  icon="fa fa-edit"
                  text
                  size="sm"
                  @click="editRole(row.data)"
                />
                <p-button
                  v-tooltip.top="$t('Delete')"
                  icon="fa fa-trash"
                  text
                  size="sm"
                  @click="deleteRole(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <ItemEditor
      :role="selectedRole"
      :show-dialog="editorToggle"
      @clearSelection="selectedRole = {}; editorToggle = false;"
      @submitted="saveRole"
    />
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
import Tag from "primevue/tag";
import AppLayout from "../../layouts/admin.vue";
import ItemEditor from "./ItemEditor.vue";

export default {
  components: {
    DataTable,
    Column,
    PButton,
    ItemEditor,
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
      roles: [],
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
      selectedRole: {},
      editorToggle: false,
    };
  },
  watch: {
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetchRoles();
      },
    },
  },
  mounted() {
    this.fetchRoles();
  },
  methods: {
    fetchRoles() {
      this.loading = true;

      let url = `${route("api.roles")}?
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
          this.roles = response.data.data;
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
      this.fetchRoles();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchRoles();
    },
    addRole() {
      this.editorToggle = true;
      this.selectedRole = {};
    },
    editRole(role) {
      this.editorToggle = true;
      this.selectedRole = role;
    },
    deleteRole(id) {
      this.$confirm.require({
        message: this.$t("Are you sure you want to delete this role?"),
        header: this.$t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: this.$t("Cancel"),
        acceptLabel: this.$t("Delete"),
        rejectClass: "p-button-secondary",
        accept: () => {
          axios.delete(`${route("api.roles.destroy", id)}`)
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: this.$t("Success"),
                detail: this.$t("Role deleted successfully"),
                life: 3000,
              });
              this.fetchRoles();
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
    saveRole(id, role) {
      if (id) {
        this.updateRole(id, role);
      } else {
        this.createRole(role);
      }
    },
    createRole(role) {
      axios.post(route("api.roles.store"), role)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Role created successfully"),
            life: 3000,
          });
          this.fetchRoles();
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
    updateRole(id, role) {
      axios.put(`${route("api.roles.update", id)}`, role)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Role updated successfully"),
            life: 3000,
          });
          this.fetchRoles();
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
