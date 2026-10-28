<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->uuid('tracking_token')->unique();
            $table->foreignId('dining_table_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 30)->nullable();
            $table->text('notes')->nullable();

            $table->string('status', 20)->default('pending');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_url', 2048)->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('service_amount')->default(0);
            $table->unsignedInteger('total');

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
