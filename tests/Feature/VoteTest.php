<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Elector;
use App\services\VoteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->electors = Elector::factory()->count(100)->create();
        $this->election = Election::factory()->create();

        $this->artisan('db:seed', ['--class' => 'CandidateSeeder']);

        $this->voteService = new VoteService();
    }

    public function test_elector_can_place_order()
    {
        $elector = $this->electors->random();

        $presidentes = Candidate::where('position', Candidate::POSITION_PRESIDENTE)->where('election_id', $this->election->id)->first();
        $governadores = Candidate::where('position', Candidate::POSITION_GOVERNADOR)->where('election_id', $this->election->id)->first();
        $senadores = Candidate::where('position', Candidate::POSITION_SENADOR)->where('election_id', $this->election->id)->first();
        $deputadosFederais = Candidate::where('position', Candidate::POSITION_DEPUTADO_FEDERAL)->where('election_id', $this->election->id)->first();
        $deputadosEstaduais = Candidate::where('position', Candidate::POSITION_DEPUTADO_ESTADUAL)->where('election_id', $this->election->id)->first();

        $this->voteService->vote($elector, $this->election, [
            'presidente_number' => $presidentes->number,
            'governador_number' => $governadores->number,
            'senador_number' => $senadores->number,
            'deputado_federal_number' => $deputadosFederais->number,
            'deputado_estadual_number' => $deputadosEstaduais->number,
        ]);

        $this->assertDatabaseHas('votes', [
            'elector_id' => $elector->id,
            'election_id' => $this->election->id,
            'presidente_number' => $presidentes->number,
            'governador_number' => $governadores->number,
            'senador_number' => $senadores->number,
            'deputado_federal_number' => $deputadosFederais->number,
            'deputado_estadual_number' => $deputadosEstaduais->number,
        ]);
    }

    public function test_president_win_in_the_first_turn()
    {
        // todos os eleitores votam no mesmo candidato
        $presidente = Candidate::where('position', Candidate::POSITION_PRESIDENTE)->where('election_id', $this->election->id)->first();
        $governador = Candidate::where('position', Candidate::POSITION_GOVERNADOR)->where('election_id', $this->election->id)->inRandomOrder()->first();
        $senador = Candidate::where('position', Candidate::POSITION_SENADOR)->where('election_id', $this->election->id)->inRandomOrder()->first();
        $deputadoFederal = Candidate::where('position', Candidate::POSITION_DEPUTADO_FEDERAL)->where('election_id', $this->election->id)->inRandomOrder()->first();
        $deputadoEstadual = Candidate::where('position', Candidate::POSITION_DEPUTADO_ESTADUAL)->where('election_id', $this->election->id)->inRandomOrder()->first();

        $this->electors->each(function ($elector) use ($presidente, $governador, $senador, $deputadoFederal, $deputadoEstadual) {
            $this->voteService->vote($elector, $this->election, [
                'presidente_number' => $presidente->number,
                'governador_number' => $governador->number,
                'senador_number' => $senador->number,
                'deputado_federal_number' => $deputadoFederal->number,
                'deputado_estadual_number' => $deputadoEstadual->number,
            ]);
        });

        $this->getJson(route('api.election.finish'))
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Votação encerrada',
                'votes' => [
                    'presidente' => $presidente->number,
                    'governador' => $governador->number,
                    'senador' => $senador->number,
                    'deputado_federal' => $deputadoFederal->number,
                    'deputado_estadual' => $deputadoEstadual->number,
                ],
            ]);
    }

    public function test_president_go_second_turn_and_creates_second_turn_with_candidates()
    {
        // Pegar os 2 primeiros candidatos e aplicar a voto apenas para estes
        $presidentes = Candidate::where('position', Candidate::POSITION_PRESIDENTE)->where('election_id', $this->election->id)->limit(2)->get();
        $governador = Candidate::where('position', Candidate::POSITION_GOVERNADOR)->where('election_id', $this->election->id)->inRandomOrder()->first();
        $senador = Candidate::where('position', Candidate::POSITION_SENADOR)->where('election_id', $this->election->id)->inRandomOrder()->first();
        $deputadoFederal = Candidate::where('position', Candidate::POSITION_DEPUTADO_FEDERAL)->where('election_id', $this->election->id)->inRandomOrder()->first();
        $deputadoEstadual = Candidate::where('position', Candidate::POSITION_DEPUTADO_ESTADUAL)->where('election_id', $this->election->id)->inRandomOrder()->first();

        $this->electors->each(function ($elector) use ($presidentes, $governador, $senador, $deputadoFederal, $deputadoEstadual) {
            $this->voteService->vote($elector, $this->election, [
                'presidente_number' => $elector->id % 2 == 0 ? $presidentes[0]->number : $presidentes[1]->number,
                'governador_number' => $governador->number,
                'senador_number' => $senador->number,
                'deputado_federal_number' => $deputadoFederal->number,
                'deputado_estadual_number' => $deputadoEstadual->number,
            ]);
        });

        $response = $this->getJson(route('api.election.finish'));
        $election = $this->election->fresh();
        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Votação encerrada',
                'finished_at' => $election->finished_at,
                'votes' => [
                    'presidente' => 'Empate presidencial. Votação para segundo turno',
                    'governador' => $governador->number,
                    'senador' => $senador->number,
                    'deputado_federal' => $deputadoFederal->number,
                    'deputado_estadual' => $deputadoEstadual->number,
                ],
            ]);

        // double check
        // verifica se finalizou a eleição e criou o segundo turno
        $this->assertDatabaseHas('elections', [
            'id' => $election->id,
            'finished_at' => $election->finished_at,
        ]);

        $this->assertDatabaseHas('elections', [
            'finished_at' => null,
            'year' => $election->year,
            'turn' => 2,
        ]);

        // verifica se os candidatos do segundo turno tem o ELECTION_ID do segundo turno
        $secondElection = Election::where('year', $election->year)->where('turn', 2)->first();
        $this->assertDatabaseHas('candidates', [
            'election_id' => $secondElection->id,
            'number' => $presidentes[0]->number,
        ]);
        $this->assertDatabaseHas('candidates', [
            'election_id' => $secondElection->id,
            'number' => $presidentes[1]->number,
        ]);
    }
}
