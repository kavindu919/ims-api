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
        Schema::create('borrow_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')
                  ->constrained('inventory_items')
                  ->restrictOnDelete();
            $table->string('borrower_name');
            $table->string('contact');
            $table->date('borrow_date');
            $table->date('expected_return_date');
            $table->date('return_date')->nullable();
            $table->unsignedInteger('quantity_borrowed');
            $table->enum('status', ['borrowed', 'returned'])->default('borrowed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_records');
    }
};
