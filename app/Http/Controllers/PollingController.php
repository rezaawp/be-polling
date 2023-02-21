<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Helpers\Response;
use App\Models\Choise;
use App\Models\Polling;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;

class PollingController extends Controller
{

    function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $data = Polling::with(['choises'  => function (Builder $q) {
            $q->with('votes.user')->withCount('votes');
        }, 'choises.votes'])->withCount(['votes', 'choises'])->get()->map(function ($polling) {
            for ($i = 0; $i < $polling['choises_count']; $i++) {
                $polling->choises[$i]->percentage = round($polling->choises[$i]['votes_count'] == 0 ? 0 : $polling->choises[$i]['votes_count'] / $polling['votes_count'] * 100);
                if ($polling->user_id == auth()->user()->id) {
                    $polling->my_poll = true;
                } else if ($polling->user_id !== auth()->user()->id) {
                    unset($polling->choises[$i]->votes);
                    $polling->my_poll = false;
                }
            }

            return $polling;
        });
        return ApiHelper::show($data);
    }

    public function store(Request $request, Polling $polling, Choise $choise)
    {
        $data = $request->all();
        $pisah = explode("\n", $data['choises']);
        $data['user_id'] = auth()->user()->id;

        $validator = Validator::make($data, [
            'thumbnail' => [
                File::image()
                    ->min(0)
                    ->max(12 * 1024)
            ]
        ]);

        if ($validator->fails()) {
            return Response::json(422, 'Validation Error', $validator->errors());
        }
        $nama_gambar = time() . '.' .  $request->file('thumbnail')->extension();
        $request->file('thumbnail')->move(public_path(''), $nama_gambar);

        $data['thumbnail'] = $nama_gambar;

        $store = ApiHelper::store($polling, [
            'question' => ['required', 'min:4'],
        ], $data);

        if (!$store['status']) {
            return Response::json(400, $store['message'], $store['data']);
        }


        $polling_id = $store['data']['id'];
        foreach ($pisah as $c) {
            $data_store_choise = [
                'polling_id' => $polling_id,
                'choise'    => $c
            ];

            $store_choise = ApiHelper::store($choise, [
                'polling_id' => ['required'],
                'choise' => ['required']
            ], $data_store_choise);

            if (!$store_choise) {
                return Response::json(400, $store_choise['message']);
                break;
            }
        }

        $result = Polling::where('id', $polling_id)->with(['choises'])->first();
        return ApiHelper::show($result);
        return Response::json(200, $store['message'], $store['data']);
    }

    public function show(Polling $polling)
    {
        $data = $polling->load(['choises'  => function (Builder $q) {
            $q->with('votes.user')->withCount('votes');
        }, 'choises.votes'])->loadCount(['votes', 'choises']);


        $data = collect([$data])->map(function ($polling) {
            for ($i = 0; $i < $polling['choises_count']; $i++) {
                $polling->choises[$i]->percentage = round($polling->choises[$i]['votes_count'] == 0 ? 0 : $polling->choises[$i]['votes_count'] / $polling['votes_count'] * 100);
                if ($polling->user_id == auth()->user()->id) {
                    $polling->my_poll = true;
                } else if ($polling->user_id !== auth()->user()->id) {
                    unset($polling->choises[$i]->votes);
                    $polling->my_poll = false;
                }
            }

            return $polling;
        });;
        $data = $data->first();

        return ApiHelper::show($data);
    }

    public function destroy(Polling $polling)
    {
        return ApiHelper::destroy($polling);
    }
}
