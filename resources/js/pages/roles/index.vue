<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Roles") }}
      </h2>
      <p-button
        v-can="'roles-create'"
        :label="$t('add role')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="$inertia.visit(route('roles.create'))"
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
            field="name"
            :header="$t('Name')"
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
                  v-tooltip.top="$t('Edit')"
                  v-can="'roles-edit'"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="$inertia.visit(route('roles.edit', row.data.id))"
                />
                <p-button
                  v-tooltip.top="$t('Delete')"
                  v-can="'roles-delete'"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="deleteRole(row.data.id)"
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
        perPage: 10,
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

      const params = new URLSearchParams();

      params.append("per_page", this.pagination.perPage);
      params.append("page", this.pagination.page);
      params.append("order_by", this.pagination.sortField);
      params.append("order_direction", this.pagination.sortOrder === -1 ? "desc" : "asc");

      if (this.pagination.filter) {
        params.append("filter", this.pagination.filter);
      }

      const url = `${route("api.roles")}?${params.toString()}`;

      axios.get(url)
        .then((response) => {
          this.roles = response.data.data.map((item) => ({
            ...item,
            created_at: moment(item.created_at).tz(window.timezone).format(window.datetimeFormat),
            updated_at: moment(item.updated_at).tz(window.timezone).format(window.datetimeFormat),
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
      this.fetchRoles();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchRoles();
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
