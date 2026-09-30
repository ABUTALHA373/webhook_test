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
        Schema::create('callback_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('webhook_request_id')->constrained('webhook_requests')->onDelete('cascade');
            $table->foreignId('callback_rule_id')->nullable()->constrained('callback_rules')->nullOnDelete();
            $table->string('status', 30)->default('queued')->index(); // queued, success, failed, retrying
            $table->integer('attempt')->default(1);
            $table->text('target_url');
            $table->string('request_method', 15)->default('POST');
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->integer('response_status')->nullable()->index();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->decimal('duration_ms', 10, 2)->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['webhook_request_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callback_logs');
    }
};
