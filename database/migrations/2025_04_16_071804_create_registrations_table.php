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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_type_id')->constrained()->onDelete('cascade');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->string('place_of_birth')->nullable();
            $table->date('dob')->nullable();
            $table->string('address')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->enum('id_card_type', ['KTP', 'SIM', 'PASSPORT', 'KARTU PELAJAR', 'KITAS', 'KITAP', 'OTHER'])->nullable();
            $table->string('id_card_number')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->enum('blood_type', ['A', 'B', 'AB', 'O'])->nullable();
            $table->string('nationality')->nullable();
            $table->enum('jersey_size', ['S', 'M', 'L', 'XL', 'XXL'])->nullable();
            $table->string('community_name')->nullable();
            $table->string('bib_name')->nullable();
            $table->string('reg_id')->unique();
            $table->boolean('is_validated')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
