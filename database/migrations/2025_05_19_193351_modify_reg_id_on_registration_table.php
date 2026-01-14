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
            // Drop unique index
            $table->dropUnique(['reg_id']); // Or use the index name if needed

            // Make column nullable
            $table->string('reg_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('registrations', function (Blueprint $table) {
            // Re-add unique constraint
            $table->unique('reg_id');

            // Make column not nullable again
            $table->string('reg_id')->nullable(false)->change();
        });
    }
};
