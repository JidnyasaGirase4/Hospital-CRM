<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('triage_level', ['critical', 'urgent', 'semi-urgent', 'non-urgent'])->nullable();
            $table->enum('status', ['registered', 'triaged', 'in-treatment', 'admitted', 'discharged', 'referred'])->default('registered');
            $table->dateTime('registered_at');
            $table->text('chief_complaint')->nullable();
            $table->text('treatment_notes')->nullable();
            $table->string('referred_to')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_visits');
    }
};
