<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_in_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->nullable()->index();
            $table->string('supplier');
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->foreignId('stock_in_transaction_id')
                ->nullable()
                ->after('inventory_item_id')
                ->constrained('stock_in_transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_in_transaction_id');
        });

        Schema::dropIfExists('stock_in_transactions');
    }
};
