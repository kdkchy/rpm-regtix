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
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('rpc_collection_days');
            $table->dropColumn('rpc_collection_dates');
            $table->datetime('rpc_start_date')->nullable()->after('description');
            $table->datetime('rpc_end_date')->nullable()->after('rpc_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('events', function (Blueprint $table) {
            $table->string('rpc_collection_days')->nullable()->after('description');
            $table->String('rpc_collection_dates')->nullable()->after('rpc_collection_days');

            $table->dropColumn('rpc_start_date');
            $table->dropColumn('rpc_end_date');
        });
    }
};
