<?php

namespace App\services;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Elector;
use App\Models\Vote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VoteService
{
    public function vote(Elector $elector, Election $election, array $candidates): bool
    {
        $elector = Elector::findOrFail($elector->id);
        $election = Election::findOrFail($election->id);

        if ($elector->hasVoted($election)) {
            throw new \Exception('Elector has already voted');
        }

        Vote::create([
            'elector_id' => $elector->id,
            'election_id' => $election->id,
            'presidente_number' => $candidates['presidente_number'],
            'governador_number' => $candidates['governador_number'],
            'senador_number' => $candidates['senador_number'],
            'deputado_federal_number' => $candidates['deputado_federal_number'],
            'deputado_estadual_number' => $candidates['deputado_estadual_number'],
        ]);

        return true;
    }

    public function getVotesGroupedByPosition(Election $election): array
    {
        $votes = DB::table('votes')->where('election_id', $election->id);

        $votesForPresidente = $votes->selectRaw('count(elector_id) as votes, presidente_number as number')
                ->groupBy('presidente_number')
                ->orderBy('votes', 'desc')->limit(5)->get();

        $votesForGovernador = $votes->selectRaw('count(elector_id) as votes, governador_number as number')
                ->groupBy('governador_number')
                ->orderBy('votes', 'desc')->limit(1)->get();

        $votesForSenador = $votes->selectRaw('count(elector_id) as votes, senador_number as number')
                ->groupBy('senador_number')
                ->orderBy('votes', 'desc')->limit(1)->get();

        $votesForDeputadoFederal = $votes->selectRaw('count(elector_id) as votes, deputado_federal_number as number')
                ->groupBy('deputado_federal_number')
                ->orderBy('votes', 'desc')->limit(1)->get();

        $votesForDeputadoEstadual= $votes->selectRaw('count(elector_id) as votes, deputado_estadual_number as number')
                ->groupBy('deputado_estadual_number')
                ->orderBy('votes', 'desc')->limit(1)->get();

        return [
            'presidente' => $votesForPresidente,
            'governador' => $votesForGovernador,
            'senador' => $votesForSenador,
            'deputado_federal' => $votesForDeputadoFederal,
            'deputado_estadual' => $votesForDeputadoEstadual,
        ];
    }

    public function finish(): array
    {
        $election = Election::where('finished_at', null)->latest()->first();

        if (!$election) {
            throw new \Exception('No election to finish');
        }

        $election->finished_at = now();
        $election->save();

        $winners = $this->getWinners($election);
        $winners['finished_at'] = $election->finished_at->toDateTimeString();

        return $winners;
    }

    public function getWinners(Election $election): array
    {
        $votes = $this->getVotesGroupedByPosition($election);

        $winners = [];

        $winners['presidente'] = $this->calcPresidenteWinner($votes['presidente'], $election);
        $winners['governador'] = $votes['governador'][0]->number;
        $winners['senador'] = $votes['senador'][0]->number;
        $winners['deputado_federal'] = $votes['deputado_federal'][0]->number;
        $winners['deputado_estadual'] = $votes['deputado_estadual'][0]->number;

        return $winners;
    }

    private function calcPresidenteWinner(Collection $presidentes, Election $election): string
    {
        // 1 oberter quantidade total de votos
        $totalVotes = Vote::where('election_id', $election->id)->count();

        $firstCandidateVotes = $presidentes[0]->votes;
        $secondCandidateVotes = $presidentes[1]->votes;

        $firstCandidatePercentage = $firstCandidateVotes / $totalVotes;
        $secondCandidatePercentage = $secondCandidateVotes / $totalVotes;

        if ($firstCandidatePercentage > $secondCandidatePercentage) {
            return $presidentes[0]->number;
        }

        if ($secondCandidatePercentage > $firstCandidatePercentage) {
            return $presidentes[1]->number;
        }

        $this->makeSecondTurn($election, $presidentes);

        return "Empate presidencial. Votação para segundo turno";
    }

    private function makeSecondTurn(Election $election, $candidates): void
    {
       $election2 = Election::firstOrCreate([
           'turn' => 2,
           'year' => $election->year,
       ]);

//       foreach ($candidates as $candidate) {
//           $candidate->election_id = $election2->id;
//           $candidate->save();
//       }

       DB::table('candidates')
                ->whereIn('number', $candidates->pluck('number'))
                ->update(['election_id' => $election2->id]);
    }
}
