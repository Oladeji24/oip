<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('bot_trades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('market');
            $table->string('symbol');
            $table->string('side');
            $table->decimal('entry', 20, 8);
            $table->decimal('exit', 20, 8)->nullable();
            $table->decimal('size', 20, 8);
            $table->decimal('profit', 20, 8)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->json('entry_order')->nullable();
            $table->json('exit_order')->nullable();
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('bot_trades');
    }
};
