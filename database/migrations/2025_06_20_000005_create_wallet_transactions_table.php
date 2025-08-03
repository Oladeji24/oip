<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create("wallet_transactions", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->string("type");  // deposit, withdrawal, trade, fee, etc.
            $table->decimal("amount", 18, 8);
            $table->string("currency");
            $table->string("status");  // pending, completed, failed, etc.
            $table->json("details")->nullable();
            $table->string("transaction_id")->nullable()->unique();
            $table->timestamps();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
        });
    }

    public function down() {
        Schema::dropIfExists("wallet_transactions");
    }
};
