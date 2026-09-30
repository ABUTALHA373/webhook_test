<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endpoints', function (Blueprint $table) {
            // JSON array of required param definitions.
            // Each entry: { "name": "token", "source": "query|body|header", "type": "string|number|email", "error_message": "..." }
            $table->json('required_params')->nullable()->after('conditional_rules');
        });
    }

    public function down(): void
    {
        Schema::table('endpoints', function (Blueprint $table) {
            $table->dropColumn('required_params');
        });
    }
};
