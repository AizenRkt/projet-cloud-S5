<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTentativeConnexionTable extends Migration
{
    public function up()
    {
        Schema::create('tentative_connexion', function (Blueprint $table) {
            $table->id('id_tentative');
            $table->unsignedBigInteger('id_utilisateur');
            $table->timestamp('date_tentative')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->boolean('succes');
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tentative_connexion');
    }
}
