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
                $table->string('name');
                $table->dropColumn('is_admin');
                $table->dropColumn('is_disabled');
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
                $table->dropColumn('name');
                $table->string('is_admin')->default(false);
                $table->string('is_disabled')->default(false);
            }
        );
    }
};
