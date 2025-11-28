<?php

namespace App\Http\Controllers;

use App\Http\Requests\EditMaterialRequest;
use App\Http\Requests\StoreMaterialRequest;
use App\Models\FabricAdjustLogs;
use App\Models\Materials;
use App\Models\MaterialsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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


    public function addFabricQuantity(Request $request, $fabricId)
    {
        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'quantity' => 'required|integer|min:1',
        ]);

        $fabric = Materials::find($fabricId);

        if (!$fabric) {
            return response()->json(['msg' => 'Fabric not found'], 404);
        }

        Log::info("VALIDATED: ", [$validated]);
        Log::info("FABRIC: ", [$fabric]);

        $fabric->quantity += $validated['quantity'];
        $fabric->save();

        // Log the fabric adjustment
        FabricAdjustLogs::create([
            'material_id' => $fabric->id,
            'quantity' => $validated['quantity'],
            'action' => FabricAdjustLogs::ADDED,
            'reason' => $request->input('reason', null),
            'delivery_date' => $validated['delivery_date']
        ]);

        return response()->json([
            'msg' => 'Fabric quantity added successfully',
        ]);
    }

    public function reduceFabricQuantity(Request $request, $fabricId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $fabric = Materials::find($fabricId);

        if (!$fabric) {
            return response()->json(['msg' => 'Fabric not found'], 404);
        }

        Log::info("VALIDATED: ", [$validated]);
        Log::info("FABRIC: ", [$fabric]);

        if ($fabric->quantity < $validated['quantity']) {
            return response()->json(['msg' => 'Insufficient fabric quantity'], 400);
        }

        $fabric->quantity -= $validated['quantity'];
        $fabric->save();

        // Log the fabric adjustment
        FabricAdjustLogs::create([
            'material_id' => $fabric->id,
            'quantity' => $validated['quantity'],
            'action' => FabricAdjustLogs::REDUCED,
            'reason' => $request->input('reason', null),
        ]);

        return response()->json([
            'msg' => 'Fabric quantity reduced successfully',
        ]);
    }
    public function getFabricAdjustLogs(Request $request)
    {
        $fabricId = $request->query('fabric_id');

        $logs = FabricAdjustLogs::with([
                'material' => function ($query) {
                    $query->select('id', 'name');
                }
            ])
            ->where('material_id', $fabricId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($logs);
    }

    public function getMaterialCategory()
    {
        $categories = MaterialsCategory::select('id', 'name')->get();

        return response()->json($categories);
    }

    public function get(Request $request)
    {
        $limit = $request->get('limit', 5);
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
            'category_id' => 1, // DUMMY DATA CAUSE THE CATEGORIZATION HAS BEEN REMOVED DUE TO PANEL REQUEST

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
