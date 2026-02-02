<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('role')) {
            Schema::create('role', function (Blueprint $table) {
                $table->id('id_role');
                $table->string('nom', 20)->unique();
            });

            DB::table('role')->insert([
                ['nom' => 'Administrateur'],
                ['nom' => 'Utilisateur'],
                ['nom' => 'Moderateur'],
            ]);
        }

        if (!Schema::hasTable('utilisateur')) {
            Schema::create('utilisateur', function (Blueprint $table) {
                $table->id('id_utilisateur');
                $table->string('email', 100)->unique();
                $table->string('firebase_uid', 128)->unique();
                $table->string('nom', 100)->nullable();
                $table->string('prenom', 100)->nullable();
                $table->unsignedBigInteger('id_role');
                $table->boolean('bloque')->default(false);
                $table->timestamp('date_creation')->useCurrent();
                $table->timestamps();

                $table->foreign('id_role')->references('id_role')->on('role');
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utilisateur');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
