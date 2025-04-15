<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesignsMaterialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        // EDITING THE DESIGN CREATED TO INTACT WHAT MATERIALS WILL BE USED IN THAT
        // SPECIFIC DESIGN IF USER WILL ORDER IT
        // (COULD BE MULTIPLE MATERIALS IS USED IN THAT DESIGN)


        $selectedDesignID = 1;

        $selectedMaterials = [
            [
                'material_id' => 1,
                'quantity_used' => 20,
            ],

            [
                'material_id' => 2,
                'quantity_used' => 15,
            ]
        ];

        foreach ($selectedMaterials as $materialData) {
            DB::table('designs_materials')->insert([
                'design_id' => $selectedDesignID,
                'material_id' => $materialData['material_id'],
                'quantity_used' => $materialData['quantity_used'], 
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }



        // CHANGE OF PLANS: ONLY THE DESIGN CAN PICK WHAT MATERIALS BEING USED IN THEM
        // TO AVOID DATA REDUNDANCY

        // EDITING THE MATERIALS CREATED TO INTACT WHAT DESIGN IT'S BEING USED 
        // (COULD BE MULTIPLE DESIGN IS USING THAT MATERIAL)


        // 1ST MATERIAL

        // $selectedMaterialID1 = 1;

        // $selectedDesignsForMat1 = [
        //     [
        //         'design_id' => 1,
        //         'quantity_used' => 20,
        //     ],
        // ];


        // foreach ($selectedDesignsForMat1 as $designData) {
        //     DB::table('designs_materials')->insert([
        //         'material_id' => $selectedMaterialID1,
        //         'design_id' => $designData['design_id'],
        //         'quantity_used' => $designData['quantity_used'], 
        //         'created_at' => now(),
        //         'updated_at' => now()
        //     ]);
        // }


        // // 2ND MATERIAL
        // $selectedMaterialID2 = 2;

        // $selectedDesignsForMat2 = [
        //     [
        //         'design_id' => 1,
        //         'quantity_used' => 15,
        //     ],
        // ];


        // foreach ($selectedDesignsForMat2 as $designData) {
        //     DB::table('designs_materials')->insert([
        //         'material_id' => $selectedMaterialID2,
        //         'design_id' => $designData['design_id'],
        //         'quantity_used' => $designData['quantity_used'], 
        //         'created_at' => now(),
        //         'updated_at' => now()
        //     ]);
        // }
    }
}
