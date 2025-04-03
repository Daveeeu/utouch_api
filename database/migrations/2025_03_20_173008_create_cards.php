<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Kártya egyedi azonosítója');
            $table->enum('status', ['inactive', 'active', 'expired'])->default('inactive')->comment('Kártya státusza');
            $table->foreignId('user_id')->nullable()->comment('Aktiváló felhasználó azonosítója');
            $table->foreignId('profile_id')->nullable()->comment('Kapcsolódó profil azonosítója');
            $table->timestamp('activated_at')->nullable()->comment('Aktiválás időpontja');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
