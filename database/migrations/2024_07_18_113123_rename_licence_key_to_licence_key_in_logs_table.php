<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->renameColumn('licenceKey', 'licence_key');
        });
    }

    public function down()
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->renameColumn('licence_key', 'licenceKey');
        });
    }
};