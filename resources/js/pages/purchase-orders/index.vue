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
          table-class="border border-surface"
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
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script>
import PButton from "primevue/button";
import AppLayout from "../../layouts/admin.vue";

export default {
  components: {
    PButton,
  },
  layout: AppLayout,
  data() {
    return {
      purchaseOrders: [],
      pagination: {
        total: 0,
        rows: 10,
        first: 0,
        filter: "",
      },
      loading: false,
    };
  },
  mounted() {
    console.log("Component mounted.");
  },
  methods: {
    fetch() {
      this.loading = true;
      axios
        .get(route("api.purchase-orders"), {
          params: {
            page: this.pagination.first / this.pagination.rows + 1,
            rows: this.pagination.rows,
            filter: this.pagination.filter,
          },
        })
        .then((response) => {
          this.purchaseOrders = response.data.data;
          this.pagination.total = response.data.total;
        })
        .catch((error) => {
          console.error(error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
};
</script>
