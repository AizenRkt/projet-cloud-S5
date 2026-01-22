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
        Schema::create('signalement', function (Blueprint $table) {
            $table->id('id_signalement');
            $table->foreignId('id_utilisateur')->nullable()->constrained('utilisateur', 'id_utilisateur')->nullOnDelete();
            $table->double('latitude');
            $table->double('longitude');
            $table->timestamp('date_signalement')->useCurrent();
            $table->enum('statut', ['nouveau', 'en cours', 'termine'])->default('nouveau');
            $table->double('surface_m2')->nullable();
            $table->double('budget')->nullable();
            $table->foreignId('id_entreprise')->nullable()->constrained('entreprise', 'id_entreprise')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signalement');
    }
};
