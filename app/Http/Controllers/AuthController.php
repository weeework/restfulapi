<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use JWTAuthException;

use App\User;

class AuthController extends Controller
{
    public function store(Request $request)
    {
        //
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:5'
        ]);

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password)
        ]);

        $credentials = [
            'email' => $email,
            'password' => $password
        ];

        if ($user->save()) {

            $token = null;
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'message' => 'Email or Password are incorrect',
                    ], 404);
                }
            } catch (JWTAuthException $e) {
                return response()->json([
                    'message' => 'failed_to_create_token',
                ],404);
            }

            $user->signin = [
                'href' => 'api/v1/user/signin',
                'method' => 'POST',
                'params' => 'email, password'
            ];
            $response = [
                'message' => 'User created',
                'user' => $user,
                'token' => $token
            ];
            return response()->json($response, 201);
        }

        $response = [
            'message' => 'An error occurred'
        ];

        return response()->json($response, 404);

    }

    public function signin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:5'
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        if ($user = User::where('email', $email)->first()){
            $credentials = [
                'email' => $email,
                'password' => $password
            ];

            $token = null;
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'message' => 'Email or Password are incorrect',
                    ], 404);
                }
            } catch (JWTAuthException $e) {
                return response()->json([
                    'message' => 'failed_to_create_token',
                ],404);
            }

            $response = [
                'message' => 'User signin',
                'user' => $user,
                'token' => $token
            ];
            return response()->json($response, 201);

        }

        $response = [
            'message' => 'An error occurred'
        ];

        return response()->json($response, 404);


    }
}
