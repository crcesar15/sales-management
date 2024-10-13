<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          @click="$inertia.visit(route('suppliers'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ supplier.fullname }} - {{ $t("Product Catalog") }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <PButton
          icon="fa fa-save"
          :label="$t('Save')"
          style="text-transform: uppercase"
          @click="submit()"
        />
      </div>
    </div>
    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-12">
        <Card class="mb-4">
          <template #content>
            <DataTable
              :value="products"
              resizable-columns
              lazy
              :total-records="pagination.total"
              :rows="pagination.rows"
              :first="pagination.first"
              :loading="loading"
              paginator
              sort-field="name"
              :sort-order="1"
              table-class="border-surface border"
              @page="onPage($event)"
              @sort="onSort($event)"
            >
              <template #header>
                <div class="grid grid-cols-12">
                  <div class="xl:col-span-3 lg:col-span-4 md:col-span-6 col-span-12 flex md:justify-start justify-center">
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
                field="name"
                header="Name"
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
                      v-if="data.status === 'active'"
                      severity="success"
                      :value="$t('Active')"
                    />
                    <Tag
                      v-else-if="data.status === 'inactive'"
                      severity="warn"
                      :value="$t('Inactive')"
                    />
                    <Tag
                      v-else
                      severity="danger"
                      :value="$t('Archived')"
                    />
                  </div>
                </template>
              </Column>
              <Column
                field="price"
                header="Price"
              />
              <Column
                field="payment_terms"
                header="Payment Terms"
              >
                <template #body="slotProps">
                  <span v-if="slotProps.data.payment_terms === 'debit'">
                    {{ $t('Cash') }}
                  </span>
                  <span v-else-if="slotProps.data.payment_terms === 'credit'">
                    {{ $t('Credit') }}
                  </span>
                  <span v-else>
                    {{ $t('Both') }}
                  </span>
                </template>
              </Column>
              <Column
                field="details"
                header="Details"
              />
              <Column
                header="Actions"
                :pt="{columnHeaderContent: 'justify-center'}"
              >
                <template #body="{ data }">
                  <span class="p-buttonset flex justify-center">
                    <p-button
                      v-tooltip.top="$t('Edit')"
                      icon="fa fa-edit"
                      text
                      size="sm"
                      @click="editProduct(data.id)"
                    />
                    <p-button
                      v-tooltip.top="$t('Delete')"
                      icon="fa fa-trash"
                      text
                      size="sm"
                      class="btn-danger"
                      @click="deleteProduct(data.id)"
                    />
                  </span>
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script>
import Card from "primevue/card";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import PButton from "primevue/button";
import Tag from "primevue/tag";
import SelectButton from "primevue/selectbutton";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import AppLayout from "../../../layouts/admin.vue";
import i18n from "../../../app";

export default {
  components: {
    Card,
    DataTable,
    Column,
    Tag,
    SelectButton,
    IconField,
    InputIcon,
    InputText,
    PButton,
  },
  layout: AppLayout,
  props: {
    supplier: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      products: [],
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
      status: "all",
    };
  },
  watch: {
    "pagination.filter": {
      handler() {
        this.pagination.page = 1;
        this.fetchProducts();
      },
    },
    status() {
      this.pagination.page = 1;
      this.fetchProducts();
    },
  },
  mounted() {
    this.fetchProducts();
  },
  methods: {
    fetchProducts() {
      this.loading = true;

      const params = {
        per_page: this.pagination.rows,
        page: this.pagination.page,
        sortField: this.pagination.sortField,
        status: this.status,
      };

      if (this.pagination.filter) {
        params.filter = this.pagination.filter;
      }

      if (this.pagination.sortOrder === -1) {
        params.order_direction = "desc";
      } else {
        params.order_direction = "asc";
      }

      axios
        .get(route("api.suppliers.products", this.supplier.id), { params })
        .then((response) => {
          this.products = response.data.data.map((product) => {
            const relatedSupplier = product.suppliers.find((supplier) => supplier.id === this.supplier.id);

            return {
              name: `${product.name} - ${product.product.name}`,
              status: product.status,
              price: relatedSupplier.pivot.price,
              payment_terms: relatedSupplier.pivot.payment_terms,
              details: relatedSupplier.pivot.details,
            };
          });

          this.pagination.total = response.data.meta.total;
          this.loading = false;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    onPage(event) {
      this.pagination.page = event.page + 1;
      this.pagination.per_page = event.rows;
      this.fetchProducts();
    },
    onSort(event) {
      this.pagination.sortField = event.sortField;
      this.pagination.sortOrder = event.sortOrder;
      this.fetchProducts();
    },
  },
};
</script>
