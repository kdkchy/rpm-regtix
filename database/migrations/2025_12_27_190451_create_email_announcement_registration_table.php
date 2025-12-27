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
        if (!Schema::hasTable('email_announcement_registration')) {
            Schema::create('email_announcement_registration', function (Blueprint $table) {
                $table->id();
                $table->foreignId('email_announcement_id')->constrained()->onDelete('cascade');
                $table->foreignId('registration_id')->constrained()->onDelete('cascade');
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
                $table->foreignId('email_log_id')->nullable()->constrained('email_logs')->onDelete('set null');
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->unique(['email_announcement_id', 'registration_id']);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_announcement_registration');
    }
};
