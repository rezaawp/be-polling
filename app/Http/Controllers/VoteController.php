<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Helpers\Response;
use App\Models\Polling;
use App\Models\Vote;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    function __construct()
    {
        $this->middleware('auth:api', ['only' => 'store']);
    }

    public function store(Request $request, Vote $vote)
    {
        $data = $request->all();
        $data['user_id'] = auth()->user()->id;

        $cari_vote = Vote::where('user_id', $data['user_id'])->where('polling_id', $data['polling_id'])->first();
        $cari_polling = Polling::find($data['polling_id']);

        if (strtotime($cari_polling['deadline']) < time()) {
            return Response::json(400, 'Sudah melewati deadline');
        }
        
        if ($cari_vote) {
            return Response::json(422, 'sudah pernah vote');
        } else if (!$cari_vote) {
            $store = ApiHelper::store($vote, [
                'user_id' => ['required', 'numeric'],
                'polling_id' => ['required', 'numeric'],
                'choise_id' => ['numeric', 'required']
            ], $data);

            if ($store['status']) {
                return Response::json(200, $store['message'], $store['data']);
            } else if (!$store['status']) {
                return Response::json(500, $store['message'], $store['data']);
            }
        }
    }
}
