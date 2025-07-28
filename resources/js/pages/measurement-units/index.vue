<template>
  <div>
    <div class="flex flex-row justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Measurement Units") }}
      </h2>
      <p-button
        v-can="'measurement-units-create'"
        :label="$t('Add Measurement Unit')"
        icon="fa fa-add"
        raised
        class="ml-2 uppercase"
        @click="addMeasurementUnit"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="measurementUnits"
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
            {{ $t('No measurement units found') }}
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
            field="abbreviation"
            :header="$t('Abbreviation')"
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
                  v-can="'measurement-units-edit'"
                  v-tooltip.top="$t('Edit')"
                  icon="fa fa-edit"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="editMeasurementUnit(row.data)"
                />
                <p-button
                  v-can="'measurement-units-delete'"
                  v-tooltip.top="$t('Delete')"
                  icon="fa fa-trash"
                  text
                  rounded
                  raised
                  size="sm"
                  @click="deleteMeasurementUnit(row.data.id)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
    <ItemEditor
      :measurement-unit="selectedMeasurementUnit"
      :show-dialog="editorToggle"
      @clearSelection="selectedMeasurementUnit = {}; editorToggle = false;"
      @submitted="saveMeasurementUnit"
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
      measurementUnits: [],
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
      selectedMeasurementUnit: {},
      editorToggle: false,
    };
  },
  watch: {
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetchMeasurementUnits();
      },
    },
  },
  mounted() {
    this.fetchMeasurementUnits();
  },
  methods: {
    fetchMeasurementUnits() {
      this.loading = true;

      const params = new URLSearchParams();

      params.append("per_page", this.pagination.perPage);
      params.append("page", this.pagination.page);
      params.append("order_by", this.pagination.sortField);
      params.append("order_direction", this.pagination.sortOrder === -1 ? "desc" : "asc");

      if (this.pagination.filter) {
        params.append("filter", this.pagination.filter);
      }

      const url = `${route("api.measurement-units")}?${params.toString()}`;

      axios.get(url)
        .then((response) => {
          this.measurementUnits = response.data.data.map((item) => ({
            ...item,
            created_at: window.moment(item.created_at).tz(window.timezone).format(window.datetimeFormat),
            updated_at: window.moment(item.updated_at).tz(window.timezone).format(window.datetimeFormat),
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
      this.fetchMeasurementUnits();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchMeasurementUnits();
    },
    addMeasurementUnit() {
      this.editorToggle = true;
      this.selectedMeasurementUnit = {};
    },
    editMeasurementUnit(measurementUnit) {
      this.editorToggle = true;
      this.selectedMeasurementUnit = measurementUnit;
    },
    deleteMeasurementUnit(id) {
      this.$confirm.require({
        message: this.$t("Are you sure you want to delete this measurement unit?"),
        header: this.$t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: this.$t("Cancel"),
        acceptLabel: this.$t("Delete"),
        rejectClass: "p-button-secondary",
        accept: () => {
          axios.delete(`${route("api.measurement-units.destroy", id)}`)
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: this.$t("Success"),
                detail: this.$t("Measurement Unit deleted successfully"),
                life: 3000,
              });
              this.fetchMeasurementUnits();
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
    saveMeasurementUnit(id, measureUnit) {
      if (id) {
        this.updateMeasurementUnit(id, measureUnit);
      } else {
        this.createMeasurementUnit(measureUnit);
      }
    },
    createMeasurementUnit(measureUnit) {
      axios.post(route("api.measurement-units.store"), measureUnit)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Measurement Unit created successfully"),
            life: 3000,
          });
          this.fetchMeasurementUnits();
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
    updateMeasurementUnit(id, measurementUnit) {
      axios.put(`${route("api.measurement-units.update", id)}`, measurementUnit)
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Measurement Unit updated successfully"),
            life: 3000,
          });
          this.fetchMeasurementUnits();
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
