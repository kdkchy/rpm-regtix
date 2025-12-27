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
        Schema::create('registration_failed', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_id')->nullable()->comment('ID dari tabel registrations asli');
            
            // Copy all columns from registrations table
            $table->foreignId('category_ticket_type_id')->nullable()->constrained('category_ticket_type')->onDelete('set null');
            $table->foreignId('voucher_code_id')->nullable()->constrained('voucher_codes')->onDelete('set null');
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
            $table->string('jersey_size', 20)->nullable();
            $table->string('community_name')->nullable();
            $table->string('bib_name')->nullable();
            $table->string('reg_id')->nullable();
            $table->boolean('is_validated')->default(false);
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->enum('status', ['pending', 'confirmed'])->default('pending');
            $table->string('transaction_code')->nullable();
            $table->enum('payment_status', [
                'pending',
                'paid',
                'settlement',
                'cancel',
                'deny',
                'expire',
                'failure',
                'refund'
            ])->default('pending');
            $table->string('payment_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('gross_amount', 12, 2)->nullable();
            $table->datetime('paid_at')->nullable();
            $table->string('payment_token')->nullable();
            $table->text('payment_url')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->string('registration_code')->nullable();
            $table->dateTime('registration_date')->nullable();
            $table->string('invitation_code')->nullable();
            $table->dateTime('last_printed_at')->nullable();
            
            // Backup-specific columns
            $table->timestamp('failed_at')->useCurrent()->comment('Timestamp kapan dihapus');
            $table->enum('failed_reason', ['expired_unpaid', 'manual', 'other'])->default('expired_unpaid');
            $table->timestamp('restored_at')->nullable()->comment('Timestamp jika di-restore');
            $table->unsignedBigInteger('restored_by')->nullable()->comment('User ID yang restore');
            $table->text('restore_note')->nullable()->comment('Catatan restore');
            
            $table->timestamps();
            
            // Indexes
            $table->index('registration_code', 'idx_registration_code');
            $table->index('failed_at', 'idx_failed_at');
            $table->index('original_id', 'idx_original_id');
            $table->index('restored_at', 'idx_restored_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_failed');
    }
};
