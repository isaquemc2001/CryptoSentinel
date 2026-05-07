<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_coins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('symbol')->unique()->comment('exchange pair, e.g. BTCUSDT');
            $table->string('base_asset', 32);
            $table->string('quote_asset', 32)->default('USDT');
            $table->string('label')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_coins');
    }
};
