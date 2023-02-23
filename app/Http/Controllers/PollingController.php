<?php

namespace App\Http\Controllers;

use App\Events\PollingEvent;
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
            return ApiHelper::resultPolling($polling);
        });
        return ApiHelper::show($data);
    }

    public function store(Request $request, Polling $polling, Choise $choise)
    {
        $data = $request->all();
        $pisah = json_decode($data['choises']);

        if (count($pisah) == 1 || count($pisah) <= 1) {
            return Response::json(422, 'Choises minimal nya adalah 2', $pisah);
        }

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

        $data = Polling::where('id', $polling_id)->with(['choises'])->first();
        $result = ApiHelper::show($data);
        broadcast(new PollingEvent(array($data)));
        return $result;
    }

    public function show(Polling $polling)
    {
        $data = $polling->load(['choises'  => function (Builder $q) {
            $q->with('votes.user')->withCount('votes');
        }, 'choises.votes'])->loadCount(['votes', 'choises']);


        $data = collect([$data])->map(function ($polling) {
            return ApiHelper::resultPolling($polling);
        });;
        $data = $data->first();

        return ApiHelper::show($data);
    }

    public function destroy(Polling $polling)
    {
        return ApiHelper::destroy($polling);
    }

    public function myPollings()
    {
        $user_id = auth()->user()->id;
        $data = Polling::where('user_id', $user_id)->with(['choises'  => function (Builder $q) {
            $q->with('votes.user')->withCount('votes');
        }, 'choises.votes'])->withCount(['votes', 'choises'])->get()->map(function ($polling) {
            return ApiHelper::resultPolling($polling);
        });
        return ApiHelper::show($data);
    }
}
