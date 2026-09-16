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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chef_id')->constrained('chefs')->onDelete('cascade');
            $table->double('total_earning', 15, 2); // Original amount (e.g., 10,000)
            $table->double('commission_amount', 15, 2); // 10% (e.g., 1,000)
            $table->double('payout_amount', 15, 2); // Net paid (e.g., 9,000)
            $table->string('razorpay_payout_id')->nullable();
            $table->string('status'); // 'processing', 'processed', 'failed'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
