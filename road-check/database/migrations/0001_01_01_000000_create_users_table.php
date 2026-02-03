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
        Schema::create('role', function (Blueprint $table) {
            $table->increments('id_role');
            $table->string('nom', 20)->unique();
        });

        Schema::create('utilisateur', function (Blueprint $table) {
            $table->increments('id_utilisateur');
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->string('firebase_uid', 128)->unique();
            $table->string('nom', 100)->nullable();
            $table->string('prenom', 100)->nullable();
            $table->unsignedInteger('id_role');
            $table->boolean('bloque')->default(false);
            $table->timestamp('date_creation')->useCurrent();

            $table->foreign('id_role')->references('id_role')->on('role');
        });

        Schema::create('session', function (Blueprint $table) {
            $table->increments('id_session');
            $table->unsignedInteger('id_utilisateur');
            $table->string('token', 255)->unique();
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_expiration');

            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur');
        });

        Schema::create('entreprise', function (Blueprint $table) {
            $table->increments('id_entreprise');
            $table->string('nom', 150);
        });

        Schema::create('type_signalement', function (Blueprint $table) {
            $table->increments('id_type_signalement');
            $table->string('nom', 100);
            $table->string('icon', 100)->nullable();
        });

        Schema::create('signalement', function (Blueprint $table) {
            $table->increments('id_signalement');
            $table->unsignedInteger('id_type_signalement');
            $table->unsignedInteger('id_entreprise')->nullable();
            $table->unsignedInteger('id_utilisateur')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->text('description')->nullable();
            $table->double('surface_m2')->nullable();
            $table->double('budget')->nullable();
            $table->timestamp('date_signalement')->useCurrent();

            $table->foreign('id_type_signalement')->references('id_type_signalement')->on('type_signalement');
            $table->foreign('id_entreprise')->references('id_entreprise')->on('entreprise');
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur');

            $table->index(['latitude', 'longitude'], 'idx_signalement_position');
        });

        Schema::create('signalement_type_status', function (Blueprint $table) {
            $table->increments('id_signalement_type_status');
            $table->string('code', 20)->unique();
            $table->string('libelle', 20);
        });

        Schema::create('signalement_status', function (Blueprint $table) {
            $table->increments('id_signalement_status');
            $table->unsignedInteger('id_signalement');
            $table->unsignedInteger('id_signalement_type_status');
            $table->timestamp('date_modification')->useCurrent();

            $table->foreign('id_signalement')->references('id_signalement')->on('signalement');
            $table->foreign('id_signalement_type_status')->references('id_signalement_type_status')->on('signalement_type_status');
        });

        Schema::create('photo_signalement', function (Blueprint $table) {
            $table->increments('id_photo');
            $table->unsignedInteger('id_signalement');
            $table->string('path', 255);

            $table->foreign('id_signalement')->references('id_signalement')->on('signalement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_signalement');
        Schema::dropIfExists('signalement_status');
        Schema::dropIfExists('signalement_type_status');
        Schema::dropIfExists('signalement');
        Schema::dropIfExists('type_signalement');
        Schema::dropIfExists('entreprise');
        Schema::dropIfExists('session');
        Schema::dropIfExists('utilisateur');
        Schema::dropIfExists('role');
    }
};
