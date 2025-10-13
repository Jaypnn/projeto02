<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // Registrar um usuário
    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());
        return response()->json(['message' => 'Registered successfully'], 201);
    }

    // Login que retorna token
    public function loginWithToken(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token, 
            'user' => $user
        ]);
    }

    // Logout por token: revoga o token atual (se existir)
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    // Login por sessão (cookie)
    // REquer que o frontend chame antes: GET /sanctum/csrf-cookie
    public function loginWithSession(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Só regenera a sessão se ela existir (evita erro em testes)
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json(['message' => 'Logged in successfully']);
    }

    // Logout por sessão (cookie)
    public function logoutFromSession(Request $request)
    {
        Auth::guard('web')->logout();

        // Só manipula sessão se ela existir (evita erro em testes)
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }
}