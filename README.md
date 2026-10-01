# API de los modelos User y Token (Laravel 13 + SQLite)

API REST para listar y crear usuarios, iniciar sesión con un token y actualizar el nombre del usuario con ese token.

## Requisitos

- PHP 8.3 o superior
- Composer
- [Laravel Herd](https://herd.laravel.com) (opcional, para usar `http://test-models.test`)
- Postman

## Instalación

```bash
git clone <url-del-repo> test-models
cd test-models
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

El seeder (`database/seeders/DatabaseSeeder.php`) crea 12 usuarios:

| Name     | Email              | Contraseña    |
|----------|--------------------|---------------|
| ana      | ana@example.com    | password123   |
| luis     | luis@example.com   | password123   |
| + 10 usuarios aleatorios (contraseña `password`) | | |

Para vaciar la base de datos y volver a cargar los usuarios: `php artisan migrate:fresh --seed`.

Si usas Herd y la carpeta está dentro de `~/Herd`, la API estará en `http://test-models.test/api`.
Sin Herd, arranca el servidor con `php artisan serve` y usa `http://localhost:8000/api`.

## Rutas

| Función     | Método | URL               | Body (JSON)                 |
|-------------|--------|-------------------|-----------------------------|
| get         | GET    | `/api/users`      | —                           |
| create      | POST   | `/api/users`      | `name`, `email`, `password` |
| login       | POST   | `/api/login`      | `email`, `password`         |
| update_name | PATCH  | `/api/users/name` | `token`, `name`             |

- **get** devuelve los 10 primeros usuarios.
- **create** guarda la contraseña hasheada con `Hash::make`.
- **login** genera un token aleatorio de 60 caracteres, lo guarda en la tabla `tokens` (modelo `Token`) y lo devuelve.
- **update_name** busca el usuario al que pertenece el token y le cambia el `name`.

Todas las funciones usan `try/catch` y todas las respuestas tienen la misma estructura:

```json
{
    "success": true,
    "message": "Sesión iniciada correctamente.",
    "data": { "token": "...", "user": { "id": 1, "name": "Test User", "email": "test@example.com" } },
    "timestamp": "2026-10-01T08:13:57+00:00"
}
```

Códigos de respuesta:

- `200` / `201`: correcto.
- `401`: email o contraseña incorrectos, o token no válido.
- `422`: datos no válidos. En `data` van los errores de cada campo.
- `429`: demasiados intentos. Login y update_name admiten 10 peticiones por minuto desde la misma IP.
- `500`: error inesperado del servidor.

Envía siempre la cabecera `Accept: application/json`.

## Postman

1. En Postman: **Import** → selecciona `postman/API User y Token.postman_collection.json`.
2. Si no usas Herd, cambia la variable `base_url` de la collection (pestaña **Variables**).
3. Ejecuta las peticiones en orden (1 → 7) o todas a la vez con **Run collection**.

*Crear usuario* genera un email nuevo en cada ejecución y *Login* guarda el token en la variable `token`, que usa *Actualizar name*. Cada petición comprueba el código de estado y la estructura `success`, `message`, `data` y `timestamp`.

## Tests

```bash
php artisan test
```
