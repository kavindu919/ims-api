<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('quantity')->default(0);
            $table->string('serial_number')->nullable();
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('storage_place_id')
                  ->constrained('storage_places')
                  ->restrictOnDelete();
            $table->enum('status', ['in_store', 'borrowed', 'damaged', 'missing'])
                  ->default('in_store');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
