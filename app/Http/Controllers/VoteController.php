<?php

namespace App\Http\Controllers;

use App\services\VoteService;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function __construct(
        public VoteService $voteService = new VoteService()
    ){}

    /**
     * @throws \Exception
     */
    public function finish(): \Illuminate\Http\JsonResponse
    {
        $votes = $this->voteService->finish();

        return response()->json([
            'message' => 'Votação encerrada',
            'finished_at' => $votes['finished_at'],
            'votes' => [
                'presidente' => $votes['presidente'],
                'governador' => $votes['governador'],
                'senador' => $votes['senador'],
                'deputado_federal' => $votes['deputado_federal'],
                'deputado_estadual' => $votes['deputado_estadual'],
            ],
        ], 200);
    }
}
