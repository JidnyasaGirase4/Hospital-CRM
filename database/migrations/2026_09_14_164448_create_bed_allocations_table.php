<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bed_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bed_id')->constrained()->restrictOnDelete();
            $table->dateTime('allocated_at');
            $table->dateTime('released_at')->nullable();
            $table->timestamps();

            // Defense in depth alongside the app-level lockForUpdate check in
            // BedAllocationService: MySQL unique indexes treat NULLs as
            // distinct, so a plain unique(bed_id, released_at) would NOT stop
            // two concurrent "active" (released_at IS NULL) rows for the same
            // bed. This generated column collapses to NULL for released rows
            // (which may repeat freely) and to bed_id for the one allowed
            // active row per bed, giving a true "at most one active
            // allocation per bed" constraint the database itself enforces.
            $table->unsignedBigInteger('active_bed_id')
                ->nullable()
                ->virtualAs('CASE WHEN released_at IS NULL THEN bed_id ELSE NULL END');
            $table->unique('active_bed_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_allocations');
    }
};
