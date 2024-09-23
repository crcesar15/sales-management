<template>
  <div>
    <div class="flex mb-2">
      <div class="col-2 flex align-items-center">
        <h2 class="text-2xl font-bold m-0">
          {{ $t("Users") }}
        </h2>
      </div>
      <div class="col-10 flex justify-content-end">
        <p-button
          :label="$t('Add User')"
          class="ml-2"
          @click="addUser"
        />
      </div>
    </div>
    <ConfirmDialog />
    <Toast />
    <Card>
      <template #content>
        <DataTable
          :value="users"
          lazy
          :total-records="pagination.total"
          :rows="pagination.rows"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="first_name"
          :sort-order="1"
          :row-class="rowClass"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ $t('No users found') }}
          </template>
          <template #header>
            <div class="grid">
              <div class="xl:col-6 lg:col-6 md:col-6 col-12 flex md:justify-content-start justify-content-center">
                <SelectButton
                  v-model="status"
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
                  xl:col-6
                  lg:col-6
                  md:col-6
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
            field="first_name"
            :header="$t('First Name')"
            header-class="surface-100"
            sortable
          />
          <Column
            field="last_name"
            :header="$t('Last Name')"
            header-class="surface-100"
            sortable
          />
          <Column
            field="role.name"
            :header="$t('Role')"
            header-class="surface-100"
          />
          <Column
            field="status"
            :header="$t('Status')"
            header-class="surface-100"
            sortable
          >
            <template #body="{ data }">
              <div
                style="height: 55px;"
                class="flex align-items-center"
              >
                <span
                  v-if="data.status === 'active'"
                  class="p-tag p-tag-success"
                >
                  {{ $t('Active') }}
                </span>
                <span
                  v-else-if="data.status === 'inactive'"
                  class="p-tag p-tag-warning"
                >
                  {{ $t('Inactive') }}
                </span>
                <span
                  v-else
                  class="p-tag p-tag-danger"
                >
                  {{ $t('Archived') }}
                </span>
              </div>
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
                  @click="editUser(row.data)"
                />
                <p-button
                  v-show="row.data.status === 'archived'"
                  v-tooltip.top="$t('Restore')"
                  icon="fa fa-trash-arrow-up"
                  text
                  size="sm"
                  @click="restoreUser(row.data)"
                />
                <p-button
                  v-show="row.data.status !== 'archived'"
                  v-tooltip.top="$t('Delete')"
                  icon="fa fa-trash"
                  text
                  size="sm"
                  @click="deleteUser(row.data.id)"
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
import Card from "primevue/card";
import Column from "primevue/column";
import Toast from "primevue/toast";
import PButton from "primevue/button";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import ConfirmDialog from "primevue/confirmdialog";
import SelectButton from "primevue/selectbutton";
import AppLayout from "../../layouts/admin.vue";

export default {
  components: {
    DataTable,
    Column,
    PButton,
    InputText,
    Toast,
    ConfirmDialog,
    Card,
    IconField,
    InputIcon,
    SelectButton,
  },
  layout: AppLayout,
  data() {
    return {
      users: [],
      pagination: {
        total: 0,
        first: 0,
        rows: 10,
        page: 1,
        sortField: "first_name",
        sortOrder: 1,
        filter: "",
      },
      loading: false,
      selectedUser: {},
      editorToggle: false,
      status: "all",
    };
  },
  watch: {
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetchUsers();
      },
    },
    status() {
      this.pagination.page = 1;
      this.fetchUsers();
    },
  },
  mounted() {
    this.fetchUsers();
  },
  methods: {
    fetchUsers() {
      this.loading = true;

      let url = `${route("api.users")}?includes=role
        &per_page=${this.pagination.rows}
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
          this.users = response.data.data;
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
      this.fetchUsers();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchUsers();
    },
    restoreUser(id) {
      this.$confirm.require({
        message: this.$t("Are you sure you want to restore this user?"),
        header: this.$t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: this.$t("Cancel"),
        acceptLabel: this.$t("Restore"),
        accept: () => {
          axios.put(route("api.users.restore", id))
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: this.$t("Success"),
                detail: this.$t("User restored successfully"),
                life: 3000,
              });
              this.fetchUsers();
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
    deleteUser(id) {
      this.$confirm.require({
        message: this.$t("Are you sure you want to delete this user?"),
        header: this.$t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: this.$t("Cancel"),
        acceptLabel: this.$t("Delete"),
        accept: () => {
          axios.delete(route("api.users.destroy", id))
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: "Success",
                detail: this.$t("User deleted successfully"),
                life: 3000,
              });
              this.fetchUsers();
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
