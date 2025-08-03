<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('currency');
            $table->decimal('balance', 20, 8)->default(0);
            $table->decimal('locked', 20, 8)->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'currency']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type'); // deposit, withdrawal, trade, transfer
            $table->decimal('amount', 20, 8);
            $table->string('currency');
            $table->string('status'); // pending, completed, failed, cancelled
            $table->json('details')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('user_wallets');
    }
};
