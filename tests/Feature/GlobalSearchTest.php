<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->decimal('stock_quantity')->default(0);
            $table->string('unit')->default('kg');
            $table->timestamps();
        });

        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type')->default('sale');
            $table->string('transaction_code')->unique();
            $table->decimal('total_amount')->default(0);
            $table->string('status')->default('completed');
            $table->timestamps();
        });

        Schema::create('sales_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_transaction_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->decimal('quantity')->default(0);
            $table->decimal('unit_price')->default(0);
            $table->decimal('total_amount')->default(0);
            $table->timestamps();
        });
    }

    private function owner(): User
    {
        $user = new User();
        $user->setRawAttributes(['id' => 1, 'role' => 'owner']);

        return $user;
    }

    public function test_authenticated_users_can_search_products_and_transactions(): void
    {
        $user = $this->owner();
        $product = InventoryItem::create([
            'name' => 'Pomelo',
            'category' => 'Citrus',
            'unit' => 'kg',
            'stock_quantity' => 12,
        ]);
        $sale = SalesTransaction::create([
            'user_id' => $user->id,
            'transaction_code' => 'TXN-POMELO-001',
            'type' => 'sale',
            'total_amount' => 560,
            'status' => 'completed',
        ]);
        SalesItem::create([
            'sale_transaction_id' => $sale->id,
            'inventory_item_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 250,
            'total_amount' => 500,
        ]);

        $response = $this->actingAs($user)->getJson(route('api.search', ['q' => 'Pomelo']));

        $response->assertOk()
            ->assertJsonFragment(['type' => 'Product', 'title' => 'Pomelo'])
            ->assertJsonFragment(['type' => 'Sale', 'title' => 'TXN-POMELO-001']);
    }

    public function test_global_search_requires_a_query_of_at_least_two_characters(): void
    {
        $user = $this->owner();

        $this->actingAs($user)
            ->getJson(route('api.search', ['q' => 'a']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }
}
