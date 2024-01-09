<template>
  <!-- Modal -->
  <div
    id="exampleModal"
    class="modal fade"
    tabindex="-1"
    aria-labelledby="exampleModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1
            id="exampleModalLabel"
            class="modal-title fs-5"
          >
            {{ product?.name }}
          </h1>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          />
        </div>
        <div class="modal-body">
          <div
            class="row"
          >
            <div
              class="col-md-6"
            >
              <img
                :src="product?.image"
                :alt="product?.name"
                class="img-fluid"
              >
            </div>
            <div
              class="col-md-6"
            >
              <p>
                <strong>
                  # Serie:
                </strong>
                {{ product?.identifier }}
              </p>
              <p>
                <strong>
                  Description:
                </strong>
                {{ product?.description }}
              </p>
              <p>
                <strong>
                  Price:
                </strong>
                {{ product?.price }}
              </p>
              <p>
                <strong>
                  Brand:
                </strong>
                {{ product?.brand }}
              </p>
              <p>
                <strong>
                  Stock:
                </strong>
                {{ product?.stock }}
              </p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
          >
            Close
          </button>
          <button
            type="button"
            class="btn btn-primary"
          >
            Save changes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    product: {
      type: Object,
      required: true,
    },
  },
};
</script>
