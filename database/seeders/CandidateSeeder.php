<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CandidateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $election = Election::latest()->first();

        Candidate::factory()->count(5)->create(['election_id' => $election->id, 'position' => Candidate::POSITION_PRESIDENTE]);
        Candidate::factory()->count(60)->create(['election_id' => $election->id, 'position' => Candidate::POSITION_GOVERNADOR]);
        Candidate::factory()->count(140)->create(['election_id' => $election->id, 'position' => Candidate::POSITION_SENADOR]);
        Candidate::factory()->count(140)->create(['election_id' => $election->id, 'position' => Candidate::POSITION_DEPUTADO_FEDERAL]);
        Candidate::factory()->count(140)->create(['election_id' => $election->id, 'position' => Candidate::POSITION_DEPUTADO_ESTADUAL]);
    }
}
