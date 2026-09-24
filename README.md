# API del modelo User (Laravel 13 + SQLite)

API REST para gestionar usuarios: listar, crear, login y actualizar o eliminar un usuario con su email y contraseña.

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
php artisan migrate
php artisan db:seed   # opcional: crea el usuario test@example.com / password
```

Si usas Herd y la carpeta está dentro de `~/Herd`, la API estará en `http://test-models.test/api`.
Sin Herd, arranca el servidor con `php artisan serve` y usa `http://localhost:8000/api`.

## Rutas

| Función          | Método | URL                   | Body (JSON)                                  |
|------------------|--------|-----------------------|----------------------------------------------|
| get              | GET    | `/api/users?page=1`   | —                                            |
| create           | POST   | `/api/users`          | `username`, `email`, `password`              |
| login            | POST   | `/api/login`          | `email`, `password`                          |
| update_username  | PATCH  | `/api/users/username` | `email`, `password`, `username`              |
| update_email     | PATCH  | `/api/users/email`    | `email`, `password`, `new_email`             |
| update_password  | PATCH  | `/api/users/password` | `email`, `password`, `new_password`          |
| delete           | DELETE | `/api/users`          | `email`, `password`                          |

Respuestas:

- `200` / `201`: correcto.
- `401`: email o contraseña incorrectos (mismo mensaje en ambos casos).
- `422`: datos no válidos (por ejemplo, email repetido o contraseña de menos de 8 caracteres).

Envía siempre la cabecera `Accept: application/json`.

## Postman

1. En Postman: **Import** → selecciona `postman/User API.postman_collection.json`.
2. Si no usas Herd, cambia la variable `base_url` de la collection (pestaña **Variables**).
3. Ejecuta las peticiones en orden (1 → 7) o todas a la vez con **Run collection**.

La petición *Create* genera un email nuevo en cada ejecución, y *Update email* / *Update password* guardan los datos nuevos en las variables para que las siguientes peticiones sigan funcionando.

## Tests

```bash
php artisan test
```
