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
            text-sm
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
              <th class="text-left p-4">
                {{ $t('Product') }}
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
              <th class="text-left px-6 py-3">
                {{ $t('Actions') }}
              </th>
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
              <td class="w-4 p-4">
                {{ item.product }}
              </td>
              <td class="px-6 py-4">
                {{ item.quantity }}
              </td>
              <td class="px-6 py-4">
                {{ item.unit_price }}
              </td>
              <td class="px-6 py-4">
                {{ item.subtotal }}
              </td>
              <td class="px-6 py-4">
                <button @click="removeItem(index)">
                  {{ $t('Remove') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-else>
      <h1>No items</h1>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    items: {
      type: Array,
      default: () => [],
    },
  },
  mounted() {
    console.log("OrderGrid.vue mounted");
  },
};
</script>
