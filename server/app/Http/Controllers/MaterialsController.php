<?php

namespace App\Http\Controllers;

use App\Http\Requests\EditMaterialRequest;
use App\Http\Requests\StoreMaterialRequest;
use App\Models\Materials;
use App\Models\MaterialsCategory;
use Illuminate\Http\Request;

class MaterialsController extends Controller
{
    public function store(StoreMaterialRequest $request)
    {
        $data = $request->validated();

        $createdMaterialID = Materials::create([
            'name' => $data['material_name'],
            'unit' => $data['unit'],
            'quantity' => $data['quantity'],
            'reorder_level' => $data['reorder_level'],
            'category_id' => 1, // DUMMY DATA CAUSE THE CATEGORIZATION HAS BEEN REMOVED DUE TO PANEL REQUEST
        ]);

        return response()->json([
            'msg' => 'Material Created Successfully',
            'material_id' => $createdMaterialID,
        ]);
    }

    public function getMaterialCategory()
    {
        $categories = MaterialsCategory::select('id', 'name')->get();

        return response()->json($categories);
    }

    public function get(Request $request)
    {
        $limit = $request->get('limit', 10);
        $materials = Materials::with([
            'category' => function ($query) {
                $query->select('id', 'name');
            },
        ])
            ->orderByDesc('created_at') // <-- Latest first
            ->paginate($limit);

        return response()->json($materials);
    }

    public function getGroupedMaterials()
    {
        $materials = Materials::with('category') // Assuming each Material belongs to a Category
            ->get()
            ->groupBy(function ($item) {
                return $item->category->name; // Group by category name
            });

        // IT WILL RETURN AN OBJECT THAT HAS KEY CATEGORY NAME AND VALUE IS AN ARRAY MATERIALS
        return response()->json($materials);
    }

    public function edit(EditMaterialRequest $request)
    {
        $data = $request->validated();

        // Find the existing material by ID and update it
        $material = Materials::findOrFail($data['id']);

        $material->update([
            'name' => $data['material_name'],
            'unit' => $data['unit'],
            'quantity' => $data['quantity'],
            'reorder_level' => $data['reorder_level'],
            'category_id' => $data['category'],
        ]);

        return response()->json([
            'msg' => 'Material Updated Successfully',
            'material_id' => $material->id,
        ]);
    }

    public function destroy($id)
    {
        $product = Materials::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Fabric deleted successfully.',
            'status' => true,
        ], 200);
    }
}
