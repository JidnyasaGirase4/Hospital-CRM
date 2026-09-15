<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained()->restrictOnDelete();
            $table->string('policy_number');
            $table->date('valid_from')->nullable();
            $table->date('valid_till')->nullable();
            $table->decimal('coverage_amount', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['insurance_company_id', 'policy_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
