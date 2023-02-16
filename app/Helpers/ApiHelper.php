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
        // try {
        //     DB::
        // } catch (Exception $e) {

        //     return Response::json(500, 'Server Interna Error', $e->getMessage());
        // }

        $delete = $model->delete();
        if ($delete) {
            return Response::json(200, "Success Deleted");
        } else {
            return Response::json(500, 'Data Tidak Berhasil Di Hapus');
        }
    }
}
