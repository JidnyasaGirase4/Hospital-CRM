<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('form')->nullable();
            $table->string('strength')->nullable();
            $table->string('unit')->nullable();
            $table->unsignedInteger('reorder_level')->default(0);
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
