<?php

namespace Tests\Feature;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->default('kg');
            $table->decimal('price_per_unit', 10, 2)->default(0);
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id');
            $table->string('batch_code')->unique();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('price_per_unit', 10, 2)->default(0);
            $table->date('received_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('supplier')->nullable();
            $table->string('status')->default('available');
            $table->timestamps();
        });
    }

    public function test_inventory_export_includes_products_and_all_batches(): void
    {
        $user = new User;
        $user->setRawAttributes(['id' => 1, 'role' => 'owner']);

        $mango = InventoryItem::create([
            'name' => '=Mango',
            'category' => 'Fruit',
            'unit' => 'kg',
            'stock_quantity' => 18,
            'reorder_level' => 5,
            'status' => 'active',
        ]);
        InventoryBatch::create([
            'inventory_item_id' => $mango->id,
            'batch_code' => 'MNG-001',
            'quantity' => 10,
            'price_per_unit' => 120,
            'received_date' => '2026-11-01',
            'expiry_date' => '2026-11-15',
            'supplier' => 'Davao Farms',
            'status' => 'available',
        ]);
        InventoryBatch::create([
            'inventory_item_id' => $mango->id,
            'batch_code' => 'MNG-002',
            'quantity' => 8,
            'price_per_unit' => 125,
            'received_date' => '2026-11-02',
            'expiry_date' => '2026-11-16',
            'supplier' => 'Davao Farms',
            'status' => 'low_stock',
        ]);
        InventoryItem::create([
            'name' => 'Banana',
            'category' => 'Fruit',
            'unit' => 'kg',
            'stock_quantity' => 0,
            'reorder_level' => 3,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('api.inventory.export.csv'));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('inventory-'.now()->toDateString().'.csv');

        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=Mango", $csv);
        $this->assertStringContainsString('MNG-001', $csv);
        $this->assertStringContainsString('MNG-002', $csv);
        $this->assertStringContainsString('Banana', $csv);
        $this->assertStringContainsString('Out of Stock', $csv);
        $this->assertSame(4, substr_count($csv, "\n"));
    }
}
