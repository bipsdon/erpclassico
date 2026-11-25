<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_bills', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();   // INV-YYYYMM-XXXX
            $table->string('bill_to');
            $table->date('bill_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('custom_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_bill_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('description')->nullable();
            $table->decimal('qty', 10, 2);
            $table->decimal('price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_bill_items');
        Schema::dropIfExists('custom_bills');
    }
};
