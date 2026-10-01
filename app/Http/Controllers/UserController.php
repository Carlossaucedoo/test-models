<?php

namespace App\Http\Controllers;

use App\Models\Token;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller
{
    /**
     * Devuelve los 10 primeros usuarios.
     */
    public function get(): JsonResponse
    {
        try {
            $users = User::orderBy('id')->limit(10)->get();

            return self::response(true, 'Lista de los 10 primeros usuarios.', $users);
        } catch (Throwable $e) {
            report($e);

            return self::response(false, 'Error al obtener los usuarios.', null, 500);
        }
    }

    /**
     * Crea un usuario nuevo con la contraseña hasheada.
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|max:255',
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return self::response(true, 'Usuario creado correctamente.', $user, 201);
        } catch (ValidationException $e) {
            return self::response(false, 'Los datos no son válidos.', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);

            return self::response(false, 'Error al crear el usuario.', null, 500);
        }
    }

    /**
     * Comprueba el email y la contraseña y devuelve un token de sesión nuevo.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $credentials['email'])->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {
                return self::response(false, 'Credenciales incorrectas.', null, 401);
            }

            $token = Token::create([
                'user_id' => $user->id,
                'token' => Str::random(60),
            ]);

            return self::response(true, 'Sesión iniciada correctamente.', [
                'token' => $token->token,
                'user' => $user,
            ]);
        } catch (ValidationException $e) {
            return self::response(false, 'Los datos no son válidos.', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);

            return self::response(false, 'Error al iniciar sesión.', null, 500);
        }
    }

    /**
     * Actualiza el name del usuario al que pertenece el token.
     */
    public function updateName(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'token' => 'required|string',
                'name' => 'required|string|min:3|max:255',
            ]);

            $token = Token::where('token', $data['token'])->first();

            if (! $token) {
                return self::response(false, 'Token no válido.', null, 401);
            }

            $user = $token->user;
            $user->update(['name' => $data['name']]);

            return self::response(true, 'Nombre actualizado correctamente.', $user);
        } catch (ValidationException $e) {
            return self::response(false, 'Los datos no son válidos.', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);

            return self::response(false, 'Error al actualizar el nombre.', null, 500);
        }
    }
}
