<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiHelper
{
    public static function store($model, $validates, $data_store)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data_store, $validates);

            if ($validator->fails()) {
                return Response::array(422, 'Validation Error', $validator->errors());
            }

            DB::commit();

            $store = $model::create($data_store);

            if ($store) {
                return Response::array(201, 'Data Created', $store);
            } else if (!$store) {
                DB::rollBack();
                return Response::array(500, 'Data Tidak Dapat Dimasukan Ke DB', $store);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return Response::array(500, 'Server Internal Error', $e->getMessage());
        }
    }

    public static function update($model, $validates, $data_updated)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data_updated, $validates);

            if ($validator->fails()) {
                return Response::array(422, 'Validation Error');
            }

            DB::commit();

            $update = $model->update($data_updated);

            if ($update) {
                return Response::json(200, 'Success Updated', $update);
            } else {
                DB::rollBack();
                return Response::json(500, 'Gagal Update data', $update);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return Response::json(500, 'Server Interna Error', $e->getMessage());
        }
    }

    public static function show($model)
    {
        if ($model) {
            return Response::json(200, 'Success Get Data', $model);
        } else {
            return Response::json(500, 'Data gagal disimpan', $model);
        }
    }

    public static function destroy($model)
    {
        $delete = $model->delete();
        if ($delete) {
            return Response::json(200, "Success Deleted");
        } else {
            return Response::json(500, 'Data Tidak Berhasil Di Hapus');
        }
    }

    public static function resultPolling($polling)
    {
        if (strtotime($polling->deadline) < time()) {
            $polling->is_deadline = true;
        } else {
            $polling->is_deadline = false;
        }
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
    }
}
