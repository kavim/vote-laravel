<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Elector;
use App\services\VoteService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $election = Election::latest()->first();

        $electors = Elector::get();

        $presidentes = Candidate::where('position', Candidate::POSITION_PRESIDENTE)->where('election_id', $election->id)->get();
        $governadores = Candidate::where('position', Candidate::POSITION_GOVERNADOR)->where('election_id', $election->id)->get();
        $senadores = Candidate::where('position', Candidate::POSITION_SENADOR)->where('election_id', $election->id)->get();
        $deputadosFederais = Candidate::where('position', Candidate::POSITION_DEPUTADO_FEDERAL)->where('election_id', $election->id)->get();
        $deputadosEstaduais = Candidate::where('position', Candidate::POSITION_DEPUTADO_ESTADUAL)->where('election_id', $election->id)->get();

        $voteService = new VoteService();

        $electors->each(function ($elector) use ($presidentes, $governadores, $senadores, $deputadosFederais, $deputadosEstaduais, $election, $voteService) {
            $voteService->vote($elector, $election, [
                'presidente_number' => $presidentes->random()->number,
                'governador_number' => $governadores->random()->number,
                'senador_number' => $senadores->random()->number,
                'deputado_federal_number' => $deputadosFederais->random()->number,
                'deputado_estadual_number' => $deputadosEstaduais->random()->number,
            ]);
        });
    }
}
