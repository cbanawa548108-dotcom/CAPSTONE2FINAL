<?php

namespace Tests\Feature;

use App\Models\OwnerSetting;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role');
        });

        Schema::create('owner_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('timezone')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('transaction_code')->unique();
            $table->string('type')->default('sale');
            $table->string('payment_method')->nullable();
            $table->string('status')->default('completed');
            $table->decimal('total_amount')->default(0);
            $table->timestamps();
        });

        Schema::create('sales_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_transaction_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->unsignedBigInteger('inventory_batch_id')->nullable();
            $table->decimal('quantity')->default(0);
            $table->decimal('unit_price')->default(0);
            $table->decimal('total_amount')->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit')->default('kg');
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id')->nullable();
        });
    }

    public function test_csv_exports_all_transactions_for_the_selected_business_date(): void
    {
        $user = $this->owner();
        $this->createTransactions();

        $response = $this->actingAs($user)->get(route('api.sales.export.csv', [
            'date' => '2026-11-13',
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('sales-transactions-2026-11-13.csv');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('TXN-01', $csv);
        $this->assertStringContainsString('TXN-11', $csv);
        $this->assertStringNotContainsString('TXN-OUTSIDE', $csv);
        $this->assertSame(12, substr_count($csv, "\n"));
    }

    public function test_pdf_export_prints_sales_for_the_selected_date_with_the_logo(): void
    {
        $user = $this->owner();
        $this->createTransactions();

        $this->actingAs($user)
            ->get(route('api.sales.export.pdf', ['date' => '2026-11-13']))
            ->assertOk()
            ->assertSee('Sales Transactions')
            ->assertSee('freshtrack-logo.svg')
            ->assertSee('TXN-01')
            ->assertSee('TXN-11')
            ->assertDontSee('TXN-OUTSIDE');
    }

    private function owner(): User
    {
        $user = new User;
        $user->setRawAttributes(['id' => 1, 'name' => 'Test Owner', 'role' => 'owner']);

        OwnerSetting::create([
            'user_id' => $user->id,
            'timezone' => 'Asia/Manila',
        ]);

        return $user;
    }

    private function createTransactions(): void
    {
        $createdAt = Carbon::parse('2026-11-13 00:30:00', 'Asia/Manila')->utc();

        for ($number = 1; $number <= 11; $number++) {
            $sale = SalesTransaction::create([
                'user_id' => 1,
                'transaction_code' => sprintf('TXN-%02d', $number),
                'type' => 'sale',
                'payment_method' => 'cash',
                'status' => 'completed',
                'total_amount' => 42,
            ]);
            $sale->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        }

        $outsideSale = SalesTransaction::create([
            'user_id' => 1,
            'transaction_code' => 'TXN-OUTSIDE',
            'type' => 'sale',
            'payment_method' => 'cash',
            'status' => 'completed',
            'total_amount' => 42,
        ]);
        $outsideDate = $createdAt->copy()->addDay();
        $outsideSale->forceFill(['created_at' => $outsideDate, 'updated_at' => $outsideDate])->save();

        foreach (SalesTransaction::all() as $sale) {
            SalesItem::create([
                'sale_transaction_id' => $sale->id,
                'quantity' => 2,
                'unit_price' => 21,
                'total_amount' => 42,
            ]);
        }
    }
}
