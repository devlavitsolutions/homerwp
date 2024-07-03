<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private function createResponseWithUserAndToken(User $user) {
        $token = $user->createToken('homertoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 200);
    }

    public function register(Request $request) {
        $fields = $request->validate(([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password'=> 'required|string|confirmed',
        ]));

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
        ]);

        return $this->createResponseWithUserAndToken($user);
    }

    public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password'=> 'required|string',
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response(['message'=> 'Username or password is wrong.'], 401);
        }

        return $this->createResponseWithUserAndToken($user);
    }

    public function logout(Request $request) {
        auth()->user()->tokens()->delete();

        return response(['message'=> 'Successfully logged out!'], 200);
    }
}
