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
        Schema::create('callback_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('endpoints')->onDelete('cascade');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->text('target_url');
            $table->string('http_method', 15)->default('POST');
            $table->integer('delay_seconds')->default(0);
            $table->string('payload_mode', 30)->default('passthrough'); // passthrough, template, empty
            $table->longText('payload_template')->nullable();
            $table->json('custom_headers')->nullable();
            $table->boolean('hmac_enabled')->default(false);
            $table->string('hmac_secret')->nullable();
            $table->string('hmac_algorithm', 20)->default('sha256');
            $table->string('hmac_header_name', 100)->default('X-Signature-256');
            $table->string('hmac_format', 30)->default('hex'); // hex, base64, stripe
            $table->integer('max_retries')->default(3);
            $table->integer('retry_backoff_seconds')->default(2);
            $table->timestamps();

            $table->index(['endpoint_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callback_rules');
    }
};
