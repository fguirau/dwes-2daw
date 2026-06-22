# UD6.3 - Autenticación con Sanctum

## ¿Por qué autenticar la API?

Hasta ahora, cualquier persona que conozca las URLs de nuestra API puede leer, crear, modificar o eliminar datos. En una aplicación real necesitamos proteger las operaciones sensibles.

Laravel Sanctum proporciona un sistema de autenticación **basado en tokens** diseñado específicamente para APIs. El flujo es sencillo:

```
1. Cliente envía credenciales (email + password)
         ↓
2. La API verifica y devuelve un token
         ↓
3. El cliente guarda el token
         ↓
4. En cada petición protegida, el cliente envía el token en la cabecera
         ↓
5. La API valida el token y permite o deniega el acceso
```

---

## Instalación de Sanctum

Si ejecutaste `php artisan install:api` en la unidad anterior, **Sanctum ya está instalado**. Puedes verificarlo comprobando que existe la migración `personal_access_tokens` y que `bootstrap/app.php` incluye el middleware de Sanctum.

Si necesitas instalarlo desde cero:

```bash
php artisan install:api
```

Este comando:

- Instala el paquete `laravel/sanctum`
- Publica la migración de tokens
- Configura automáticamente `bootstrap/app.php`

Ejecuta las migraciones si aún no lo has hecho:

```bash
php artisan migrate
```

Esto crea la tabla `personal_access_tokens` en la base de datos.

---

## Preparar el modelo User

El modelo `User` debe usar el trait `HasApiTokens` de Sanctum para poder generar tokens:

```php
// app/Models/User.php

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ...
}
```

!!! info "Laravel 11"
    En Laravel 11, el trait `HasApiTokens` ya puede estar incluido en el modelo `User` por defecto si instalaste Breeze con anterioridad. Compruébalo antes de añadirlo.

---

## Controlador de autenticación

Creamos un controlador específico para los endpoints de login y registro:

```bash
php artisan make:controller Api/AuthApiController
```

### Registro de usuario

```php
// app/Http/Controllers/Api/AuthApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $datos['name'],
            'email'    => $datos['email'],
            'password' => Hash::make($datos['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ], 201);
    }
```

### Login

```php
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Eliminamos tokens anteriores (opcional — sesión única)
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }
```

### Logout

```php
    public function logout(Request $request): JsonResponse
    {
        // Revoca el token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
```

---

## Rutas de autenticación

```php
// routes/api.php

use App\Http\Controllers\Api\AuthApiController;

// Rutas públicas (sin autenticación)
Route::post('/register', [AuthApiController::class, 'register']);
Route::post('/login',    [AuthApiController::class, 'login']);

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);
});
```

!!! warning "Orden de las rutas"
    Las rutas públicas (`/register`, `/login`) deben definirse **fuera** del grupo `auth:sanctum`. Si las incluyes dentro, los usuarios no podrán autenticarse nunca.

---

## Proteger rutas con `auth:sanctum`

Ahora podemos proteger los endpoints de escritura (POST, PUT, DELETE) mientras dejamos los de lectura (GET) públicos:

```php
// routes/api.php

Route::apiResource('generos', GeneroApiController::class)
    ->only(['index', 'show']);  // GET públicos

Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('generos', GeneroApiController::class)
        ->except(['index', 'show']);  // POST, PUT, DELETE protegidos

    Route::apiResource('productos', ProductoApiController::class)
        ->except(['index', 'show']);

    Route::post('/logout', [AuthApiController::class, 'logout']);
});
```

!!! tip "Lectura pública, escritura protegida"
    Este patrón es muy habitual: cualquiera puede consultar el catálogo de productos (`GET`), pero solo los usuarios autenticados pueden crear, modificar o eliminar (`POST`, `PUT`, `DELETE`).

---

## Cómo funciona el token

Cuando el cliente hace login, recibe un **token en texto plano** (tipo `Bearer`). Para las peticiones protegidas, debe enviarlo en la cabecera `Authorization`:

