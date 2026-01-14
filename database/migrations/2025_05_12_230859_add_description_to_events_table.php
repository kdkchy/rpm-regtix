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
            $table->string('location_gmaps_url')->nullable()->after('location');
            $table->date('registration_start_date')->nullable()->after('location_gmaps_url');
            $table->date('registration_end_date')->nullable()->after('registration_start_date');
            $table->text('description')->nullable()->after('registration_end_date');
            $table->string('rpc_collection_days')->nullable()->after('description');
            $table->String('rpc_collection_dates')->nullable()->after('rpc_collection_days');
            $table->string('rpc_collection_times')->nullable()->after('rpc_collection_dates');
            $table->string('rpc_collection_location')->nullable()->after('rpc_collection_times');
            $table->string('rpc_collection_gmaps_url')->nullable()->after('rpc_collection_location');
            $table->string('event_url')->nullable()->after('rpc_collection_gmaps_url');
            $table->string('ig_url')->nullable()->after('event_url');
            $table->string('fb_url')->nullable()->after('ig_url');
            $table->string('contact_email')->nullable()->after('fb_url');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->string('event_owner')->nullable()->after('contact_phone');
            $table->string('event_organizer')->nullable()->after('event_owner');            
            $table->string('event_logo')->nullable()->after('event_organizer'); 
            $table->string('event_banner')->nullable()->after('event_logo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            //
        });
    }
};
