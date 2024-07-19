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
        Schema::create('activations', function (Blueprint $table) {
            $table->id();
            $table->index('user_id');
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('license_key');
            $table->string('website');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_premium')->default(false);
            $table->index('email');
            $table->index('license_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activations', function (Blueprint $table) {
            $table->dropForeign('activations_user_id_foreign');
            $table->dropIndex('activations_user_id_index');
        });
        Schema::dropIfExists('activations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_premium');
            $table->dropIndex('users_email_index');
            $table->dropIndex('users_license_key_index');
        });
    }
};
