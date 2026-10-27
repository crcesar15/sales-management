export interface ProductGalleryOption {
  id: number;
  name: string;
}

export interface ProductGalleryVariant {
  id: number;
  product_name: string;
  brand_name: string | null;
  option_values: string[];
  price: number;
  unit_name: string | null;
  is_available: boolean;
  thumbnail_url: string | null;
}

export interface ProductGalleryImage {
  id: number;
  thumb_url: string;
  full_url: string;
}

export interface ProductGalleryVariantDetail {
  id: number;
  product_name: string;
  description: string | null;
  brand_name: string | null;
  identifier: string | null;
  barcode: string | null;
  option_values: Array<{ option_name: string | null; value: string }>;
  categories: ProductGalleryOption[];
  price: number;
  unit_name: string | null;
  is_available: boolean;
  images: ProductGalleryImage[];
  sale_units: Array<{
    id: number;
    name: string;
    conversion_factor: number;
    price: number;
  }>;
}

export interface ProductGalleryCollection {
  data: ProductGalleryVariant[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface ProductGalleryFilters {
  filter: string;
  brand_id: number | null;
  category_id: number | null;
  available: boolean;
}

export interface ProductGalleryFilterOptions {
  brands: ProductGalleryOption[];
  categories: ProductGalleryOption[];
}
