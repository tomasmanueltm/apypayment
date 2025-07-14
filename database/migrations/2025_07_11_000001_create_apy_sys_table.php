<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('apy_sys', function (Blueprint $table) {
            $table->id('idPayment');
            $table->uuid('id')->nullable();
            $table->string('merchantTransactionId')->nullable();;
            $table->string('type')->nullable();;
            $table->string('operation')->nullable();;
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('status')->nullable();
            $table->text('description')->nullable();
            $table->boolean('disputes')->default(false)->nullable();
            $table->decimal('applicationFeeAmount', 10, 2)->default(0);
            $table->string('paymentMethod')->nullable();;
            $table->json('options')->nullable();
            $table->json('reference')->nullable();
            $table->timestamp('createdDate')->nullable();;
            $table->timestamp('updatedDate')->nullable();;
            $table->timestamps();

            $table->index('merchantTransactionId');
            $table->index('status');
            $table->index('createdDate');
        });
    }

    public function down()
    {
        Schema::dropIfExists('apy_sys');
    }
};