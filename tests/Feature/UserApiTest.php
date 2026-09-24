<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('get devuelve los usuarios paginados de 10 en 10', function () {
    User::factory()->count(15)->create();

    $this->getJson('/api/users')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('total', 15)
        ->assertJsonPath('data.0.id', 1)
        ->assertJsonMissingPath('data.0.password');
});

test('create crea un usuario con la contraseña cifrada', function () {
    $this->postJson('/api/users', [
        'username' => 'carlos',
        'email' => 'carlos@example.com',
        'password' => 'secreto123',
    ])
        ->assertCreated()
        ->assertJsonPath('username', 'carlos')
        ->assertJsonMissingPath('password');

    $user = User::where('email', 'carlos@example.com')->first();

    expect($user->password)->not->toBe('secreto123')
        ->and(Hash::check('secreto123', $user->password))->toBeTrue();
});

test('create valida los datos y no permite emails repetidos', function () {
    User::factory()->create(['email' => 'carlos@example.com']);

    $this->postJson('/api/users', [
        'username' => 'ab',
        'email' => 'carlos@example.com',
        'password' => 'corta',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['username', 'email', 'password']);
});

test('login devuelve los datos del usuario con credenciales correctas', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonMissingPath('password');
});

test('login responde 401 con el mismo mensaje si falla el email o la contraseña', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'incorrecta'])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Credenciales incorrectas.');

    $this->postJson('/api/login', ['email' => 'noexiste@example.com', 'password' => 'password'])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Credenciales incorrectas.');
});

test('update_username cambia el username', function () {
    $user = User::factory()->create();

    $this->patchJson('/api/users/username', [
        'email' => $user->email,
        'password' => 'password',
        'username' => 'nuevo_nombre',
    ])->assertOk()
        ->assertJsonPath('username', 'nuevo_nombre');

    expect($user->fresh()->username)->toBe('nuevo_nombre');
});

test('update_email cambia el email', function () {
    $user = User::factory()->create();

    $this->patchJson('/api/users/email', [
        'email' => $user->email,
        'password' => 'password',
        'new_email' => 'nuevo@example.com',
    ])->assertOk()
        ->assertJsonPath('email', 'nuevo@example.com');

    expect($user->fresh()->email)->toBe('nuevo@example.com');
});

test('update_password cambia la contraseña', function () {
    $user = User::factory()->create();

    $this->patchJson('/api/users/password', [
        'email' => $user->email,
        'password' => 'password',
        'new_password' => 'nueva_clave123',
    ])->assertOk();

    expect(Hash::check('nueva_clave123', $user->fresh()->password))->toBeTrue();
});

test('delete elimina el usuario', function () {
    $user = User::factory()->create();

    $this->deleteJson('/api/users', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();

    $this->assertModelMissing($user);
});

test('las funciones protegidas responden 401 con credenciales incorrectas', function (string $method, string $uri) {
    $user = User::factory()->create();

    $this->json($method, $uri, [
        'email' => $user->email,
        'password' => 'incorrecta',
        'username' => 'otro',
        'new_email' => 'otro@example.com',
        'new_password' => 'otra_clave123',
    ])->assertUnauthorized();

    $this->assertModelExists($user);
})->with([
    ['PATCH', '/api/users/username'],
    ['PATCH', '/api/users/email'],
    ['PATCH', '/api/users/password'],
    ['DELETE', '/api/users'],
]);

test('las funciones con credenciales bloquean tras 10 intentos por minuto', function () {
    $user = User::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertUnauthorized();
    }

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertTooManyRequests();
});
