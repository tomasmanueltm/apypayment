<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('apy_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('method')->nullable();
            $table->string('hash')->nullable();
            $table->string('type')->nullable();
            $table->boolean('isActive')->default(false)->nullable();
            $table->boolean('isDefault')->default(false)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('apy_methods');
    }
};