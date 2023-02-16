<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Helpers\Response;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    //

    function __construct()
    {
        $this->middleware('auth:api', ['only' => ['me', 'logout', 'resetPassword']]);
    }

    public function login($email = '', $password = '', $use_this = false)
    {
        if ($use_this) {
            $credentials = ['email' => $email, 'password' => $password];

            if (!$token = auth()->attempt($credentials)) {
                return Response::json(401, 'Unauthorized', []);
            }

            return Response::json(200, 'Register Success', [
                'token' => $token,
                'token_type' => 'bearer',
                'expired_id' => auth()->factory()->getTTL() * 60
            ]);
        } else {
            $credentials = request(['email', 'password']);

            if (!$token = auth()->attempt($credentials)) {
                return Response::json(401, 'Unauthorized', []);
            }

            return Response::json(200, 'Login Success', [
                'token' => $token,
                'token_type' => 'bearer',
                'expired_id' => auth()->factory()->getTTL() * 60
            ]);
        }
    }

    public function register(Request $request, User $user)
    {

        $data =  $request->all();
        $password_old = $data['password'];

        $data['password'] = bcrypt($data['password']);

        $store = ApiHelper::store($user, [
            'name' => ['required', 'min:4'],
            'email' => ['email', 'required', 'unique:' . User::class],
            'password' => ['min:8', 'required']
        ], $data);

        if (!$store['status']) {
            return Response::json(400, $store['message'], $store['data']);
        }

        return $this->login($data['email'], $password_old, true);
    }

    public function logout()
    {
        auth()->logout();
        return Response::json(200, 'Success Logout');
    }

    public function me()
    {
        return Response::json(200, 'Success Get Data', auth()->user());
    }

    public function resetPassword(Request $request)
    {
        try {
            $data = $request->all();

            $validator  = Validator::make($data, [
                'old_password' => ['required', 'current_password:api'],
                'new_password' => ['required', 'min:8']
            ]);

            if ($validator->fails()) {
                return Response::json(422, 'Validation Error', $validator->errors());
            }

            $user = User::find(auth()->user()->id);
            $updated = $user->update(['password' => bcrypt($data['new_password'])]);

            if ($updated) {
                return Response::json(200, 'Success Change Password', $updated);
            } else if (!$updated) {
                return Response::json(500, "Data Can't Save", $updated);
            }
        } catch (Exception $e) {
            return Response::json(500, "Internal Server Error", $e->getMessage());
        }
    }
}
