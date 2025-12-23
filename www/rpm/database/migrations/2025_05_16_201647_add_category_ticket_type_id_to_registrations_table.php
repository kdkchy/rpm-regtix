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
            $table->dropForeign(['ticket_type_id']);
            $table->dropColumn('ticket_type_id');
            $table->foreignId('category_ticket_type_id')->after('id')->nullable()->constrained('category_ticket_type')->onDelete('set null');
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('ticket_type_id')->after('id')->nullable()->constrained()->onDelete('set null');
            $table->dropForeign(['category_ticket_type_id']);
            $table->dropColumn('category_ticket_type_id');
        });
    }
};
