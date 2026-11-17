<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportsController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryReportOpeningStockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-17 12:00:00');

        Schema::create('owner_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('timezone')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->default('kg');
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('initial_quantity', 10, 2)->default(0);
            $table->decimal('price_per_unit', 10, 2)->default(0);
            $table->date('received_date')->nullable();
            $table->string('status')->default('available');
        });

        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('sale');
            $table->string('status')->default('completed');
            $table->timestamps();
        });

        Schema::create('sales_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_transaction_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->decimal('quantity', 10, 2)->default(0);
        });
    }

    protected function tearDown(): void
    {
        Auth::forgetGuards();
        Carbon::setTestNow();

        foreach (['sales_items', 'sales_transactions', 'inventory_batches', 'inventory_items', 'owner_settings'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_later_opening_stock_is_listed_as_zero_in_earlier_month_and_received_in_its_month(): void
    {
        $user = new User;
        $user->setRawAttributes(['id' => 1, 'role' => 'owner']);
        Auth::setUser($user);

        $appleId = Schema::getConnection()->table('inventory_items')->insertGetId([
            'name' => 'Apple',
            'unit' => 'kg',
            'stock_quantity' => 12,
            'reorder_level' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::getConnection()->table('inventory_batches')->insert([
            'inventory_item_id' => $appleId,
            'quantity' => 12,
            'initial_quantity' => 12,
            'price_per_unit' => 100,
            'received_date' => '2026-11-05',
            'status' => 'available',
        ]);

        $october = app(ReportsController::class)->inventoryReport(Request::create('/reports/inventory', 'GET', [
            'period' => '2026-10',
        ]))->getData(true);

        $appleInOctober = collect($october['items'])->firstWhere('product_name', 'Apple');
        $this->assertNotNull($appleInOctober);
        $this->assertEquals(0, $appleInOctober['opening_stock']);
        $this->assertEquals(0, $appleInOctober['received']);
        $this->assertEquals(0, $appleInOctober['closing_stock']);

        $november = app(ReportsController::class)->inventoryReport(Request::create('/reports/inventory', 'GET', [
            'period' => '2026-11',
        ]))->getData(true);

        $appleInNovember = collect($november['items'])->firstWhere('product_name', 'Apple');
        $this->assertNotNull($appleInNovember);
        $this->assertEquals(12, $appleInNovember['received']);
        $this->assertEquals(12, $appleInNovember['closing_stock']);
    }
}
