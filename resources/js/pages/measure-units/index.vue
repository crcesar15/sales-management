<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Measure Units") }}
      </h2>
      <p-button
        :label="$t('Add Measure Unit')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="addMeasureUnit"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="measureUnits"
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
            {{ $t('No measure units found') }}
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
            field="description"
            :header="$t('Description')"
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
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editMeasureUnit(row.data)"
                />
                <p-button
                  v-tooltip.top="$t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="deleteMeasureUnit(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <ItemEditor
      :measure-unit="selectedMeasureUnit"
      :show-dialog="editorToggle"
      @clearSelection="selectedMeasureUnit = {}; editorToggle = false;"
      @submitted="saveMeasureUnit"
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
  },
  layout: AppLayout,
  data() {
    return {
      measureUnits: [],
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
      selectedMeasureUnit: {},
      editorToggle: false,
    };
  },
  watch: {
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetchMeasureUnits();
      },
    },
  },
  mounted() {
    this.fetchMeasureUnits();
  },
  methods: {
    fetchMeasureUnits() {
      this.loading = true;

      let url = `${route("api.measure-units")}?
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
          this.measureUnits = response.data.data;
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
      this.fetchMeasureUnits();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchMeasureUnits();
    },
    addMeasureUnit() {
      this.editorToggle = true;
      this.selectedMeasureUnit = {};
    },
    editMeasureUnit(measureUnit) {
      this.editorToggle = true;
      this.selectedMeasureUnit = measureUnit;
    },
    deleteMeasureUnit(id) {
      this.$confirm.require({
        message: this.$t("Are you sure you want to delete this measure unit?"),
        header: this.$t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: this.$t("Cancel"),
        acceptLabel: this.$t("Delete"),
        rejectClass: "p-button-secondary",
        accept: () => {
          axios.delete(`${route("api.measure-units.destroy", id)}`)
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: this.$t("Success"),
                detail: this.$t("Measure Unit deleted successfully"),
                life: 3000,
              });
              this.fetchMeasureUnits();
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
    saveMeasureUnit(id, measureUnit) {
      if (id) {
        this.updateMeasureUnit(id, measureUnit);
      } else {
        this.createMeasureUnit(measureUnit);
      }
    },
    createMeasureUnit(measureUnit) {
      axios.post(route("api.measure-units.store"), measureUnit)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Measure Unit created successfully"),
            life: 3000,
          });
          this.fetchMeasureUnits();
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
    updateMeasureUnit(id, measureUnit) {
      axios.put(`${route("api.measure-units.update", id)}`, measureUnit)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Measure Unit updated successfully"),
            life: 3000,
          });
          this.fetchMeasureUnits();
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
