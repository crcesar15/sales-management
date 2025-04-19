<template>
  <div>
    <div v-if="items.length > 0">
      <div
        class="
          relative
          overflow-x-auto
          sm:rounded-lg
        "
      >
        <table
          class="
            w-full
            text-left
            rtl:text-right
            border
            border-gray-300
            dark:border-gray-600
            text-gray-500
            dark:text-gray-400
          "
        >
          <thead
            class="
              text-xs
              uppercase
              border
              border-gray-300
              dark:border-gray-600
              bg-gray-50
              dark:bg-gray-950
              text-gray-700
              dark:text-gray-400
            "
          >
            <tr>
              <th class="text-left px-6 py-3">
                {{ $t('Product') }}
              </th>
              <th class="text-left px-6 py-3">
                {{ $t('Stock') }}
              </th>
              <th class="text-left px-6 py-3">
                {{ $t('Quantity') }}
              </th>
              <th class="text-left px-6 py-3">
                {{ $t('Price') }}
              </th>
              <th class="text-left px-6 py-3">
                {{ $t('Total') }}
              </th>
              <th class="text-left px-6 py-3" />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, index) in items"
              :key="index"
              class="
                bg-white border-b
                dark:bg-gray-950
                dark:border-gray-600
                hover:bg-gray-50
                dark:hover:bg-gray-600
              "
            >
              <td class="px-6 py-4">
                {{ item.product.name }}
              </td>
              <td class="px-6 py-4">
                {{ item.product.stock }}
              </td>
              <td class="px-6 py-4">
                <InputNumber
                  v-model="item.quantity"
                  :min="1"
                  :increment="1"
                  :decrement="1"
                  show-buttons
                  button-layout="horizontal"
                  :pt="{
                    pcInputText: { root: { class: 'w-[80px] text-left' } },
                  }"
                  @input="refocus($event)"
                >
                  <template #decrementicon>
                    <i class="fa fa-minus" />
                  </template>
                  <template #incrementicon>
                    <i class="fa fa-plus" />
                  </template>
                </InputNumber>
              </td>
              <td class="px-6 py-4">
                BOB. {{ item.unit_price }}
              </td>
              <td class="px-6 py-4">
                BOB. {{ getSubtotal(item).toFixed(2) }}
              </td>
              <td class="px-6 py-4">
                <button @click="removeItem(index)">
                  <i class="fa fa-trash" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div
      v-else
      class="
        flex
        justify-center
        items-center
        h-16
        border
        border-gray-300
        dark:border-gray-600
        rounded
      "
    >
      <h6 class="m-0 text-gray-500 dark:text-gray-400">
        Add Some Products
      </h6>
    </div>
  </div>
</template>

<script>
import { InputNumber } from "primevue";

export default {
  components: {
    InputNumber,
  },
  props: {
    modelValue: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      items: this.modelValue,
    };
  },
  watch: {
    items: {
      handler(value) {
        this.$emit("update:modelValue", value);
      },
      deep: true,
    },
  },
  methods: {
    getSubtotal(item) {
      return item.quantity * item.unit_price;
    },
    removeItem(index) {
      this.items.splice(index, 1);
    },
    refocus($event) {
      const { target } = $event.originalEvent;
      target.blur();
      target.focus();
    },
  },
};

</script>
