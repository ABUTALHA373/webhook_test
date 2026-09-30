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
        Schema::create('webhook_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('endpoint_id')->constrained('endpoints')->onDelete('cascade');
            $table->string('method', 15)->index();
            $table->text('url');
            $table->string('path', 500)->default('/');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('headers');
            $table->json('query_params')->nullable();
            $table->longText('raw_body')->nullable();
            $table->json('parsed_body')->nullable();
            $table->string('content_type', 255)->nullable();
            $table->unsignedBigInteger('content_length')->nullable();
            $table->integer('response_status')->default(200)->index();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->decimal('duration_ms', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['endpoint_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_requests');
    }
};
