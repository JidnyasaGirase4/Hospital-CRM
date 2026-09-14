<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('prescription_item_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_sale_items');
    }
};
