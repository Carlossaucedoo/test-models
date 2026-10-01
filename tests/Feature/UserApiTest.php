<?php

use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('get devuelve los 10 primeros usuarios', function () {
    User::factory()->count(15)->create();

    $this->getJson('/api/users')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('data.0.id', 1)
        ->assertJsonPath('data.9.id', 10)
        ->assertJsonMissingPath('data.0.password');
});

test('create crea un usuario con la contraseña hasheada', function () {
    $this->postJson('/api/users', [
        'name' => 'Carlos',
        'email' => 'carlos@example.com',
        'password' => 'secreto123',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Carlos')
        ->assertJsonMissingPath('data.password');

    $user = User::where('email', 'carlos@example.com')->first();

    expect($user->password)->not->toBe('secreto123')
        ->and(Hash::check('secreto123', $user->password))->toBeTrue();
});

test('create responde 422 con los errores si los datos no son válidos', function () {
    User::factory()->create(['email' => 'carlos@example.com']);

    $this->postJson('/api/users', [
        'name' => 'ab',
        'email' => 'carlos@example.com',
        'password' => 'corta',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['data' => ['name', 'email', 'password']]);
});

test('login genera un token, lo guarda y lo devuelve', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonMissingPath('data.user.password');

    $token = $response->json('data.token');

    expect($token)->toHaveLength(60)
        ->and(Token::where('token', $token)->value('user_id'))->toBe($user->id);
});

test('login responde 401 con el mismo mensaje si falla el email o la contraseña', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'incorrecta'])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Credenciales incorrectas.');

    $this->postJson('/api/login', ['email' => 'noexiste@example.com', 'password' => 'password'])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Credenciales incorrectas.');

    expect(Token::count())->toBe(0);
});

test('update_name cambia el name del usuario del token', function () {
    $user = User::factory()->create();
    $token = Token::create(['user_id' => $user->id, 'token' => 'token-de-prueba']);

    $this->patchJson('/api/users/name', [
        'token' => $token->token,
        'name' => 'Nuevo Nombre',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Nuevo Nombre');

    expect($user->fresh()->name)->toBe('Nuevo Nombre');
});

test('update_name responde 401 si el token no existe', function () {
    $user = User::factory()->create(['name' => 'Original']);

    $this->patchJson('/api/users/name', [
        'token' => 'token-inventado',
        'name' => 'Nuevo Nombre',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Token no válido.');

    expect($user->fresh()->name)->toBe('Original');
});

test('todas las respuestas tienen success, message, data y timestamp', function (string $method, string $uri, array $body, int $status) {
    $user = User::factory()->create(['email' => 'test@example.com']);
    Token::create(['user_id' => $user->id, 'token' => 'token-valido']);

    $this->json($method, $uri, $body)
        ->assertStatus($status)
        ->assertJsonStructure(['success', 'message', 'data', 'timestamp']);
})->with([
    'get' => ['GET', '/api/users', [], 200],
    'create correcto' => ['POST', '/api/users', ['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'secreto123'], 201],
    'create con datos no válidos' => ['POST', '/api/users', [], 422],
    'login correcto' => ['POST', '/api/login', ['email' => 'test@example.com', 'password' => 'password'], 200],
    'login incorrecto' => ['POST', '/api/login', ['email' => 'test@example.com', 'password' => 'mal'], 401],
    'update_name correcto' => ['PATCH', '/api/users/name', ['token' => 'token-valido', 'name' => 'Ana'], 200],
    'update_name con token no válido' => ['PATCH', '/api/users/name', ['token' => 'otro', 'name' => 'Ana'], 401],
    'ruta que no existe' => ['GET', '/api/no-existe', [], 404],
]);

test('login bloquea tras 10 intentos por minuto', function () {
    $user = User::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertUnauthorized();
    }

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertTooManyRequests()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'data', 'timestamp']);
});
