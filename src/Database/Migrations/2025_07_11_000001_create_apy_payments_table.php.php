<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('apy_payments', function (Blueprint $table) {
            $table->id('idPayment');
            $table->uuid('id')->nullable();
            $table->string('merchantTransactionId');
            $table->string('type');
            $table->string('operation');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('status');
            $table->text('description')->nullable();
            $table->boolean('disputes')->default(false);
            $table->decimal('applicationFeeAmount', 10, 2)->default(0);
            $table->string('paymentMethod');
            $table->json('options')->nullable();
            $table->json('reference')->nullable();
            $table->timestamp('createdDate');
            $table->timestamp('updatedDate');
            $table->timestamps();

            $table->index('merchantTransactionId');
            $table->index('status');
            $table->index('createdDate');
        });
    }

    public function down()
    {
        Schema::dropIfExists('apy_payments');
    }
};