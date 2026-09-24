<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Devuelve la paginación de los usuarios, 10 por página.
     */
    public function get(): LengthAwarePaginator
    {
        return User::orderBy('id')->paginate(10);
    }

    /**
     * Crea un usuario nuevo.
     */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string|min:3|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:255',
        ]);

        $user = User::create($data);

        return response()->json($user, 201);
    }

    /**
     * Devuelve los datos del usuario si el email y la contraseña son correctos.
     */
    public function login(Request $request): JsonResponse
    {
        $user = $this->findUserByCredentials($request);

        return response()->json($user);
    }

    /**
     * Actualiza el username del usuario.
     */
    public function updateUsername(Request $request): JsonResponse
    {
        $user = $this->findUserByCredentials($request);

        $data = $request->validate([
            'username' => 'required|string|min:3|max:255',
        ]);

        $user->update(['username' => $data['username']]);

        return response()->json($user);
    }

    /**
     * Actualiza el email del usuario.
     */
    public function updateEmail(Request $request): JsonResponse
    {
        $user = $this->findUserByCredentials($request);

        $data = $request->validate([
            'new_email' => 'required|email|max:255|unique:users,email',
        ]);

        $user->update(['email' => $data['new_email']]);

        return response()->json($user);
    }

    /**
     * Actualiza la contraseña del usuario.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $this->findUserByCredentials($request);

        $data = $request->validate([
            'new_password' => 'required|string|min:8|max:255',
        ]);

        $user->update(['password' => $data['new_password']]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    /**
     * Elimina el usuario.
     */
    public function delete(Request $request): JsonResponse
    {
        $user = $this->findUserByCredentials($request);

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }

    /**
     * Busca el usuario por email y comprueba su contraseña.
     * Si no existe o la contraseña no coincide, responde 401 con el mismo mensaje.
     */
    private function findUserByCredentials(Request $request): User
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            abort(401, 'Credenciales incorrectas.');
        }

        return $user;
    }
}
