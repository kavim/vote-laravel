<?php

namespace Database\Seeders;

use App\Models\Elector;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ElectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Elector::factory()->count(100)->create();
    }
}
