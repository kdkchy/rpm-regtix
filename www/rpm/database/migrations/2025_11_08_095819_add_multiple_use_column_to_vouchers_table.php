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
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'discount')) {
                $table->renameColumn('discount', 'final_price');
            }
            if (!Schema::hasColumn('vouchers', 'is_multiple_use')) {
                $table->boolean('is_multiple_use')->after('final_price')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'final_price')) {
                $table->renameColumn('final_price', 'discount');
            }

            if (Schema::hasColumn('vouchers', 'is_multiple_use')) {
                $table->dropColumn('is_multiple_use');
            }
        });
    }
};
