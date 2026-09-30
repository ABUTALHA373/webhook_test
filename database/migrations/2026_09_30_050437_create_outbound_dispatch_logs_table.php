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
        Schema::create('outbound_dispatch_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('target_url');
            $table->string('method', 15)->default('POST');
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->integer('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->decimal('duration_ms', 10, 2)->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_dispatch_logs');
    }
};
