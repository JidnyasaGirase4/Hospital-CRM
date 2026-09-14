<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number');
            $table->integer('quantity')->default(0);
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('mrp', 10, 2)->nullable();
            $table->date('manufactured_date')->nullable();
            $table->date('expiry_date');
            $table->date('received_at')->nullable();
            $table->timestamps();

            $table->index(['medicine_id', 'expiry_date']);
            $table->unique(['medicine_id', 'batch_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_batches');
    }
};
