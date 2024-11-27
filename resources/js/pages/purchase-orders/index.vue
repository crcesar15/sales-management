<template>
  <div>
    <div class="flex justify-between mb-3">
      <h2 class="text-2xl font-bold flex items-end m-0">
        {{ $t("Purchase Orders") }}
      </h2>
      <p-button
        :label="$t('Purchase Order')"
        style="text-transform: uppercase"
        icon="fa fa-add"
        raised
        class="ml-2"
        @click="addPurchaseOrder"
      />
    </div>
    <ConfirmDialog />
    <Card>
      <template #content>
        <DataTable
          :value="purchaseOrders"
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
            {{ $t('No purchase orders found') }}
          </template>
          <template #header>
            <div class="grid grid-cols-12">
              <div
                class="
                  flex
                  md:justify-start
                  justify-center
                  xl:col-span-3
                  lg:col-span-4
                  md:col-span-6
                  col-span-12
                "
              >
                <SelectButton
                  v-model="status"
                  :allow-empty="false"
                  :options="[{
                    label: $t('All'),
                    value: 'all',
                  }, {
                    label: $t('Pending'),
                    value: 'pending',
                  }, {
                    label: $t('Draft'),
                    value: 'draft',
                  }, {
                    label: $t('Paid'),
                    value: 'paid',
                  }, {
                    label: $t('Canceled'),
                    value: 'canceled',
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
            field="vendor.fullname"
            :header="$t('Vendor')"
          />
          <Column
            field="user.full_name"
            :header="$t('User')"
          />
          <Column
            field="total"
            :header="$t('Total')"
          />
          <Column
            field="status"
            :header="$t('Status')"
            header-class="flex justify-center"
            class="flex justify-center"
          >
            <template #body="{ data }">
              <div
                style="height: 55px;"
                class="flex items-center"
              >
                <Tag
                  v-if="data.status === 'draft'"
                  severity="info"
                  :value="$t('Draft')"
                />
                <Tag
                  v-else-if="data.status === 'pending'"
                  severity="warn"
                  :value="$t('Pending')"
                />
                <Tag
                  v-else-if="data.status === 'paid'"
                  severity="warn"
                  :value="$t('Paid')"
                />
                <Tag
                  v-else
                  severity="canceled"
                  :value="$t('Canceled')"
                />
              </div>
            </template>
          </Column>
          <Column
            field="created_at"
            :header="$t('Created At')"
            sortable
          />
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script>
import PButton from "primevue/button";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import InputIcon from "primevue/inputicon";
import IconField from "primevue/iconfield";
import Card from "primevue/card";
import Tag from "primevue/tag";
import SelectButton from "primevue/selectbutton";
import AppLayout from "../../layouts/admin.vue";

export default {
  components: {
    PButton,
    Card,
    ConfirmDialog,
    DataTable,
    Column,
    InputText,
    IconField,
    InputIcon,
    Tag,
    SelectButton,
  },
  layout: AppLayout,
  data() {
    return {
      purchaseOrders: [],
      pagination: {
        total: 0,
        first: 0,
        rows: 10,
        page: 1,
        sortField: "created_at",
        sortOrder: 1,
        filter: "",
      },
      loading: true,
      status: "pending",
    };
  },
  watch: {
    status() {
      this.pagination.page = 1;
      this.fetch();
    },
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetch();
      },
    },
  },
  mounted() {
    this.fetch();
  },
  methods: {
    onPage(event) {
      this.pagination.page = event.page + 1;
      this.pagination.per_page = event.rows;
      this.fetch();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetch();
    },
    fetch() {
      this.loading = true;

      const params = {
        per_page: this.pagination.rows,
        page: this.pagination.page,
        order_by: this.pagination.sortField,
        order_direction: this.pagination.sortOrder === 1 ? "asc" : "desc",
        include: "vendor,user",
        status: this.status,
      };

      if (this.pagination.filter) {
        params.filter = this.pagination.filter;
      }

      axios
        .get(route("api.purchase-orders"), { params })
        .then((response) => {
          this.purchaseOrders = response.data.data;
          this.pagination.total = response.data.total;
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: this.$t("Error"),
            detail: error.response.data.message,
            life: 3000,
          });
        })
        .finally(() => {
          this.loading = false;
        });
    },
    addPurchaseOrder() {
      this.$inertia.visit(route("purchase-orders.create"));
    },
  },
};
</script>
