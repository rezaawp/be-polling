<?php

namespace App\Helpers;

class Response
{
    public static function json($status, $message, $data = [])
    {
        $status_boolean = $status == 200 || $status == 201 ? true : false;

        return response()->json([
            'status' => $status_boolean,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function array($status, $message, $data = [])
    {
        $status_boolean = $status == 200 || $status == 201 ? true : false;

        return [
            'status' => $status_boolean,
            'message' => $message,
            'data' => $data
        ];
    }
}
