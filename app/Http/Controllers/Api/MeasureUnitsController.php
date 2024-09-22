<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\MeasureUnit;
use Illuminate\Http\Request;

class MeasureUnitsController extends Controller
{
    //Get all the measure units
    public function index(Request $request)
    {
        $query = MeasureUnit::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter) {
                    $query->where('name', 'like', $filter);
                }
            );
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

    //Get a measure unit by id
    public function show($id)
    {
        $measureUnit = MeasureUnit::find($id);
        if ($measureUnit) {
            return response()->json(['data' => $measureUnit], 200);
        } else {
            return response()->json(['message' => 'Measure unit not found'], 404);
        }
    }

    //Create a new measure unit
    public function store(Request $request)
    {
        $measureUnit = MeasureUnit::create($request->all());

        return response()->json(['data' => $measureUnit], 201);
    }

    //Update a measure unit
    public function update(Request $request, $id)
    {
        $measureUnit = MeasureUnit::find($id);
        if ($measureUnit) {
            $measureUnit->update($request->all());

            return response()->json(['data' => $measureUnit], 200);
        } else {
            return response()->json(['message' => 'Measure unit not found'], 404);
        }
    }

    //Delete a measure unit
    public function destroy($id)
    {
        $measureUnit = MeasureUnit::find($id);
        if ($measureUnit) {
            //remove the measure unit from all products
            $measureUnit->products()->update(['measure_unit_id' => null]);

            $measureUnit->delete();
        } else {
            return response()->json(['message' => 'Measure unit not found'], 404);
        }
    }
}
