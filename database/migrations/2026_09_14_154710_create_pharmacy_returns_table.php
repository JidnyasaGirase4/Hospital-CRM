<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_sale_item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('returned_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_returns');
    }
};