```
Authorization: Bearer 1|aBcDeFgHiJkLmNoPqRsTuVwXyZ...
```

Laravel Sanctum intercepta esta cabecera, busca el token en la tabla `personal_access_tokens`, y si es válido inyecta el usuario autenticado en `$request->user()`.

---

## Probar la autenticación con Thunder Client

### 1. Registrar un usuario

```
Método: POST
URL:    http://localhost:8000/api/register
Headers:
    Accept: application/json
    Content-Type: application/json
Body:
{
    "name": "Paco",
    "email": "paco@gameshop.test",
    "password": "password",
    "password_confirmation": "password"
}
```

La respuesta incluye el token:

```json
{
    "user": { "id": 1, "name": "Paco", "email": "paco@gameshop.test" },
    "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ..."
}
```

### 2. Usar el token en peticiones protegidas

Copia el token y úsalo en la cabecera de las peticiones protegidas:

```
Método: POST
URL:    http://localhost:8000/api/productos
Headers:
    Accept: application/json
    Content-Type: application/json
    Authorization: Bearer 1|aBcDeFgHiJkLmNoPqRsTuVwXyZ...
Body:
{
    "nombre": "Hollow Knight",
    "precio": 14.99,
    "stock": 20,
    "genero_id": 2
}
```

!!! tip "Variables de entorno en Thunder Client"
    Thunder Client permite guardar el token como variable de entorno para no tener que copiarlo manualmente en cada petición. Ve a **Env** → **New Environment** y crea una variable `token`. Luego úsala con `{{token}}` en las cabeceras.

### 3. Petición sin token (debe fallar)

Intenta la misma petición POST sin la cabecera `Authorization`. Deberías recibir:

```json
{
    "message": "Unauthenticated."
}
```

Con código de estado `401 Unauthorized`.

---

## Endpoint de usuario autenticado

Es habitual exponer un endpoint `/api/user` que devuelve los datos del usuario actual:

```php
// routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    // ... resto de rutas protegidas
});
```

---

## Ejercicio práctico 6.3 — Proteger GameShop API

!!! example "Ejercicio — Autenticación completa"

    **Objetivo:** Añadir autenticación con Sanctum a la API de GameShop y verificar que las rutas protegidas funcionan correctamente.

    ### Parte A — Configuración

    1. Verifica que Sanctum está instalado y el trait `HasApiTokens` está en el modelo `User`.
    2. Crea el `AuthApiController` con los métodos `register`, `login` y `logout`.

    ### Parte B — Rutas

    3. Define las rutas de autenticación en `api.php`.
    4. Protege con `auth:sanctum` las operaciones de escritura de productos y géneros.
    5. Deja los `GET` públicos (sin autenticación).

    ### Parte C — Pruebas en Thunder Client

    Crea las siguientes peticiones en una colección llamada **GameShop Auth**:

    | # | Petición | Resultado esperado |
    |---|---|---|
    | 1 | `POST /api/register` con datos correctos | `201` + token en respuesta |
    | 2 | `POST /api/login` con datos correctos | `200` + token en respuesta |
    | 3 | `POST /api/login` con contraseña incorrecta | `422` + mensaje de error |
    | 4 | `GET /api/productos` sin token | `200` (ruta pública) |
    | 5 | `POST /api/productos` sin token | `401 Unauthenticated` |
    | 6 | `POST /api/productos` con token válido | `201` + producto creado |
    | 7 | `POST /api/logout` con token válido | `200` + mensaje de confirmación |
    | 8 | `POST /api/productos` con token revocado | `401 Unauthenticated` |

    !!! tip "Orden importa"
        Realiza las peticiones en el orden indicado. El token que obtienes en el paso 2 lo usas en los pasos 6 y 7. Después de ejecutar el paso 7 (logout), el token queda revocado y el paso 8 debe fallar.
