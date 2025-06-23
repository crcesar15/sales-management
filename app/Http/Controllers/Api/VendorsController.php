<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\Variants as VariantsResource;
use App\Models\ProductVariant;
use App\Models\Vendor;
use App\Services\VariantService;
use Illuminate\Http\Request;

class VendorsController extends Controller
{
    //Get all vendors
    public function index(Request $request)
    {
        $query = Vendor::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter) {
                    $query->where('fullname', 'like', $filter);
                }
            );
        }

        $status = $request->input('status', 'all');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $order_by = $request->has('order_by')
            ? $order_by = $request->get('order_by')
            : 'name';
        $order_direction = $request->has('order_direction')
            ? $request->get('order_direction')
            : 'ASC';

        $response = $query->orderBy(
            $request->input('order_by', $order_by),
            $request->input('order_direction', $order_direction)
        )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    //Get a vendor by id
    public function show($id)
    {
        $vendor = Vendor::find($id);
        if ($vendor) {
            return response()->json(['data' => $vendor], 200);
        } else {
            return response()->json(['message' => 'Vendor not found'], 404);
        }
    }

    //Create a new vendor
    public function store(Request $request)
    {
        $vendor = Vendor::create($request->all());

        return response()->json(['data' => $vendor], 201);
    }

    //Update a vendor
    public function update(Request $request, $id)
    {
        $vendor = Vendor::find($id);
        if ($vendor) {
            $vendor->update($request->all());

            return response()->json(['data' => $vendor], 200);
        } else {
            return response()->json(['message' => 'Vendor not found'], 404);
        }
    }

    //Delete a vendor
    public function destroy($id)
    {
        $vendor = Vendor::find($id);

        if ($vendor) {
            $vendor->delete();

            return response()->json(['data' => $vendor], 200);
        } else {
            return response()->json(['message' => 'Role not found'], 404);
        }
    }

    public function getProductVariants(Request $request, VariantService $variantService, Vendor $vendor)
    {
        $includes = $request->input('includes', '');
        $includes = explode(',', $includes);

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);

        $order_by = $request->input('order_by', 'product_name');
        $order_direction = $request->input('order_direction', 'ASC');

        $filter = $request->input('filter', '');
        $filterBy = $request->input('filter_by', 'name');
        $status = $request->input('status', 'all');

        $vendorId = $vendor->id;

        $config = [
            'includes' => $includes,
            'order_by' => $order_by,
            'order_direction' => $order_direction,
            'filter' => $filter,
            'filter_by' => $filterBy,
            'status' => $status,
            'page' => $page,
            'per_page' => $per_page,
            // 'vendor_id' => $vendorId,
        ];

        // Fetch variants using the service
        $response = $variantService->getVariants($config);

        return new VariantsResource($response);
    }

    public function updateProductVariants(Request $request, Vendor $vendor)
    {
        $products = $request->input('variants');

        $formattedProducts = [];

        foreach ($products as $product) {
            $formattedProducts[$product['id']] = [
                'price' => $product['price'],
                'details' => $product['details'] ?? null,
                'payment_terms' => $product['payment_terms'],
                'status' => $product['status'] ?? 'active',
            ];
        }

        if ($vendor && $product) {
            $vendor->variants()->syncWithoutDetaching($formattedProducts);

            return response()->json(['data' => $vendor], 200);
        } else {
            return 2;

            return response()->json(['message' => 'Vendor or Product not found'], 404);
        }
    }

    public function removeProductVariant(Vendor $vendor, ProductVariant $variant)
    {
        if ($vendor && $variant) {
            $vendor->variants()->detach($variant);

            return response()->json(['data' => $vendor], 200);
        } else {
            return response()->json(['message' => 'Vendor or Product not found'], 404);
        }
    }
}
