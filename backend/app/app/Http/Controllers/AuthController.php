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
        
        // Faz login automático após registro
        Auth::login($user);
        
        // Regenera a sessão se existir (para evitar erro em testes)
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        
        return response()->json([
            'message' => 'Registered successfully', 
            'user' => $user
        ], 201);
    }



    // Login por sessão (cookie)
    // Requer que o frontend chame antes: GET /sanctum/csrf-cookie
    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Só regenera a sessão se ela existir (evita erro em testes)
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user = Auth::user();
        
        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $user
        ]);
    }

    // Logout por sessão (cookie)
    public function logout(Request $request)
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