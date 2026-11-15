export interface PricingEvaluation {
  cost: number | null;
  price: number;
  current_margin: number | null;
  suggested_price: number | null;
  below_cost: boolean;
  below_target: boolean;
}

export interface PricingSaleUnit extends PricingEvaluation {
  id: number;
  name: string;
  conversion_factor?: number;
}

export interface PricingOffer {
  id: number;
  vendor_id: number;
  supplier_name: string | null;
  unit_name: string;
  cost: number;
  price: number;
  conversion_factor: number;
  eligible: boolean;
  is_outdated: boolean;
}

export interface ReplacementPricing {
  target_type: "percent" | "amount";
  target_value: number | null;
  change_amount: number | null;
  change_percent: number | null;
  reference_id: number | null;
  catalog_id: number | null;
  replacement_cost: number | null;
  legacy_cost: number | null;
  supplier_name: string | null;
  source: string | null;
  effective_at: string | null;
  outdated: boolean;
  previous_cost: number | null;
  pending_review: boolean;
  reasons: string[];
  evaluation: PricingEvaluation;
  sale_units: PricingSaleUnit[];
  base_unit: string;
  offers: PricingOffer[];
  history: Array<{
    id: number;
    cost: number;
    supplier_name: string | null;
    source: string;
    purchase_order_id: number | null;
    date: string | null;
    invalidated: boolean;
  }>;
}

export interface ActualStockCosts {
  average: number | null;
  known_quantity: number;
  total_quantity: number;
  complete: boolean;
  latest_received_cost: number | null;
  latest_received_at: string | null;
  receipts: Array<{ reception_order_id: number; date: string; cost: number | null }>;
}

export interface PricingPreviewItem {
  product_variant_id: number;
  unit_id?: number | null;
  price: number;
  margin_type?: "percent" | "amount";
  margin_value?: number | null;
  selling_price?: number;
  sale_factor?: number;
}

export interface PricingPreview extends PricingEvaluation {
  index: number;
  product_variant_id: number;
  product_name: string | null;
  previous_cost: number | null;
  reference_id: number | null;
  sale_units: PricingSaleUnit[];
}
