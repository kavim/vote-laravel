<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('elector_id')->constrained();
            $table->foreignId('election_id')->constrained();

            //O "mais correto" seria criar uma tabela de lista de votos para cada candidato por eleitor, mas como não é o foco do curso, vamos deixar assim
//            $table->foreignId('votelist_id')->constrained();

            // Dessa forma podemos ter uma lista de votos para cada eleitor, e garantir q não vamos criar valores duplicados nem varias linha desnecessárias na tabela de votos, e tbm eu vou saber replicar na versão spring boot
            $table->string('presidente_number', 5);
            $table->string('governador_number', 5);
            $table->string('senador_number', 5);
            $table->string('deputado_federal_number', 5);
            $table->string('deputado_estadual_number', 5);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('votes');
    }
};
