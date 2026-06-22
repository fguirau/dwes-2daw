# UD6.4 - Proyecto GameShop API

## Descripción del proyecto

En esta unidad construimos la **API REST completa de GameShop**: una tienda de videojuegos que expone todos sus recursos a través de endpoints JSON con autenticación Sanctum.

La API que vamos a desarrollar permitirá:

- Consultar el catálogo de productos y géneros (público)
- Gestionar productos y géneros (solo administradores autenticados)
- Registrarse, hacer login y logout (usuarios)
- Añadir y eliminar productos del carrito (usuarios autenticados)
- Crear pedidos a partir del carrito (usuarios autenticados)
- Consultar el historial de pedidos propios (usuarios autenticados)

---

## Estructura de la API

### Endpoints públicos

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/productos` | Listar todos los productos |
| `GET` | `/api/productos/{id}` | Ver detalle de un producto |
| `GET` | `/api/generos` | Listar todos los géneros |
| `GET` | `/api/generos/{id}` | Ver detalle de un género |
| `POST` | `/api/register` | Registro de usuario |
| `POST` | `/api/login` | Login → devuelve token |

### Endpoints protegidos (requieren token)

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/user` | Datos del usuario autenticado |
| `POST` | `/api/logout` | Revocar token actual |
| `GET` | `/api/carrito` | Ver carrito del usuario |
| `POST` | `/api/carrito` | Añadir producto al carrito |
| `DELETE` | `/api/carrito/{id}` | Eliminar línea del carrito |
| `POST` | `/api/pedidos` | Crear pedido desde el carrito |
| `GET` | `/api/pedidos` | Historial de pedidos del usuario |
| `GET` | `/api/pedidos/{id}` | Detalle de un pedido |

### Endpoints de administración (requieren token de admin)

| Método | Endpoint | Descripción |
|---|---|---|
| `POST` | `/api/productos` | Crear producto |
| `PUT` | `/api/productos/{id}` | Actualizar producto |
| `DELETE` | `/api/productos/{id}` | Eliminar producto |
| `POST` | `/api/generos` | Crear género |
| `PUT` | `/api/generos/{id}` | Actualizar género |
| `DELETE` | `/api/generos/{id}` | Eliminar género |

---

## Arquitectura de la solución

### Resources necesarios

```bash
php artisan make:resource GeneroResource
php artisan make:resource ProductoResource
php artisan make:resource CarritoItemResource
php artisan make:resource PedidoResource
php artisan make:resource PedidoLineaResource
```

### Controladores necesarios

```bash
php artisan make:controller Api/AuthApiController
php artisan make:controller Api/GeneroApiController --api
php artisan make:controller Api/ProductoApiController --api
php artisan make:controller Api/CarritoApiController
php artisan make:controller Api/PedidoApiController
```

---

## Implementación paso a paso

### Paso 1 — Resources

#### `ProductoResource`

```php
// app/Http/Resources/ProductoResource.php

public function toArray(Request $request): array
{
    return [
        'id'          => $this->id,
        'nombre'      => $this->nombre,
        'descripcion' => $this->descripcion,
        'precio'      => number_format($this->precio, 2),
        'stock'       => $this->stock,
        'imagen'      => $this->imagen,
        'genero'      => [
            'id'     => $this->genero->id,
            'nombre' => $this->genero->nombre,
        ],
        'en_stock'    => $this->stock > 0,
    ];
}
```

#### `PedidoResource`

```php
// app/Http/Resources/PedidoResource.php

public function toArray(Request $request): array
{
    return [
        'id'         => $this->id,
        'fecha'      => $this->created_at->format('d/m/Y H:i'),
        'estado'     => $this->estado,
        'total'      => number_format($this->total, 2),
        'lineas'     => PedidoLineaResource::collection(
                            $this->whenLoaded('lineas')
                        ),
    ];
}
```

!!! info "`whenLoaded()`"
    El método `whenLoaded()` incluye la relación en el JSON **solo si ya estaba cargada** en el controlador con `load()` o `with()`. Si no está cargada, omite el campo. Esto evita consultas N+1 involuntarias.

---

### Paso 2 — Controlador del carrito

El carrito utiliza la tabla `carrito_items` (o la tabla pivote equivalente de tu modelo) asociada al usuario autenticado.

```php
// app/Http/Controllers/Api/CarritoApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarritoItemResource;
use App\Models\CarritoItem;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarritoApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()
            ->carritoItems()
            ->with('producto.genero')
            ->get();

        $total = $items->sum(fn($item) => $item->cantidad * $item->producto->precio);

        return response()->json([
            'data'  => CarritoItemResource::collection($items),
            'total' => number_format($total, 2),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad'    => 'required|integer|min:1',
        ]);

        $producto = Producto::findOrFail($datos['producto_id']);

        if ($producto->stock < $datos['cantidad']) {
            return response()->json([
                'message' => 'Stock insuficiente.',
            ], 422);
        }

        $item = $request->user()->carritoItems()->updateOrCreate(
            ['producto_id' => $datos['producto_id']],
            ['cantidad'    => $datos['cantidad']],
        );

        return response()->json(
            new CarritoItemResource($item->load('producto')),
            201
        );
    }

    public function destroy(Request $request, CarritoItem $carritoItem): JsonResponse
    {
        // Verificar que el item pertenece al usuario autenticado
        if ($carritoItem->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Acción no permitida.'], 403);
        }

        $carritoItem->delete();

        return response()->json(null, 204);
    }
}
```

---

### Paso 3 — Controlador de pedidos

