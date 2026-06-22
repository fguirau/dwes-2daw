# UD6.2 - Controladores API y Resources

## Controladores API

En la unidad anterior definimos endpoints directamente en `routes/api.php`. Para proyectos reales, la lógica debe ir en un **controlador dedicado a la API**.

Laravel distingue dos tipos de controladores:

| Tipo | Comando | Métodos generados |
|---|---|---|
| Controlador web | `make:controller --resource` | `index, create, store, show, edit, update, destroy` |
| Controlador API | `make:controller --api` | `index, store, show, update, destroy` |

El controlador API omite `create()` y `edit()` porque no necesitamos devolver formularios HTML.

### Crear el controlador

```bash
php artisan make:controller Api/ProductoApiController --api
```

!!! info "Carpeta Api/"
    Es una buena práctica agrupar los controladores de la API en una subcarpeta `Api/` dentro de `app/Http/Controllers/`. Esto mantiene el proyecto organizado cuando convive una web Blade con una API REST.

El controlador generado tiene esta estructura:

```php
// app/Http/Controllers/Api/ProductoApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoApiController extends Controller
{
    public function index() { }
    public function store(Request $request) { }
    public function show(Producto $producto) { }
    public function update(Request $request, Producto $producto) { }
    public function destroy(Producto $producto) { }
}
```

### Registrar la ruta

```php
// routes/api.php
use App\Http\Controllers\Api\ProductoApiController;

Route::apiResource('productos', ProductoApiController::class);
```

---

## ¿Qué es un API Resource?

Cuando devolvemos un modelo Eloquent directamente como JSON, Laravel serializa **todos sus atributos**, incluyendo campos que quizás no queremos exponer (`password`, `remember_token`, timestamps internos...).

Los **API Resources** actúan como una capa de transformación entre el modelo y el JSON que devolvemos al cliente. Nos permiten:

- Seleccionar qué campos incluir
- Renombrar o formatear campos
- Añadir relaciones de forma controlada
- Mantener una estructura de respuesta consistente

```
Modelo Eloquent  →  API Resource  →  JSON de respuesta
   (base de          (capa de          (lo que ve
    datos)          transformación)     el cliente)
```

---

## Crear un API Resource

```bash
php artisan make:resource ProductoResource
```

Esto crea `app/Http/Resources/ProductoResource.php`:

```php
// app/Http/Resources/ProductoResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio'      => number_format($this->precio, 2),
            'stock'       => $this->stock,
            'genero'      => $this->genero->nombre ?? null,
            'imagen'      => $this->imagen,
        ];
    }
}
```

!!! tip "Acceso a atributos con `$this`"
    Dentro de un Resource, `$this` actúa como proxy del modelo. Puedes acceder a cualquier atributo o relación del modelo con `$this->nombre`, `$this->genero->nombre`, etc.

---

## Crear un Resource Collection

Para devolver listas de recursos, podemos usar `ProductoResource::collection()` directamente, o crear una colección dedicada si queremos personalizar los metadatos:

```bash
php artisan make:resource ProductoCollection
```

```php
// app/Http/Resources/ProductoCollection.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductoCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data'  => $this->collection,
            'total' => $this->collection->count(),
        ];
    }
}
```

Para la mayoría de casos, `ProductoResource::collection($productos)` es suficiente y más sencillo.

---

## CRUD completo del controlador

### `index()` — Listar productos

```php
public function index(): JsonResponse
{
    $productos = Producto::with('genero')->get();

    return response()->json(
        ProductoResource::collection($productos),
        200
    );
}
```

### `store()` — Crear producto

```php
public function store(Request $request): JsonResponse
{
    $datos = $request->validate([
        'nombre'      => 'required|string|max:255',
        'descripcion' => 'nullable|string',
        'precio'      => 'required|numeric|min:0',
        'stock'       => 'required|integer|min:0',
        'genero_id'   => 'required|exists:generos,id',
    ]);

    $producto = Producto::create($datos);

    return response()->json(
        new ProductoResource($producto->load('genero')),
        201
    );
}
```

!!! info "Código 201 al crear"
    Al crear un recurso nuevo, devolvemos el código `201 Created` en lugar de `200 OK`. Es la convención REST correcta.

### `show()` — Ver un producto

```php
public function show(Producto $producto): JsonResponse
{
    return response()->json(
        new ProductoResource($producto->load('genero')),
        200
    );
}
```

### `update()` — Actualizar producto

```php
public function update(Request $request, Producto $producto): JsonResponse
{
    $datos = $request->validate([
        'nombre'      => 'required|string|max:255',
        'descripcion' => 'nullable|string',
        'precio'      => 'required|numeric|min:0',
        'stock'       => 'required|integer|min:0',
        'genero_id'   => 'required|exists:generos,id',
    ]);

    $producto->update($datos);

    return response()->json(
        new ProductoResource($producto->load('genero')),
        200
    );
}
```

### `destroy()` — Eliminar producto

