# UD6.1 - Fundamentos REST y API Routes

## ¿Qué es una API REST?

Una **API REST** (*Representational State Transfer*) es un estilo arquitectónico para diseñar servicios web que permite la comunicación entre aplicaciones mediante el protocolo HTTP.

A diferencia de una aplicación web tradicional que devuelve HTML, una API REST devuelve **datos en formato JSON** (o XML) que pueden ser consumidos por cualquier cliente: una aplicación móvil, un frontend en Vue/React, otra aplicación servidor, etc.

!!! info "API en GameShop"
    En las unidades anteriores construimos la web de GameShop con Blade. Ahora vamos a construir una **capa de datos independiente**: una API REST que exponga los productos, géneros y pedidos para que cualquier cliente pueda consumirlos.

---

## Principios REST

Una API se considera RESTful cuando cumple estos principios:

| Principio | Descripción |
|---|---|
| **Stateless** | Cada petición es independiente. El servidor no guarda el estado del cliente entre peticiones |
| **Uniform Interface** | Los recursos se identifican por URLs consistentes |
| **Client-Server** | El cliente y el servidor están desacoplados |
| **Cacheable** | Las respuestas pueden marcarse como cacheables |
| **Layered System** | El cliente no sabe si habla directamente con el servidor final |

---

## Verbos HTTP

En REST, el tipo de operación que realizamos sobre un recurso viene determinado por el **verbo HTTP** de la petición, no por la URL.

| Verbo | Acción CRUD | Ejemplo |
|---|---|---|
| `GET` | Leer | `GET /api/productos` → listar productos |
| `POST` | Crear | `POST /api/productos` → crear producto |
| `PUT` | Actualizar completo | `PUT /api/productos/5` → reemplazar producto |
| `PATCH` | Actualizar parcial | `PATCH /api/productos/5` → modificar campos |
| `DELETE` | Eliminar | `DELETE /api/productos/5` → borrar producto |

!!! tip "PUT vs PATCH"
    En la práctica, Laravel usa `PUT` para las actualizaciones completas del recurso. `PATCH` se reserva para actualizaciones parciales (modificar solo un campo). En esta unidad usaremos `PUT`.

---

## Códigos de estado HTTP

La respuesta de una API debe incluir siempre un **código de estado HTTP** que informe al cliente del resultado de la operación.

### Códigos más importantes

| Código | Nombre | Cuándo usarlo |
|---|---|---|
| `200 OK` | Éxito | GET, PUT, PATCH exitosos |
| `201 Created` | Creado | POST exitoso (recurso creado) |
| `204 No Content` | Sin contenido | DELETE exitoso |
| `400 Bad Request` | Petición incorrecta | Datos mal formados |
| `401 Unauthorized` | No autenticado | Sin token o token inválido |
| `403 Forbidden` | Sin permisos | Autenticado pero sin autorización |
| `404 Not Found` | No encontrado | Recurso no existe |
| `422 Unprocessable Entity` | Error de validación | Datos no pasan validación de Laravel |
| `500 Internal Server Error` | Error del servidor | Error inesperado en el servidor |

!!! warning "El código importa"
    Devolver siempre `200 OK` aunque haya errores es un antipatrón muy común. El cliente necesita el código correcto para saber cómo actuar ante cada situación.

---

## Estructura de una respuesta JSON

Una buena API devuelve respuestas JSON consistentes. Aunque no hay un estándar obligatorio, una estructura habitual es:

=== "Respuesta exitosa"
    ```json
    {
        "data": {
            "id": 1,
            "nombre": "The Witcher 3",
            "precio": "29.99",
            "genero": "RPG"
        }
    }
    ```

=== "Colección de recursos"
    ```json
    {
        "data": [
            { "id": 1, "nombre": "The Witcher 3", "precio": "29.99" },
            { "id": 2, "nombre": "Cyberpunk 2077", "precio": "39.99" }
        ],
        "meta": {
            "total": 2,
            "pagina_actual": 1
        }
    }
    ```

=== "Error de validación"
    ```json
    {
        "message": "The given data was invalid.",
        "errors": {
            "nombre": ["El campo nombre es obligatorio."],
            "precio": ["El precio debe ser un número positivo."]
        }
    }
    ```

---

## API Routes en Laravel

