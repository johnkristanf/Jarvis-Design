<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Models\Materials;
use App\Models\MaterialsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'category_id' => $data['category'],
        ]);

        return response()->json([
            'msg' => 'Material Created Successfully',
            'material_id' => $createdMaterialID
        ]);
    }

    public function getMaterialCategory()
    {
        $categories = MaterialsCategory::select('id', 'name')->get();
        return response()->json($categories);
    }


    public function get()
    {
        $materials = Materials::with([
            'category' => function ($query) {
                $query->select('id', 'name');
            }
        ])
            ->get();

        return response()->json($materials);
    }

    public function getGroupedMaterials()
    {
        $materials = Materials::with('category') // Assuming each Material belongs to a Category
        ->get()
        ->groupBy(function ($item) {
            return $item->category->name; // Group by category name
        });

        return response()->json($materials);
    }
}
