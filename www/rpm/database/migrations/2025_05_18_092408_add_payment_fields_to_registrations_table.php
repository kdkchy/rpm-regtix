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
        Schema::table('registrations', function (Blueprint $table) {
            $table->enum('payment_status', [
                'pending',
                'paid',
                'settlement',
                'cancel',
                'deny',
                'expire',
                'failure',
                'refund'
            ])->default('pending')->after('transaction_code');

            $table->string('payment_type')->nullable()->after('payment_status');   // e.g. gopay, credit_card
            $table->string('payment_method')->nullable()->after('payment_type');   // e.g. bank_transfer
            $table->decimal('gross_amount', 12, 2)->nullable()->after('payment_method'); // e.g. 99000.00

            $table->datetime('paid_at')->nullable()->after('gross_amount');        // waktu dibayar
            $table->string('payment_token')->nullable()->after('paid_at');         // Snap token
            $table->text('payment_url')->nullable()->after('payment_token');       // Snap redirect URL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_type',
                'payment_method',
                'gross_amount',
                'paid_at',
                'payment_token',
                'payment_url',
            ]);
        });
    }
};