Laravel separa las rutas web de las rutas de API en dos archivos distintos:

| Archivo | Prefijo | Middleware | Uso |
|---|---|---|---|
| `routes/web.php` | `/` | `web` (sesiones, CSRF) | Rutas con Blade |
| `routes/api.php` | `/api` | `api` (stateless) | Rutas de la API REST |

Todas las rutas definidas en `api.php` se acceden automáticamente con el prefijo `/api`. Por ejemplo:

```php
// routes/api.php
Route::get('/productos', ...);
// Accesible en: http://localhost/api/productos
```

!!! info "Laravel 11 y api.php"
    En Laravel 11, el archivo `routes/api.php` **no existe por defecto**. Hay que crearlo con el comando de Artisan:
    ```bash
    php artisan install:api
    ```
    Este comando crea `routes/api.php` e instala Sanctum automáticamente. También añade el fichero a `bootstrap/app.php`.

---

## Primer endpoint a mano

Antes de usar controladores, vamos a crear un endpoint directamente en `routes/api.php` para entender el flujo básico.

### Paso 1 — Crear el archivo de rutas API

```bash
php artisan install:api
```

### Paso 2 — Definir una ruta de prueba

```php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Endpoint de prueba — sin autenticación
Route::get('/ping', function () {
    return response()->json([
        'mensaje' => 'GameShop API funcionando',
        'version' => '1.0',
    ]);
});
```

### Paso 3 — Probar con Thunder Client

1. Abre VS Code → Thunder Client (icono del rayo en la barra lateral)
2. Haz clic en **New Request**
3. Selecciona el verbo `GET`
4. Introduce la URL: `http://localhost:8000/api/ping`
5. Pulsa **Send**

Deberías recibir:

```json
{
    "mensaje": "GameShop API funcionando",
    "version": "1.0"
}
```

Con un código de estado `200 OK` en la parte superior de la respuesta.

---

## Rutas de recursos API

Laravel incluye el método `apiResource()` que genera automáticamente las 5 rutas RESTful de una API (sin las rutas `create` y `edit` que son exclusivas de vistas HTML):

```php
// routes/api.php
Route::apiResource('productos', ProductoApiController::class);
```

Esto genera:

| Método | URI | Acción del controlador | Nombre de ruta |
|---|---|---|---|
| GET | `/api/productos` | `index` | `productos.index` |
| POST | `/api/productos` | `store` | `productos.store` |
| GET | `/api/productos/{producto}` | `show` | `productos.show` |
| PUT/PATCH | `/api/productos/{producto}` | `update` | `productos.update` |
| DELETE | `/api/productos/{producto}` | `destroy` | `productos.destroy` |

!!! tip "apiResource vs resource"
    `apiResource()` omite los métodos `create()` y `edit()` porque en una API no existen formularios HTML. Todo se envía directamente como JSON en el cuerpo de la petición.

Puedes verificar las rutas generadas con:

```bash
php artisan route:list --path=api
```

---

## Ejercicio práctico 6.1

!!! example "Ejercicio — Primeros endpoints en GameShop"

    **Objetivo:** Crear los primeros endpoints de la API de GameShop sin usar controladores todavía, para familiarizarse con el flujo de petición/respuesta.

    ### Parte A — Endpoints de prueba

    En `routes/api.php`, crea los siguientes endpoints:

    1. `GET /api/ping` → devuelve `{ "estado": "ok", "app": "GameShop API" }`
    2. `GET /api/version` → devuelve `{ "version": "1.0", "laravel": "11.x" }`

    ### Parte B — Endpoint con datos reales

    3. `GET /api/generos` → devuelve todos los géneros de la base de datos como JSON

    ```php
    use App\Models\Genero;

    Route::get('/generos', function () {
        return response()->json([
            'data' => Genero::all(['id', 'nombre']),
        ]);
    });
    ```

    ### Parte C — Pruebas en Thunder Client

    Comprueba los 3 endpoints con Thunder Client y verifica:
    - El código de estado es `200 OK`
    - El JSON tiene la estructura esperada
    - El header `Content-Type` de la respuesta es `application/json`

    !!! tip "Ver headers en Thunder Client"
        En la respuesta de Thunder Client, haz clic en la pestaña **Headers** para ver el `Content-Type` y otros headers devueltos por Laravel.
