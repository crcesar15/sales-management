import { readonly, shallowRef, watch } from "vue";

export type PosProductView = "grid" | "list";

const PRODUCT_VIEW_KEY = "pos-product-view";

function readStoredView(): PosProductView {
  try {
    return localStorage.getItem(PRODUCT_VIEW_KEY) === "list" ? "list" : "grid";
  } catch {
    return "grid";
  }
}

const productView = shallowRef<PosProductView>(readStoredView());

watch(productView, (view) => {
  try {
    localStorage.setItem(PRODUCT_VIEW_KEY, view);
  } catch {
    return;
  }
});

export function usePosProductView() {
  function setProductView(view: PosProductView): void {
    productView.value = view;
  }

  return {
    productView: readonly(productView),
    setProductView,
  };
}