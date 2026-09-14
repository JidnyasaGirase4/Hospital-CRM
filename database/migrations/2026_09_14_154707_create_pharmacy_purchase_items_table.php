<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $table->foreignId('medicine_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number');
            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 12, 2);
            $table->date('expiry_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_purchase_items');
    }
};
