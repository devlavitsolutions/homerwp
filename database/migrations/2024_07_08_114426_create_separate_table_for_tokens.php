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
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->index('user_id');
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->integer('free_tokens_used')->default(0);
            $table->integer('tokens_count')->default(0);
            $table->timestamp('last_used')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('cid')->nullable();
            $table->index('cid');
            $table->dropColumn('tokens_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->dropForeign('lists_user_id_foreign');
            $table->dropIndex('lists_user_id_index');
        });
        Schema::dropIfExists('tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('lists_cid_index');
            $table->dropColumn('cid');
            $table->integer('tokens_count')->default(0);
        });
    }
};
