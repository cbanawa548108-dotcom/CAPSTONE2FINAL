<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_training_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->date('sale_date');
            $table->decimal('quantity', 10, 2);
            $table->timestamps();

            $table->unique(['inventory_item_id', 'sale_date']);
            $table->index('sale_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_training_samples');
    }
};