```php
public function destroy(Producto $producto): JsonResponse
{
    $producto->delete();

    return response()->json(null, 204);
}
```

!!! tip "204 No Content"
    Al eliminar un recurso devolvemos `204 No Content` con el cuerpo vacío (`null`). No hay nada que devolver porque el recurso ya no existe.

---

## Manejo de errores

### Recurso no encontrado (404)

Laravel gestiona automáticamente los `404` cuando usamos **Route Model Binding** (parámetro tipado `Producto $producto`). Si el producto no existe, Laravel devuelve:

```json
{
    "message": "No query results for model [App\\Models\\Producto] 5"
}
```

Para devolver un mensaje más limpio, podemos personalizar la respuesta en `bootstrap/app.php`:

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (ModelNotFoundException $e, Request $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Recurso no encontrado.',
            ], 404);
        }
    });
})
```

### Errores de validación (422)

Cuando la validación falla en una petición que espera JSON, Laravel devuelve automáticamente un `422` con los errores:

```json
{
    "message": "The nombre field is required.",
    "errors": {
        "nombre": ["The nombre field is required."],
        "precio": ["The precio field must be a number."]
    }
}
```

!!! warning "Cabecera Accept: application/json"
    Para que Laravel devuelva los errores de validación como JSON (en lugar de redirigir), la petición debe incluir la cabecera:
    ```
    Accept: application/json
    ```
    Thunder Client la añade automáticamente al seleccionar JSON como tipo de respuesta esperada.

---

## Probar el CRUD con Thunder Client

### Configurar la colección

En Thunder Client, crea una **colección** llamada `PixelShop API` para organizar todas las peticiones.

### Peticiones de ejemplo

=== "GET /api/productos"
    ```
    Método: GET
    URL:    http://localhost:8000/api/productos
    Headers: Accept: application/json
    ```

=== "POST /api/productos"
    ```
    Método: POST
    URL:    http://localhost:8000/api/productos
    Headers:
        Accept: application/json
        Content-Type: application/json
    Body (JSON):
    {
        "nombre": "Elden Ring",
        "descripcion": "RPG de mundo abierto",
        "precio": 59.99,
        "stock": 10,
        "genero_id": 1
    }
    ```

=== "PUT /api/productos/1"
    ```
    Método: PUT
    URL:    http://localhost:8000/api/productos/1
    Headers:
        Accept: application/json
        Content-Type: application/json
    Body (JSON):
    {
        "nombre": "Elden Ring",
        "descripcion": "RPG de mundo abierto de FromSoftware",
        "precio": 49.99,
        "stock": 15,
        "genero_id": 1
    }
    ```

=== "DELETE /api/productos/1"
    ```
    Método: DELETE
    URL:    http://localhost:8000/api/productos/1
    Headers: Accept: application/json
    ```

---

## Ejercicio práctico 6.2a — CRUD de géneros

!!! example "Ejercicio A — API de géneros"

    Implementa el CRUD completo de géneros siguiendo el mismo patrón que productos.

    **Pasos:**

    1. Crea el controlador:
    ```bash
    php artisan make:controller Api/GeneroApiController --api
    ```

    2. Crea el resource:
    ```bash
    php artisan make:resource GeneroResource
    ```
    El resource debe exponer: `id`, `nombre`, `descripcion` (si existe), y `total_productos` (número de productos del género).

    3. Implementa los 5 métodos del controlador (`index`, `store`, `show`, `update`, `destroy`).

    4. Registra la ruta en `api.php`:
    ```php
    Route::apiResource('generos', GeneroApiController::class);
    ```

    5. Prueba todas las operaciones desde Thunder Client.

    !!! tip "Contar productos en el Resource"
        Para incluir el número de productos en `GeneroResource`, carga la relación con `withCount` en el controlador:
        ```php
        Genero::withCount('productos')->get()
        ```
        Y accede a él en el resource con `$this->productos_count`.

---

## Ejercicio práctico 6.2b — Filtrado y búsqueda

!!! example "Ejercicio B — Búsqueda y filtros"

    Añade parámetros de consulta al endpoint `GET /api/productos` para filtrar resultados.

    **Requisitos:**

    - `GET /api/productos?genero=1` → filtra por género
    - `GET /api/productos?buscar=witcher` → busca por nombre
    - `GET /api/productos?orden=precio` → ordena por precio ascendente

    **Implementación en `index()`:**

    ```php
    public function index(Request $request): JsonResponse
    {
        $query = Producto::with('genero');

        if ($request->has('genero')) {
            $query->where('genero_id', $request->genero);
        }

        if ($request->has('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%');
        }

        if ($request->has('orden') && $request->orden === 'precio') {
            $query->orderBy('precio');
        }

        return response()->json(
            ProductoResource::collection($query->get()),
            200
        );
    }
    ```

    Prueba las combinaciones de filtros desde Thunder Client añadiendo los parámetros en la pestaña **Query** de la petición.
