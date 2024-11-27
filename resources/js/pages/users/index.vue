<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Users") }}
      </h2>
      <p-button
        :label="$t('Add User')"
        class="ml-2 uppercase"
        icon="fa fa-add"
        raised
        @click="$inertia.visit(route('users.create'))"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="users"
          resizable-columns
          lazy
          :total-records="pagination.total"
          :rows="pagination.rows"
          :first="pagination.first"
          :loading="loading"
          paginator
          sort-field="first_name"
          :sort-order="1"
          @page="onPage($event)"
          @sort="onSort($event)"
        >
          <template #empty>
            {{ $t('No users found') }}
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
              </div>
            </div>
          </template>
          <Column
            field="first_name"
            :header="$t('First Name')"
            sortable
          />
          <Column
            field="last_name"
            :header="$t('Last Name')"
            sortable
          />
          <Column
            field="username"
            :header="$t('Username')"
            sortable
          />
          <Column
            field="role.name"
            :header="$t('Role')"
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
                <Tag
                  v-else
                  severity="danger"
                >
                  {{ $t('Archived') }}
                </Tag>
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
                  v-tooltip.top="$t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editUser(row.data)"
                />
                <p-button
                  v-show="row.data.status === 'archived'"
                  v-tooltip.top="$t('Restore')"
                  icon="fa fa-trash-arrow-up"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="restoreUser(row.data)"
                />
                <p-button
                  v-show="row.data.status !== 'archived'"
                  v-tooltip.top="$t('Delete')"
                  :disabled="isCurrentUser(row.data.id)"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
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
    PButton,
    InputText,
    ConfirmDialog,
    Card,
    IconField,
    InputIcon,
    SelectButton,
    Tag,
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
    isCurrentUser(id) {
      return user.id === id;
    },
    editUser(id) {
      this.$inertia.visit(route("users.edit", id));
    },
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
        rejectClass: "p-button-secondary",
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
        rejectClass: "p-button-secondary",
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
