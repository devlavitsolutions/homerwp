<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(
            'users',
            function (Blueprint $table) {
                $table->dropColumn('license_key');
                $table->dropColumn('tokens_count');
            }
        );
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(
            'users',
            function (Blueprint $table) {
                $table->string('license_key')->nullable();
                $table->integer('tokens_count')->default(0);
            }
        );
    }
};
