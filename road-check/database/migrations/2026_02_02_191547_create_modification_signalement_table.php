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
        Schema::create('modification_signalement', function (Blueprint $table) {
            $table->id('id_modification');
            $table->unsignedInteger('id_signalement');
            $table->unsignedInteger('id_utilisateur');
            $table->string('statut', 20);
            $table->double('budget')->nullable();
            $table->double('surface_m2')->nullable();
            $table->unsignedInteger('id_entreprise')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('date_modification')->useCurrent();

            $table->foreign('id_signalement')->references('id_signalement')->on('signalement')->onDelete('cascade');
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur');
            $table->foreign('id_entreprise')->references('id_entreprise')->on('entreprise');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modification_signalement');
    }
};
