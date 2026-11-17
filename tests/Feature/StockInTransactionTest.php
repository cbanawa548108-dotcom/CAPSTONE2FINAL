<?php

namespace Tests\Feature;

use App\Http\Controllers\InventoryController;
use App\Models\InventoryItem;
use App\Models\StockInTransaction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockInTransactionTest extends TestCase
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

        Schema::create('stock_in_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->nullable();
            $table->string('supplier');
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id');
            $table->foreignId('stock_in_transaction_id')->nullable();
            $table->string('batch_code')->unique();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('initial_quantity', 10, 2)->default(0);
            $table->decimal('price_per_unit', 10, 2)->default(0);
            $table->date('received_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('supplier')->nullable();
            $table->string('status')->default('available');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('stock_in_transactions');
        Schema::dropIfExists('inventory_items');

        parent::tearDown();
    }

    public function test_one_stock_in_transaction_can_contain_multiple_product_batches(): void
    {
        $mango = InventoryItem::create(['name' => 'Mango']);
        $papaya = InventoryItem::create(['name' => 'Papaya']);

        $response = app(InventoryController::class)->stockIn(Request::create('/api/inventory/stock-in', 'POST', [
            'supplier' => 'Davao Fresh Farms',
            'received_date' => '2026-11-17',
            'reference_number' => 'SI-2611-1234',
            'items' => [
                [
                    'product_id' => $mango->id,
                    'product_name' => 'Mango',
                    'quantity' => 12,
                    'price_per_unit' => 80,
                    'batch_code' => 'MAN-101',
                    'expiry_date' => '2026-12-01',
                    'supplier' => 'Davao Fresh Farms',
                ],
                [
                    'product_id' => $papaya->id,
                    'product_name' => 'Papaya',
                    'quantity' => 8,
                    'price_per_unit' => 45,
                    'batch_code' => 'PAP-102',
                    'expiry_date' => '2026-11-30',
                    'supplier' => 'Davao Fresh Farms',
                ],
            ],
        ]));

        $this->assertTrue($response->getData()->success);
        $transaction = StockInTransaction::with('batches')->sole();
        $this->assertSame('SI-2611-1234', $transaction->reference_number);
        $this->assertCount(2, $transaction->batches);
        $this->assertSame(
            [$mango->id, $papaya->id],
            $transaction->batches->pluck('inventory_item_id')->sort()->values()->all()
        );
    }

    public function test_stock_in_returns_a_clear_error_when_its_migration_is_pending(): void
    {
        Schema::drop('stock_in_transactions');

        $response = app(InventoryController::class)->stockIn(Request::create('/api/inventory/stock-in', 'POST'));

        $this->assertSame(503, $response->getStatusCode());
        $this->assertStringContainsString('migration is pending', $response->getData()->message);
    }
}
