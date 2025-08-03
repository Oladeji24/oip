<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create("price_alerts", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->string("symbol");
            $table->string("condition");  // "above" or "below"
            $table->decimal("price", 20, 8);
            $table->boolean("triggered")->default(false);
            $table->timestamp("triggered_at")->nullable();
            $table->decimal("triggered_price", 20, 8)->nullable();
            $table->timestamps();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
        });
    }

    public function down() {
        Schema::dropIfExists("price_alerts");
    }
};
