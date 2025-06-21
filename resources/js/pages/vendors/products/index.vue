<template>
  <div>
    <div class="flex justify-between mb-3">
      <div class="flex">
        <PButton
          icon="fa fa-arrow-left"
          text
          severity="secondary"
          class="hover:shadow-md mr-2 uppercase"
          @click="$inertia.visit(route('vendors'))"
        />
        <h4 class="text-2xl font-bold flex items-center m-0">
          {{ $t("Product Catalog") }} - {{ vendor.fullname }}
        </h4>
      </div>
      <div class="flex flex-col justify-center">
        <PButton
          :label="$t('Add Product')"
          icon="fa fa-plus"
          class="uppercase"
          raised
          @click="addProduct()"
        />
      </div>
    </div>
    <ConfirmDialog />
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
              @page="onPage($event)"
              @sort="onSort($event)"
            >
              <template #empty>
                {{ $t('No products found') }}
              </template>
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
                :header="$t('Price')"
              />
              <Column
                field="payment_terms"
                :header="$t('Payment Terms')"
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
                :header="$t('Details')"
              />
              <Column
                :header="$t('Actions')"
                :pt="{columnHeaderContent: 'justify-center'}"
              >
                <template #body="{ data }">
                  <span class="flex justify-center gap-2">
                    <p-button
                      v-tooltip.top="$t('Edit')"
                      icon="fa fa-edit"
                      text
                      rounded
                      raised
                      size="sm"
                      @click="editProduct(data.id)"
                    />
                    <p-button
                      v-tooltip.top="$t('Delete')"
                      icon="fa fa-trash"
                      text
                      rounded
                      raised
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
    <ProductEditor
      :product="selectedProduct"
      :show="showProductEditor"
      @save="saveProduct"
      @close="closeProductEditor"
    />
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
import ConfirmDialog from "primevue/confirmdialog";
import AppLayout from "../../../layouts/admin.vue";
import ProductEditor from "./editor.vue";

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
    ProductEditor,
    ConfirmDialog,
  },
  layout: AppLayout,
  props: {
    vendor: {
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
      selectedProduct: {},
      showProductEditor: false,
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
        includes: "products",
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
        .get(route("api.vendors.variants", this.vendor.id), { params })
        .then((response) => {
          this.products = response.data.data.map((product) => {
            const relatedVendor = product.vendors.find((vendor) => vendor.id === this.vendor.id);

            return {
              id: product.id,
              name: `${product.name} - ${product.product.name}`,
              status: relatedVendor.pivot.status,
              price: relatedVendor.pivot.price,
              payment_terms: relatedVendor.pivot.payment_terms,
              details: relatedVendor.pivot.details,
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
    editProduct(id) {
      this.selectedProduct = this.products.find((product) => product.id === id);
      this.showProductEditor = true;
    },
    addProduct() {
      this.selectedProduct = {};
      this.showProductEditor = true;
    },
    saveProduct(product) {
      axios
        .put(route("api.vendors.variants.update", { vendor: this.vendor.id }), { variants: [product] })
        .then(() => {
          this.$toast.add({
            severity: "success",
            summary: this.$t("Success"),
            detail: this.$t("Product has been saved successfully"),
            life: 3000,
          });
          this.closeProductEditor();
          this.fetchProducts();
        });
    },
    deleteProduct(id) {
      // confirm
      this.$confirm.require({
        message: this.$t("Are you sure you want to delete this product for this vendor?"),
        header: this.$t("Confirm"),
        icon: "fas fa-exclamation-triangle",
        rejectLabel: this.$t("Cancel"),
        acceptLabel: this.$t("Delete"),
        rejectClass: "p-button-secondary",
        accept: () => {
          axios
            .delete(route("api.vendors.variants.delete", { vendor: this.vendor.id, variant: id }))
            .then(() => {
              this.$toast.add({
                severity: "success",
                summary: this.$t("Success"),
                detail: this.$t("Product has been deleted successfully"),
                life: 3000,
              });
              this.fetchProducts();
            });
        },
      });
    },
    closeProductEditor() {
      this.showProductEditor = false;
      this.selectedProduct = {};
    },
  },
};
</script>