```php
// app/Http/Controllers/Api/PedidoApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pedidos = $request->user()
            ->pedidos()
            ->with('lineas.producto')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(
            PedidoResource::collection($pedidos)
        );
    }

    public function show(Request $request, Pedido $pedido): JsonResponse
    {
        if ($pedido->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Acción no permitida.'], 403);
        }

        return response()->json(
            new PedidoResource($pedido->load('lineas.producto'))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $items = $request->user()
            ->carritoItems()
            ->with('producto')
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'message' => 'El carrito está vacío.',
            ], 422);
        }

        $pedido = DB::transaction(function () use ($request, $items) {
            $total = $items->sum(
                fn($item) => $item->cantidad * $item->producto->precio
            );

            $pedido = Pedido::create([
                'user_id' => $request->user()->id,
                'total'   => $total,
                'estado'  => 'pendiente',
            ]);

            foreach ($items as $item) {
                $pedido->lineas()->create([
                    'producto_id' => $item->producto_id,
                    'cantidad'    => $item->cantidad,
                    'precio'      => $item->producto->precio,
                ]);

                // Decrementar stock
                $item->producto->decrement('stock', $item->cantidad);
            }

            // Vaciar el carrito
            $request->user()->carritoItems()->delete();

            return $pedido;
        });

        return response()->json(
            new PedidoResource($pedido->load('lineas.producto')),
            201
        );
    }
}
```

!!! info "Transacciones con `DB::transaction()`"
    El proceso de crear el pedido involucra varias operaciones en la base de datos (crear el pedido, crear las líneas, decrementar stock, vaciar carrito). Si alguna falla, `DB::transaction()` revierte todas. Sin esto, podríamos tener un pedido creado pero sin líneas, o el carrito vaciado sin que el pedido exista.

---

### Paso 4 — Rutas completas

```php
// routes/api.php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CarritoApiController;
use App\Http\Controllers\Api\GeneroApiController;
use App\Http\Controllers\Api\PedidoApiController;
use App\Http\Controllers\Api\ProductoApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── Autenticación ──────────────────────────────────────────────
Route::post('/register', [AuthApiController::class, 'register']);
Route::post('/login',    [AuthApiController::class, 'login']);

// ── Recursos públicos ──────────────────────────────────────────
Route::apiResource('productos', ProductoApiController::class)
    ->only(['index', 'show']);

Route::apiResource('generos', GeneroApiController::class)
    ->only(['index', 'show']);

// ── Rutas protegidas ───────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user',    fn(Request $r) => response()->json($r->user()));
    Route::post('/logout', [AuthApiController::class, 'logout']);

    // Carrito
    Route::get('/carrito',           [CarritoApiController::class, 'index']);
    Route::post('/carrito',          [CarritoApiController::class, 'store']);
    Route::delete('/carrito/{carritoItem}', [CarritoApiController::class, 'destroy']);

    // Pedidos
    Route::get('/pedidos',        [PedidoApiController::class, 'index']);
    Route::post('/pedidos',       [PedidoApiController::class, 'store']);
    Route::get('/pedidos/{pedido}', [PedidoApiController::class, 'show']);

    // Administración de catálogo (CRUD completo)
    Route::apiResource('productos', ProductoApiController::class)
        ->except(['index', 'show']);

    Route::apiResource('generos', GeneroApiController::class)
        ->except(['index', 'show']);
});
```

---

## Proyecto evaluable

!!! example "Proyecto UD6.4 — GameShop API completa"

    **Objetivo:** Implementar y documentar la API REST completa de GameShop.

    ### Entregables

    1. **Código fuente** — Rama `ud6-solucion` del repositorio `gameshop-laravel` con toda la implementación.

    2. **Colección Thunder Client** — Archivo `.json` exportado con todas las peticiones organizadas y funcionando.

    ### Criterios de evaluación

    | Criterio | Puntos |
    |---|---|
    | Endpoints públicos funcionan correctamente (GET productos, géneros) | 1.5 |
    | Registro y login devuelven token válido | 1.5 |
    | Rutas protegidas requieren token (401 sin token) | 1.5 |
    | CRUD de productos y géneros completo y funcional | 2.0 |
    | Carrito: añadir, ver y eliminar items | 1.5 |
    | Pedidos: crear desde carrito y consultar historial | 1.5 |
    | Resources bien formateados (sin campos innecesarios) | 0.5 |
    | **Total** | **10** |

    ### Secuencia de pruebas obligatoria

    La colección Thunder Client debe incluir, en este orden:

    1. `POST /api/register` → registrar usuario de prueba
    2. `POST /api/login` → obtener token
    3. `GET /api/productos` → listar productos (sin token)
    4. `GET /api/productos/1` → ver producto (sin token)
    5. `GET /api/generos` → listar géneros (sin token)
    6. `POST /api/carrito` → añadir producto al carrito (con token)
    7. `POST /api/carrito` → añadir otro producto al carrito (con token)
    8. `GET /api/carrito` → ver carrito con total (con token)
    9. `DELETE /api/carrito/1` → eliminar un item (con token)
    10. `POST /api/pedidos` → confirmar pedido (con token)
    11. `GET /api/pedidos` → historial de pedidos (con token)
    12. `GET /api/pedidos/1` → detalle del pedido (con token)
    13. `POST /api/productos` → crear producto como admin (con token)
    14. `PUT /api/productos/1` → actualizar producto (con token)
    15. `DELETE /api/productos/1` → eliminar producto (con token)
    16. `POST /api/logout` → revocar token
    17. `GET /api/carrito` → intentar acceder con token revocado → `401`

    !!! tip "Exportar la colección"
        En Thunder Client → Collections → haz clic derecho en la colección → **Export**. Guarda el archivo `.json` y entrégalo junto con el código.
