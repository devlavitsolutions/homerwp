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
        Schema::dropIfExists('logs');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(
            'logs',
            function (Blueprint $table) {
                $table->id();
                $table->string('keywords');
                $table->string('website');
                $table->string('licenceKey');
                $table->text('response');
                // created_at and updated_at fields
                $table->timestamps();
            }
        );
    }
};
