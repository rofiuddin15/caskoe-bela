<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\MenuCategory;
use App\Models\Menu;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Stock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ============================================
        // 1. ROLES & PERMISSIONS
        // ============================================
        $this->createRolesAndPermissions();

        // ============================================
        // 2. BRANCHES
        // ============================================
        $branches = $this->createBranches();

        // ============================================
        // 3. USERS & EMPLOYEES
        // ============================================
        $this->createUsersAndEmployees($branches);

        // ============================================
        // 4. SUPPLIERS
        // ============================================
        $suppliers = $this->createSuppliers();

        // ============================================
        // 5. RAW MATERIALS
        // ============================================
        $rawMaterials = $this->createRawMaterials();

        // ============================================
        // 6. PURCHASE ORDERS & STOCK
        // ============================================
        $this->createPurchaseOrdersAndStock($branches, $suppliers, $rawMaterials);

        // ============================================
        // 7. RECIPES
        // ============================================
        $recipes = $this->createRecipes($rawMaterials);

        // ============================================
        // 8. MENU CATEGORIES & MENUS
        // ============================================
        $this->createMenus($recipes);

        $this->command->info('Database seeding completed successfully!');
    }

    private function createRolesAndPermissions()
    {
        $this->command->info('Creating roles and permissions...');

        // Create roles
        $owner = Role::create(['name' => 'owner']);
        $manager = Role::create(['name' => 'manajer_cabang']);
        $kasir = Role::create(['name' => 'kasir']);
        $gudang = Role::create(['name' => 'staff_gudang']);

        $this->command->info('✓ Roles created');
    }

    private function createBranches()
    {
        $this->command->info('Creating branches...');

        $branches = [
            Branch::create([
                'name' => 'Cabang Senopati',
                'code' => 'SNP',
                'address' => 'Jl. Senopati No. 123, Jakarta Selatan',
                'phone' => '021-7654321',
                'email' => 'senopati@cafepro.com',
                'opening_time' => '08:00',
                'closing_time' => '22:00',
                'is_active' => true,
            ]),
            Branch::create([
                'name' => 'Cabang Kemang',
                'code' => 'KMG',
                'address' => 'Jl. Kemang Raya No. 45, Jakarta Selatan',
                'phone' => '021-7654322',
                'email' => 'kemang@cafepro.com',
                'opening_time' => '09:00',
                'closing_time' => '23:00',
                'is_active' => true,
            ]),
            Branch::create([
                'name' => 'Cabang BSD',
                'code' => 'BSD',
                'address' => 'BSD City, Tangerang Selatan',
                'phone' => '021-7654323',
                'email' => 'bsd@cafepro.com',
                'opening_time' => '10:00',
                'closing_time' => '22:00',
                'is_active' => true,
            ]),
        ];

        $this->command->info('✓ Branches created: ' . count($branches));
        return $branches;
    }

    private function createUsersAndEmployees($branches)
    {
        $this->command->info('Creating users and employees...');

        // Owner
        $owner = User::create([
            'name' => 'Owner Cafe',
            'email' => 'owner@cafepro.com',
            'password' => Hash::make('password'),
            'pin' => '111111',
        ]);
        $owner->assignRole('owner');

        // Manager Senopati
        $manager1 = User::create([
            'name' => 'Manager Senopati',
            'email' => 'manager.senopati@cafepro.com',
            'password' => Hash::make('password'),
            'pin' => '222222',
            'branch_id' => $branches[0]->id,
        ]);
        $manager1->assignRole('manajer_cabang');
        Employee::create([
            'user_id' => $manager1->id,
            'branch_id' => $branches[0]->id,
            'employee_code' => 'MGR001',
            'position' => 'Branch Manager',
            'hire_date' => '2024-01-15',
            'salary' => 8000000,
            'is_active' => true,
        ]);

        // Kasir Senopati 1
        $kasir1 = User::create([
            'name' => 'Kasir Senopati 1',
            'email' => 'kasir1.senopati@cafepro.com',
            'password' => Hash::make('password'),
            'pin' => '123456',
            'branch_id' => $branches[0]->id,
        ]);
        $kasir1->assignRole('kasir');
        Employee::create([
            'user_id' => $kasir1->id,
            'branch_id' => $branches[0]->id,
            'employee_code' => 'KSR001',
            'position' => 'Cashier',
            'hire_date' => '2024-02-01',
            'salary' => 4500000,
            'is_active' => true,
        ]);

        // Kasir Senopati 2
        $kasir2 = User::create([
            'name' => 'Kasir Senopati 2',
            'email' => 'kasir2.senopati@cafepro.com',
            'password' => Hash::make('password'),
            'pin' => '234567',
            'branch_id' => $branches[0]->id,
        ]);
        $kasir2->assignRole('kasir');
        Employee::create([
            'user_id' => $kasir2->id,
            'branch_id' => $branches[0]->id,
            'employee_code' => 'KSR002',
            'position' => 'Cashier',
            'hire_date' => '2024-03-01',
            'salary' => 4500000,
            'is_active' => true,
        ]);

        // Kasir Kemang
        $kasir3 = User::create([
            'name' => 'Kasir Kemang 1',
            'email' => 'kasir1.kemang@cafepro.com',
            'password' => Hash::make('password'),
            'pin' => '345678',
            'branch_id' => $branches[1]->id,
        ]);
        $kasir3->assignRole('kasir');
        Employee::create([
            'user_id' => $kasir3->id,
            'branch_id' => $branches[1]->id,
            'employee_code' => 'KSR003',
            'position' => 'Cashier',
            'hire_date' => '2024-03-15',
            'salary' => 4500000,
            'is_active' => true,
        ]);

        // Staff Gudang
        $gudang1 = User::create([
            'name' => 'Staff Gudang Senopati',
            'email' => 'gudang.senopati@cafepro.com',
            'password' => Hash::make('password'),
            'pin' => '456789',
            'branch_id' => $branches[0]->id,
        ]);
        $gudang1->assignRole('staff_gudang');
        Employee::create([
            'user_id' => $gudang1->id,
            'branch_id' => $branches[0]->id,
            'employee_code' => 'GDG001',
            'position' => 'Warehouse Staff',
            'hire_date' => '2024-02-10',
            'salary' => 4000000,
            'is_active' => true,
        ]);

        $this->command->info('✓ Users and employees created');
    }

    private function createSuppliers()
    {
        $this->command->info('Creating suppliers...');

        $suppliers = [
            Supplier::create([
                'name' => 'PT Kopi Nusantara',
                'code' => 'SUP001',
                'contact_person' => 'Budi Santoso',
                'phone' => '021-9876543',
                'email' => 'sales@kopinusantara.com',
                'address' => 'Jl. Gatot Subroto No. 100, Jakarta',
                'is_active' => true,
            ]),
            Supplier::create([
                'name' => 'CV Susu Segar',
                'code' => 'SUP002',
                'contact_person' => 'Siti Aminah',
                'phone' => '021-9876544',
                'email' => 'order@sususegar.com',
                'address' => 'Jl. Sudirman No. 50, Jakarta',
                'is_active' => true,
            ]),
            Supplier::create([
                'name' => 'Toko Gula Manis',
                'code' => 'SUP003',
                'contact_person' => 'Ahmad',
                'phone' => '021-9876545',
                'email' => 'info@gulamanis.com',
                'address' => 'Jl. Thamrin No. 25, Jakarta',
                'is_active' => true,
            ]),
        ];

        $this->command->info('✓ Suppliers created: ' . count($suppliers));
        return $suppliers;
    }

    private function createRawMaterials()
    {
        $this->command->info('Creating raw materials...');

        $materials = [
            RawMaterial::create([
                'name' => 'Kopi Arabica',
                'code' => 'RM001',
                'unit' => 'gram',
                'unit_price' => 0.15,
                'description' => 'Kopi Arabica premium',
                'min_stock' => 5000,
                'is_active' => true,
            ]),
            RawMaterial::create([
                'name' => 'Kopi Robusta',
                'code' => 'RM002',
                'unit' => 'gram',
                'unit_price' => 0.10,
                'description' => 'Kopi Robusta kualitas baik',
                'min_stock' => 5000,
                'is_active' => true,
            ]),
            RawMaterial::create([
                'name' => 'Susu Full Cream',
                'code' => 'RM003',
                'unit' => 'ml',
                'unit_price' => 0.02,
                'description' => 'Susu segar full cream',
                'min_stock' => 10000,
                'is_active' => true,
            ]),
            RawMaterial::create([
                'name' => 'Gula Pasir',
                'code' => 'RM004',
                'unit' => 'gram',
                'unit_price' => 0.012,
                'description' => 'Gula pasir putih',
                'min_stock' => 10000,
                'is_active' => true,
            ]),
            RawMaterial::create([
                'name' => 'Coklat Bubuk',
                'code' => 'RM005',
                'unit' => 'gram',
                'unit_price' => 0.08,
                'description' => 'Coklat bubuk premium',
                'min_stock' => 3000,
                'is_active' => true,
            ]),
            RawMaterial::create([
                'name' => 'Whipped Cream',
                'code' => 'RM006',
                'unit' => 'ml',
                'unit_price' => 0.05,
                'description' => 'Whipped cream untuk topping',
                'min_stock' => 2000,
                'is_active' => true,
            ]),
            RawMaterial::create([
                'name' => 'Sirup Vanila',
                'code' => 'RM007',
                'unit' => 'ml',
                'unit_price' => 0.04,
                'description' => 'Sirup vanila',
                'min_stock' => 2000,
                'is_active' => true,
            ]),
            RawMaterial::create([
                'name' => 'Sirup Hazelnut',
                'code' => 'RM008',
                'unit' => 'ml',
                'unit_price' => 0.045,
                'description' => 'Sirup hazelnut',
                'min_stock' => 2000,
                'is_active' => true,
            ]),
        ];

        $this->command->info('✓ Raw materials created: ' . count($materials));
        return $materials;
    }

    private function createPurchaseOrdersAndStock($branches, $suppliers, $rawMaterials)
    {
        $this->command->info('Creating purchase orders and stock...');

        // PO 1 - Kopi untuk Senopati
        $po1 = PurchaseOrder::create([
            'po_number' => 'PO-20250101-0001',
            'supplier_id' => $suppliers[0]->id,
            'branch_id' => $branches[0]->id,
            'created_by' => 1,
            'order_date' => '2025-01-01',
            'expected_delivery_date' => '2025-01-05',
            'actual_delivery_date' => '2025-01-05',
            'status' => 'received',
            'total_amount' => 2250,
            'notes' => 'Order bulanan kopi',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'raw_material_id' => $rawMaterials[0]->id, // Arabica
            'quantity' => 10000,
            'unit_price' => 0.15,
            'subtotal' => 1500,
            'received_quantity' => 10000,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'raw_material_id' => $rawMaterials[1]->id, // Robusta
            'quantity' => 7500,
            'unit_price' => 0.10,
            'subtotal' => 750,
            'received_quantity' => 7500,
        ]);

        // Create stock for PO1
        Stock::create([
            'raw_material_id' => $rawMaterials[0]->id,
            'branch_id' => $branches[0]->id,
            'quantity' => 10000,
            'reserved_quantity' => 0,
        ]);

        Stock::create([
            'raw_material_id' => $rawMaterials[1]->id,
            'branch_id' => $branches[0]->id,
            'quantity' => 7500,
            'reserved_quantity' => 0,
        ]);

        // PO 2 - Susu dan Gula untuk Senopati
        $po2 = PurchaseOrder::create([
            'po_number' => 'PO-20250102-0002',
            'supplier_id' => $suppliers[1]->id,
            'branch_id' => $branches[0]->id,
            'created_by' => 1,
            'order_date' => '2025-01-02',
            'expected_delivery_date' => '2025-01-06',
            'actual_delivery_date' => '2025-01-06',
            'status' => 'received',
            'total_amount' => 520,
            'notes' => 'Order susu dan gula',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po2->id,
            'raw_material_id' => $rawMaterials[2]->id, // Susu
            'quantity' => 20000,
            'unit_price' => 0.02,
            'subtotal' => 400,
            'received_quantity' => 20000,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po2->id,
            'raw_material_id' => $rawMaterials[3]->id, // Gula
            'quantity' => 10000,
            'unit_price' => 0.012,
            'subtotal' => 120,
            'received_quantity' => 10000,
        ]);

        Stock::create([
            'raw_material_id' => $rawMaterials[2]->id,
            'branch_id' => $branches[0]->id,
            'quantity' => 20000,
            'reserved_quantity' => 0,
        ]);

        Stock::create([
            'raw_material_id' => $rawMaterials[3]->id,
            'branch_id' => $branches[0]->id,
            'quantity' => 10000,
            'reserved_quantity' => 0,
        ]);

        // Stock untuk bahan lainnya di Senopati
        foreach ([$rawMaterials[4], $rawMaterials[5], $rawMaterials[6], $rawMaterials[7]] as $material) {
            Stock::create([
                'raw_material_id' => $material->id,
                'branch_id' => $branches[0]->id,
                'quantity' => 5000,
                'reserved_quantity' => 0,
            ]);
        }

        // Stock untuk Kemang (sedikit lebih sedikit)
        foreach ($rawMaterials as $material) {
            Stock::create([
                'raw_material_id' => $material->id,
                'branch_id' => $branches[1]->id,
                'quantity' => 3000,
                'reserved_quantity' => 0,
            ]);
        }

        $this->command->info('✓ Purchase orders and stock created');
    }

    private function createRecipes($rawMaterials)
    {
        $this->command->info('Creating recipes...');

        $recipes = [];

        // Recipe: Espresso
        $recipe1 = Recipe::create([
            'name' => 'Espresso',
            'description' => 'Single shot espresso',
            'yield_quantity' => 1,
            'yield_unit' => 'cup',
            'total_cost' => 0,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe1->id,
            'raw_material_id' => $rawMaterials[0]->id, // Arabica
            'quantity' => 18,
            'cost' => 18 * 0.15,
        ]);
        $recipe1->calculateTotalCost();
        $recipes[] = $recipe1;

        // Recipe: Cappuccino
        $recipe2 = Recipe::create([
            'name' => 'Cappuccino',
            'description' => 'Espresso dengan steamed milk dan foam',
            'yield_quantity' => 1,
            'yield_unit' => 'cup',
            'total_cost' => 0,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe2->id,
            'raw_material_id' => $rawMaterials[0]->id, // Arabica
            'quantity' => 18,
            'cost' => 18 * 0.15,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe2->id,
            'raw_material_id' => $rawMaterials[2]->id, // Susu
            'quantity' => 150,
            'cost' => 150 * 0.02,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe2->id,
            'raw_material_id' => $rawMaterials[3]->id, // Gula
            'quantity' => 10,
            'cost' => 10 * 0.012,
        ]);
        $recipe2->calculateTotalCost();
        $recipes[] = $recipe2;

        // Recipe: Cafe Latte
        $recipe3 = Recipe::create([
            'name' => 'Cafe Latte',
            'description' => 'Espresso dengan susu lebih banyak',
            'yield_quantity' => 1,
            'yield_unit' => 'cup',
            'total_cost' => 0,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe3->id,
            'raw_material_id' => $rawMaterials[0]->id,
            'quantity' => 18,
            'cost' => 18 * 0.15,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe3->id,
            'raw_material_id' => $rawMaterials[2]->id,
            'quantity' => 200,
            'cost' => 200 * 0.02,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe3->id,
            'raw_material_id' => $rawMaterials[3]->id,
            'quantity' => 10,
            'cost' => 10 * 0.012,
        ]);
        $recipe3->calculateTotalCost();
        $recipes[] = $recipe3;

        // Recipe: Mocha
        $recipe4 = Recipe::create([
            'name' => 'Mocha',
            'description' => 'Espresso dengan coklat dan susu',
            'yield_quantity' => 1,
            'yield_unit' => 'cup',
            'total_cost' => 0,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe4->id,
            'raw_material_id' => $rawMaterials[0]->id,
            'quantity' => 18,
            'cost' => 18 * 0.15,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe4->id,
            'raw_material_id' => $rawMaterials[2]->id,
            'quantity' => 150,
            'cost' => 150 * 0.02,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe4->id,
            'raw_material_id' => $rawMaterials[4]->id, // Coklat
            'quantity' => 20,
            'cost' => 20 * 0.08,
        ]);
        RecipeItem::create([
            'recipe_id' => $recipe4->id,
            'raw_material_id' => $rawMaterials[5]->id, // Whipped cream
            'quantity' => 30,
            'cost' => 30 * 0.05,
        ]);
        $recipe4->calculateTotalCost();
        $recipes[] = $recipe4;

        $this->command->info('✓ Recipes created: ' . count($recipes));
        return $recipes;
    }

    private function createMenus($recipes)
    {
        $this->command->info('Creating menu categories and menus...');

        // Menu Categories
        $categories = [
            MenuCategory::create([
                'name' => 'Espresso Based',
                'code' => 'ESP',
                'description' => 'Menu berbasis espresso',
                'sort_order' => 1,
                'is_active' => true,
            ]),
            MenuCategory::create([
                'name' => 'Coffee',
                'code' => 'COF',
                'description' => 'Menu kopi',
                'sort_order' => 2,
                'is_active' => true,
            ]),
            MenuCategory::create([
                'name' => 'Non Coffee',
                'code' => 'NCF',
                'description' => 'Menu non kopi',
                'sort_order' => 3,
                'is_active' => true,
            ]),
            MenuCategory::create([
                'name' => 'Snacks',
                'code' => 'SNK',
                'description' => 'Makanan ringan',
                'sort_order' => 4,
                'is_active' => true,
            ]),
        ];

        // Menus
        Menu::create([
            'menu_category_id' => $categories[0]->id,
            'recipe_id' => $recipes[0]->id,
            'name' => 'Espresso',
            'code' => 'MNU001',
            'description' => 'Single shot espresso premium',
            'price' => 18000,
            'cost' => $recipes[0]->total_cost,
            'preparation_time' => 5,
            'is_available' => true,
            'is_active' => true,
        ]);

        Menu::create([
            'menu_category_id' => $categories[0]->id,
            'recipe_id' => $recipes[1]->id,
            'name' => 'Cappuccino',
            'code' => 'MNU002',
            'description' => 'Espresso dengan steamed milk dan foam',
            'price' => 28000,
            'cost' => $recipes[1]->total_cost,
            'preparation_time' => 7,
            'is_available' => true,
            'is_active' => true,
        ]);

        Menu::create([
            'menu_category_id' => $categories[0]->id,
            'recipe_id' => $recipes[2]->id,
            'name' => 'Cafe Latte',
            'code' => 'MNU003',
            'description' => 'Espresso dengan susu creamy',
            'price' => 30000,
            'cost' => $recipes[2]->total_cost,
            'preparation_time' => 7,
            'is_available' => true,
            'is_active' => true,
        ]);

        Menu::create([
            'menu_category_id' => $categories[0]->id,
            'recipe_id' => $recipes[3]->id,
            'name' => 'Mocha',
            'code' => 'MNU004',
            'description' => 'Espresso dengan coklat dan whipped cream',
            'price' => 35000,
            'cost' => $recipes[3]->total_cost,
            'preparation_time' => 8,
            'is_available' => true,
            'is_active' => true,
        ]);

        Menu::create([
            'menu_category_id' => $categories[1]->id,
            'name' => 'Americano',
            'code' => 'MNU005',
            'description' => 'Espresso dengan hot water',
            'price' => 22000,
            'cost' => 3.5,
            'preparation_time' => 5,
            'is_available' => true,
            'is_active' => true,
        ]);

        Menu::create([
            'menu_category_id' => $categories[2]->id,
            'name' => 'Hot Chocolate',
            'code' => 'MNU006',
            'description' => 'Coklat panas premium',
            'price' => 25000,
            'cost' => 5.0,
            'preparation_time' => 6,
            'is_available' => true,
            'is_active' => true,
        ]);

        Menu::create([
            'menu_category_id' => $categories[3]->id,
            'name' => 'Croissant',
            'code' => 'MNU007',
            'description' => 'Croissant butter fresh',
            'price' => 20000,
            'cost' => 8.0,
            'preparation_time' => 2,
            'is_available' => true,
            'is_active' => true,
        ]);

        $this->command->info('✓ Menu categories and menus created');
    }
}
