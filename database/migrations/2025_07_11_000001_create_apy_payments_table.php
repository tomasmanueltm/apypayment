<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('apy_payments', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('reference')->nullable();
            $table->string('merchantTransactionId')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->date('dueDate')->default(now()->days(30))->nullable();
            $table->enum('status', ['Pending', 'Success', 'Requested'])->nullable()->default('Pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('apy_payments');
    }
};