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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->comment('Profil neve');
            $table->enum('type', ['personal', 'business', 'other'])->default('personal')->comment('Profil típusa');
            $table->text('description')->nullable()->comment('Profil leírása');
            $table->string('image')->nullable()->comment('Profil kép URL-je');
            $table->json('contact_info')->nullable()->comment('Kapcsolattartási adatok JSON formátumban');
            $table->json('social_links')->nullable()->comment('Közösségi média linkek JSON formátumban');
            $table->integer('visits')->default(0)->comment('Profil látogatások száma');
            $table->boolean('is_public')->default(true)->comment('Publikus-e a profil');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
