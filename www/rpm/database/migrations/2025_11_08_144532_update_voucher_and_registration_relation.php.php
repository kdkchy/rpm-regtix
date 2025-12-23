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
            $table->foreignId('voucher_code_id')
                ->nullable()
                ->after('category_ticket_type_id')
                ->constrained('voucher_codes')
                ->nullOnDelete();
        });

        Schema::table('voucher_codes', function (Blueprint $table) {
            if (Schema::hasColumn('voucher_codes', 'registration_id')) {
                $table->dropForeign(['registration_id']);
                $table->dropColumn('registration_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            if (Schema::hasColumn('registrations', 'voucher_code_id')) {
                $table->dropForeign(['voucher_code_id']);
                $table->dropColumn('voucher_code_id');
            }
        });
        
        Schema::table('voucher_codes', function (Blueprint $table) {
            $table->foreignId('registration_id')
                ->nullable()
                ->constrained('registrations')
                ->nullOnDelete();
        });
    }
};
