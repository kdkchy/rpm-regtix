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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('registrations')->onDelete('cascade');
            $table->string('brevo_message_id')->nullable()->unique()->comment('Message ID dari Brevo API');
            $table->string('email')->comment('Alamat email yang dikirim');
            $table->enum('status', [
                'sent',
                'delivered',
                'bounced',
                'invalid',
                'hardBounce',
                'softBounce',
                'error'
            ])->default('sent')->comment('Status email dari Brevo');
            $table->json('status_details')->nullable()->comment('Detail tambahan dari webhook atau API response');
            $table->string('event_type')->nullable()->comment('Jenis event dari webhook (delivered, bounced, dll)');
            $table->timestamp('sent_at')->nullable()->comment('Timestamp saat email dikirim');
            $table->timestamp('delivered_at')->nullable()->comment('Timestamp saat email sampai ke inbox');
            $table->timestamp('bounced_at')->nullable()->comment('Timestamp saat email bounce');
            $table->text('error_message')->nullable()->comment('Pesan error jika ada');
            $table->timestamps();

            // Indexes
            $table->index('registration_id');
            $table->index('brevo_message_id');
            $table->index('status');
            $table->index('sent_at');
            $table->index(['status', 'sent_at']); // Composite index untuk polling query
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
