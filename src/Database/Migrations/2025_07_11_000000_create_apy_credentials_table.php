<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('apy_credentials', function (Blueprint $table) {
            $table->id();
            $table->longText('token')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->string('grant_type')->nullable();
            $table->string('resource')->nullable();
            $table->string('auth_url')->nullable();
            $table->string('api_url')->nullable();
            $table->string('expires_on')->nullable();
            $table->string('expires_in')->nullable();
            $table->boolean('istoken')->default(false)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('apy_credentials');
    }
};