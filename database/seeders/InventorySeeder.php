<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{ItemCategory, Item, Warehouse, Supplier};
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        try {
            // Create Item Categories
            $categories = [
                ['code' => 'PIPE', 'name' => 'Pipa', 'description' => 'Berbagai jenis pipa untuk instalasi gas'],
                ['code' => 'FITTING', 'name' => 'Fitting', 'description' => 'Sambungan dan fitting pipa'],
                ['code' => 'VALVE', 'name' => 'Valve', 'description' => 'Katup dan valve'],
                ['code' => 'EQUIPMENT', 'name' => 'Equipment', 'description' => 'Peralatan dan equipment'],
                ['code' => 'ACCESSORY', 'name' => 'Accessory', 'description' => 'Aksesoris pendukung'],
            ];

            foreach ($categories as $cat) {
                ItemCategory::create($cat);
            }

            // Create Warehouses
            $warehouses = [
                [
                    'code' => 'WH-JKT-001',
                    'name' => 'Gudang Jakarta Pusat',
                    'location' => 'Jakarta Pusat',
                    'address' => 'Jl. Contoh No. 123, Jakarta Pusat',
                    'warehouse_type' => 'pusat',
                    'is_active' => true,
                    'pic_name' => 'Admin Gudang',
                    'pic_phone' => '081234567890',
                ],
                [
                    'code' => 'WH-JKT-002',
                    'name' => 'Gudang Jakarta Selatan',
                    'location' => 'Jakarta Selatan',
                    'address' => 'Jl. Contoh No. 456, Jakarta Selatan',
                    'warehouse_type' => 'cabang',
                    'is_active' => true,
                    'pic_name' => 'Admin Cabang',
                    'pic_phone' => '081234567891',
                ],
            ];

            foreach ($warehouses as $wh) {
                Warehouse::create($wh);
            }

            // Create Items
            $pipeCategory = ItemCategory::where('code', 'PIPE')->first();
            $fittingCategory = ItemCategory::where('code', 'FITTING')->first();
            $valveCategory = ItemCategory::where('code', 'VALVE')->first();
            $equipmentCategory = ItemCategory::where('code', 'EQUIPMENT')->first();

            $items = [
                // Pipes
                [
                    'code' => 'PIPE-PE-20MM',
                    'name' => 'Pipa PE 20mm',
                    'category_id' => $pipeCategory->id,
                    'unit' => 'm',
                    'description' => 'Pipa Polyethylene diameter 20mm',
                    'minimum_stock' => 100,
                    'maximum_stock' => 1000,
                    'reorder_point' => 200,
                ],
                [
                    'code' => 'PIPE-GL-MEDIUM',
                    'name' => 'Pipa Galvanis Medium',
                    'category_id' => $pipeCategory->id,
                    'unit' => 'm',
                    'description' => 'Pipa galvanis ukuran medium',
                    'minimum_stock' => 50,
                    'maximum_stock' => 500,
                    'reorder_point' => 100,
                ],
                [
                    'code' => 'PIPE-GL-3/4',
                    'name' => 'Pipa Galvanis 3/4"',
                    'category_id' => $pipeCategory->id,
                    'unit' => 'm',
                    'description' => 'Pipa galvanis diameter 3/4 inch',
                    'minimum_stock' => 50,
                    'maximum_stock' => 500,
                    'reorder_point' => 100,
                ],

                // Fittings
                [
                    'code' => 'ELBOW-1/2-GALV',
                    'name' => 'Elbow 1/2" Galvanis',
                    'category_id' => $fittingCategory->id,
                    'unit' => 'pcs',
                    'description' => 'Elbow galvanis 1/2 inch',
                    'minimum_stock' => 50,
                    'maximum_stock' => 500,
                    'reorder_point' => 100,
                ],
                [
                    'code' => 'COUPLER-20MM',
                    'name' => 'Coupler 20mm',
                    'category_id' => $fittingCategory->id,
                    'unit' => 'pcs',
                    'description' => 'Coupler untuk pipa 20mm',
                    'minimum_stock' => 50,
                    'maximum_stock' => 500,
                    'reorder_point' => 100,
                ],

                // Valves
                [
                    'code' => 'BALL-VALVE-1/2',
                    'name' => 'Ball Valve 1/2"',
                    'category_id' => $valveCategory->id,
                    'unit' => 'pcs',
                    'description' => 'Ball valve diameter 1/2 inch',
                    'minimum_stock' => 20,
                    'maximum_stock' => 200,
                    'reorder_point' => 50,
                ],
                [
                    'code' => 'BALL-VALVE-3/4',
                    'name' => 'Ball Valve 3/4"',
                    'category_id' => $valveCategory->id,
                    'unit' => 'pcs',
                    'description' => 'Ball valve diameter 3/4 inch',
                    'minimum_stock' => 20,
                    'maximum_stock' => 200,
                    'reorder_point' => 50,
                ],

                // Equipment
                [
                    'code' => 'MGRT',
                    'name' => 'Meter Gas Rumah Tangga (MGRT)',
                    'category_id' => $equipmentCategory->id,
                    'unit' => 'unit',
                    'description' => 'Meter gas untuk rumah tangga',
                    'minimum_stock' => 10,
                    'maximum_stock' => 100,
                    'reorder_point' => 20,
                ],
            ];

            foreach ($items as $item) {
                Item::create($item);
            }

            // Create Suppliers
            $suppliers = [
                [
                    'code' => 'SUP-001',
                    'name' => 'PT. Supplier Pipa Indonesia',
                    'contact_person' => 'Budi Santoso',
                    'phone' => '021-12345678',
                    'email' => 'budi@supplier-pipa.com',
                    'address' => 'Jl. Supplier No. 1, Jakarta',
                    'is_active' => true,
                ],
                [
                    'code' => 'SUP-002',
                    'name' => 'CV. Fitting Jaya',
                    'contact_person' => 'Siti Aminah',
                    'phone' => '021-87654321',
                    'email' => 'siti@fitting-jaya.com',
                    'address' => 'Jl. Supplier No. 2, Jakarta',
                    'is_active' => true,
                ],
            ];

            foreach ($suppliers as $supplier) {
                Supplier::create($supplier);
            }

            DB::commit();
            $this->command->info('Inventory seeder completed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
