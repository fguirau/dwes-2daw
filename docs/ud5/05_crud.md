# UD5.5 - Introducción a CRUD

## ¿Qué es CRUD?

El término **CRUD** corresponde a las cuatro operaciones básicas sobre datos:

| Operación | Acción |
|---|---|
| **C**reate | Insertar nuevos datos |
| **R**ead | Consultar y visualizar datos |
| **U**pdate | Modificar datos existentes |
| **D**elete | Borrar datos |

En Laravel, implementar un CRUD completo nos permite comprender cómo **Modelos**, **Controladores** y **Vistas** interactúan entre sí. Además, este tema nos sirve para aprender el funcionamiento de los **formularios** en Laravel.

---

## Circuito MVC rápido para rutas dinámicas

Antes de desarrollar el CRUD completo, veamos cómo crear un circuito MVC básico con rutas dinámicas.

### Modelo y migración

Vamos a usar una tabla `notas` con los campos:

- `id` — entero, autoincremental
- `titulo` — string
- `descripcion` — text
- `fecha` — date
- `realizada` — boolean

```bash
php artisan make:model Nota -m
```

En la migración creada:

```php
public function up()
{
    Schema::create('notas', function (Blueprint $table) {
        $table->id();
        $table->string('titulo');
        $table->text('descripcion');
        $table->date('fecha');
        $table->boolean('realizada')->default(false);
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('notas');
}
```

```bash
php artisan migrate
```

### Modelo Nota

Definimos los campos con asignación masiva permitida:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    protected $fillable = ['titulo', 'descripcion', 'fecha', 'realizada'];
}
```

!!! info "¿Y `$guarded`?"
    Como vimos en UD5.2, `$fillable` y `$guarded` son alternativas, no se combinan. Con `$fillable` definido, cualquier otro campo —incluido `id`— queda protegido automáticamente frente a la asignación masiva. No hace falta añadir `$guarded = ['id']`.

### Controlador

```bash
php artisan make:controller NotaController
```

Método de prueba para recibir un parámetro dinámico:

```php
public function show($id)
{
    return view('notas.show', compact('id'));
}
```

### Ruta dinámica

En `routes/web.php`:

```php
use App\Http\Controllers\NotaController;

Route::get('/nota/{id}', [NotaController::class, 'show'])->name('nota.show');
```

### Vista de prueba

`resources/views/notas/show.blade.php`:

```blade
<h1>Detalle de Nota</h1>
<p>El ID de la nota es: {{ $id }}</p>
```

Accediendo a `/nota/2` se muestra: *El ID de la nota es: 2*.

!!! warning "Antes de seguir: retira esta ruta de prueba"
    Esta ruta (`/nota/{id}`) y la vista `notas/show.blade.php` eran solo para practicar cómo Laravel captura un parámetro dinámico de la URL. Antes de continuar con el CRUD completo, **elimina esta ruta** de `routes/web.php`. Hay dos motivos:

    1. En el CRUD real usaremos `nota.mostrar` (más adelante), con inyección de modelo, que es la versión completa de esto mismo — no necesitamos mantener las dos.
    2. Si la dejas, **el orden importa**: `/nota/{id}` y `/nota/crear` tienen la misma forma (`/nota/` + un segmento). Si `/nota/{id}` se define primero, Laravel la hará coincidir antes y `/nota/crear` quedará "tapada" — al pulsar "Crear Nota" verías el mensaje "El ID de la nota es: crear" en lugar del formulario. Regla general: las rutas con parámetros (`{id}`) deben declararse **después** de las rutas estáticas más específicas, o eliminarse si ya no se usan.

---

## Desarrollo del CRUD completo

### Layout base

`resources/views/layouts/notas.blade.php`:

```blade title="layouts/notas.blade.php"
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body>
    <header>
        <h1>Mi Aplicación de Notas</h1>
        <nav>
            <a href="{{ route('nota.index') }}">Inicio</a> |
            <a href="{{ route('nota.crear') }}">Crear Nota</a>
        </nav>
    </header>
    <main class="container">
        @yield('content')
    </main>
</body>
</html>
```

---

### R — Listar todas las notas

**Ruta:**

```php
Route::get('/', [NotaController::class, 'index'])->name('nota.index');
```

!!! warning "Sustituye la ruta de bienvenida, no la dupliques"
    Un proyecto Laravel nuevo ya incluye `Route::get('/', function () { return view('welcome'); });` en `routes/web.php`. **Sustituye esa línea** por la de arriba; no añadas una segunda ruta para `/`. Si dejas las dos, gana la primera que aparezca en el archivo, y `nota.index` podría no llegar a ejecutarse nunca aunque el código sea correcto.

**Controlador:**

```php
use App\Models\Nota;

public function index()
{
    $notas = Nota::all();
    return view('notas.index', compact('notas'));
}
```

**Vista** `resources/views/notas/index.blade.php`:

```blade title="notas/index.blade.php"
@extends('layouts.notas')
@section('title', 'Listado de Notas')
@section('content')
    <h2>Listado de Notas</h2>

    @forelse ($notas as $nota)
        <div>
            <h3><a href="{{ route('nota.mostrar', $nota->id) }}">{{ $nota->titulo }}</a></h3>
            <p>{{ $nota->descripcion }}</p>
            <small>{{ $nota->fecha }}</small>
            <div>
                <a href="{{ route('nota.editar', $nota->id) }}" class="btn btn-warning">Editar</a>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                        data-bs-target="#modalEliminar"
                        data-id="{{ $nota->id }}"
                        data-titulo="{{ $nota->titulo }}">
                    Eliminar
                </button>
            </div>
        </div>
    @empty
        <p>No hay notas disponibles.</p>
    @endforelse
@endsection
```

---

### C — Crear una nueva nota

Necesitamos tres elementos: ruta del formulario, método del controlador y vista.

**Ruta:**

```php
Route::get('/nota/crear', [NotaController::class, 'crear'])->name('nota.crear');
```

**Controlador:**

```php
public function crear()
{
    return view('notas.crear');
}
```

**Vista** `resources/views/notas/crear.blade.php`:

```blade title="notas/crear.blade.php"
@extends('layouts.notas')
@section('title', 'Crear Nueva Nota')
@section('content')
    <h2>Crear Nueva Nota</h2>
    <div class="row">
        <div class="col-md-6">
            <form action="{{ route('nota.guardar') }}" method="POST">
                @csrf
                <div>
                    <label>Título:</label>
                    <input type="text" name="titulo"
                           class="form-control @error('titulo') is-invalid @enderror"
                           value="{{ old('titulo') }}">
                    @error('titulo')
                        <div class="text-danger"><p>{{ $message }}</p></div>
                    @enderror
                </div>
                <div>
                    <label>Descripción:</label>
                    <textarea name="descripcion" class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <div class="text-danger"><p>{{ $message }}</p></div>
                    @enderror
                </div>
                <div>
                    <label>Fecha:</label>
                    <input type="date" name="fecha"
                           class="form-control @error('fecha') is-invalid @enderror"
                           value="{{ old('fecha') }}">
                    @error('fecha')
                        <div class="text-danger"><p>{{ $message }}</p></div>
                    @enderror
                </div>
                <div>
                    <label>Completada:</label>
                    <input type="checkbox" name="realizada" class="form-check-input"
                           value="1" {{ old('realizada') ? 'checked' : '' }}>
                </div>
                <div>
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <a href="{{ route('nota.index') }}" class="btn btn-danger">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
```

!!! info "¿Por qué el `<textarea>` va todo en una línea?"
    A diferencia de `<input>`, un `<textarea>` conserva **literalmente** todo lo que escribas entre sus etiquetas, incluyendo saltos de línea y espacios de indentación. Si escribieras `{{ old('descripcion') }}` en su propia línea con sangría, ese salto de línea y esos espacios formarían parte del valor del campo, y se guardarían en la base de datos cada vez que el usuario envíe el formulario sin tocar la descripción. Por eso `{{ old('descripcion') }}` va pegado a `<textarea ...>` y a `</textarea>`, sin saltos de línea entre medio.

!!! info "¿Qué es CSRF?"
    Laravel genera un **token único** por sesión de usuario que se incluye en cada formulario con `@csrf`. Al enviar el formulario, Laravel verifica que el token coincida con el de la sesión. Si no coinciden, lanza un error **419 (Page Expired)**. Esto previene ataques donde un tercero envíe formularios en nombre del usuario sin su consentimiento.

**Ruta para guardar:**

```php
Route::post('/nota/guardar', [NotaController::class, 'guardar'])->name('nota.guardar');
```

**Controlador** — dos formas equivalentes (todavía sin validación, la añadiremos más adelante):

```php
// Opción 1: campo a campo
public function guardar(Request $request)
{
    $nota = new Nota();
    $nota->titulo      = $request->input('titulo');
    $nota->descripcion = $request->input('descripcion');
    $nota->fecha       = $request->input('fecha');
    $nota->realizada   = $request->boolean('realizada');
    $nota->save();
    return redirect()->route('nota.index');
}

// Opción 2: con create() (más concisa)
public function guardar(Request $request)
{
    Nota::create($request->all());
    return redirect()->route('nota.index');
}
```

!!! tip "El checkbox sin marcar"
    Si el checkbox `realizada` no está marcado, el navegador **no envía ese campo** en la petición. `$request->boolean('realizada')` devuelve `false` en ese caso (no falla ni lanza error). En la Opción 2, al no venir `realizada` en `$request->all()`, Eloquent usa el valor `default(false)` que definimos en la migración. Ambas opciones funcionan correctamente para los dos casos (marcado / sin marcar).

---

### U — Editar y actualizar una nota

**Ruta para mostrar el formulario:**

```php
Route::get('/nota/editar/{nota}', [NotaController::class, 'editar'])->name('nota.editar');
```

**Controlador** — dos formas:

```php
// Opción 1: buscar por ID manualmente
public function editar($id)
{
    $nota = Nota::findOrFail($id);
    return view('notas.editar', compact('nota'));
}

// Opción 2: inyección de modelo (recomendada)
public function editar(Nota $nota)
{
    return view('notas.editar', compact('nota'));
}
```

!!! tip "Inyección de modelo"
    Cuando declaramos `Nota $nota` como parámetro, Laravel busca automáticamente el registro por su ID y lo inyecta en el método. Es la forma recomendada al trabajar con modelos.

**Vista** `resources/views/notas/editar.blade.php`:

```blade title="notas/editar.blade.php"
@extends('layouts.notas')
@section('title', 'Editar Nota')
@section('content')
    <h2>Editar Nota</h2>
    <div class="row">
        <div class="col-md-6">
            <form action="{{ route('nota.actualizar', $nota->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div>
                    <label>Título:</label>
                    <input type="text" name="titulo"
                           class="form-control @error('titulo') is-invalid @enderror"
                           value="{{ $nota->titulo }}">
                    @error('titulo')
                        <div class="text-danger"><p>{{ $message }}</p></div>
                    @enderror
                </div>
                <div>
                    <label>Descripción:</label>
                    <textarea name="descripcion" class="form-control @error('descripcion') is-invalid @enderror">{{ $nota->descripcion }}</textarea>
                    @error('descripcion')
                        <div class="text-danger"><p>{{ $message }}</p></div>
                    @enderror
                </div>
                <div>
                    <label>Fecha:</label>
                    <input type="date" name="fecha"
                           class="form-control @error('fecha') is-invalid @enderror"
                           value="{{ $nota->fecha }}">
                    @error('fecha')
                        <div class="text-danger"><p>{{ $message }}</p></div>
                    @enderror
                </div>
                <div>
                    <label>Completada:</label>
                    <input type="checkbox" name="realizada" class="form-check-input"
                           value="1" {{ $nota->realizada ? 'checked' : '' }}>
                </div>
                <div>
                    <button type="submit" class="btn btn-success">Actualizar</button>
                    <a href="{{ route('nota.index') }}" class="btn btn-danger">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
```

!!! note "`value=\"{{ $nota->fecha }}\"`: por qué funciona (y cuándo dejaría de hacerlo)"
    Como el modelo `Nota` no tiene `$casts` para `fecha`, Eloquent devuelve el valor tal cual lo entrega la base de datos para una columna `DATE`: una cadena `"YYYY-MM-DD"`, justo el formato que espera `<input type="date">`. Si en el futuro añadís `'fecha' => 'date'` a `$casts` (algo razonable, para poder usar métodos de Carbon como `format()` o `addDays()`), `$nota->fecha` pasaría a ser un objeto Carbon, y su conversión a texto por defecto incluye la hora (`"2026-01-15 00:00:00"`), lo que rompería este input. En ese caso habría que cambiar a `value="{{ $nota->fecha->format('Y-m-d') }}"`.

!!! warning "@method('PUT')"
    Los formularios HTML solo soportan `GET` y `POST`. Para simular `PUT` o `DELETE`, Laravel proporciona la directiva `@method('PUT')`, que debe ir dentro del formulario, antes de los inputs.

**Ruta para actualizar:**

```php
Route::put('/nota/actualizar/{nota}', [NotaController::class, 'actualizar'])->name('nota.actualizar');
```

**Controlador** (todavía sin validación, la añadiremos más adelante):

```php
public function actualizar(Request $request, Nota $nota)
{
    $datos = $request->all();
    $datos['realizada'] = $request->boolean('realizada');
    $nota->update($datos);
    return redirect()->route('nota.index')->with('success', 'La nota se ha actualizado correctamente.');
}
```

---

### R — Mostrar una nota individual

**Ruta:**

```php
Route::get('/nota/mostrar/{nota}', [NotaController::class, 'mostrar'])->name('nota.mostrar');
```

**Controlador:**

```php
public function mostrar(Nota $nota)
{
    return view('notas.mostrar', compact('nota'));
}
```

**Vista** `resources/views/notas/mostrar.blade.php`:

```blade
@extends('layouts.notas')
@section('title', 'Detalle de Nota')
@section('content')
    <h2>{{ $nota->titulo }}</h2>
    <p>{{ $nota->descripcion }}</p>
    <p>Fecha: {{ $nota->fecha }}</p>
    <p>Estado: {{ $nota->realizada ? 'Completada' : 'Pendiente' }}</p>
    <a href="{{ route('nota.index') }}">Volver</a>
@endsection
```

---

### D — Eliminar una nota

**Ruta:**

```php
Route::delete('/nota/eliminar/{nota}', [NotaController::class, 'eliminar'])->name('nota.eliminar');
```

**Controlador:**

```php
public function eliminar(Nota $nota)
{
    $nota->delete();
    return redirect()->route('nota.index')->with('success', 'La nota se ha eliminado correctamente.');
}
```

El método `delete()` elimina el registro de la base de datos.

---

## Validación de datos

### ¿Por qué validar?

!!! danger "Importante"
    Siempre debemos validar los datos **antes de almacenarlos** y **antes de actualizarlos**. Nunca debemos asumir que lo que llega del formulario es seguro o correcto.

### Clase FormRequest personalizada

```bash
php artisan make:request NotaRequest
```

Esto crea `app/Http/Requests/NotaRequest.php`.

#### Método `authorize()`

Define si el usuario tiene permiso para hacer la petición. Lo dejamos en `true`:

```php
public function authorize(): bool
{
    return true;
}
```

!!! warning "Importante"
    Si este método devuelve `false`, la validación no se ejecutará y se lanzará un error **403 (Forbidden)**.

#### Método `rules()`

```php
public function rules(): array
{
    return [
        'titulo'      => 'required|string|max:255',
        'descripcion' => 'required|string|min:10',
        'fecha'       => 'required|date',
        'realizada'   => 'nullable|boolean',
    ];
}
```

#### Método `messages()` — mensajes personalizados

```php
public function messages(): array
{
    return [
        'titulo.required'      => 'El título es obligatorio.',
        'titulo.max'           => 'El título no puede tener más de 255 caracteres.',
        'descripcion.required' => 'La descripción es obligatoria.',
        'descripcion.min'      => 'La descripción debe tener al menos 10 caracteres.',
        'fecha.required'       => 'La fecha es obligatoria.',
        'fecha.date'           => 'La fecha no tiene un formato válido.',
    ];
}
```

!!! info "¿Y las reglas que no aparecen aquí?"
    No es necesario añadir un mensaje para cada combinación `campo.regla`. Para las que no personalices (por ejemplo, `realizada.boolean`), Laravel mostrará su mensaje por defecto, en inglés salvo que instales el paquete de traducción al español para los archivos de validación.

### Uso en el controlador

Ahora que existe `NotaRequest`, actualizamos **los dos métodos que reciben datos de formularios** (`guardar()` y `actualizar()`) para usarla. En lugar de `Request $request`, usamos `NotaRequest $request`: Laravel valida automáticamente antes de ejecutar el método, y si la validación falla, redirige de vuelta al formulario con los errores (los `@error` y `old()` que ya tenemos en las vistas se encargan del resto).

Usamos `$request->validated()` en lugar de `$request->all()`: devuelve únicamente los campos que han pasado las reglas de `rules()`, sin depender de que `$fillable` "filtre" el resto.

```php
use App\Http\Requests\NotaRequest;

public function guardar(NotaRequest $request)
{
    $datos = $request->validated();
    $datos['realizada'] = $request->boolean('realizada');
    Nota::create($datos);
    return redirect()->route('nota.index')->with('success', 'La nota se ha creado correctamente.');
}

public function actualizar(NotaRequest $request, Nota $nota)
{
    $datos = $request->validated();
    $datos['realizada'] = $request->boolean('realizada');
    $nota->update($datos);
    return redirect()->route('nota.index')->with('success', 'La nota se ha actualizado correctamente.');
}
```

!!! info "¿Por qué seguimos tratando `realizada` aparte?"
    La regla `'realizada' => 'nullable|boolean'` permite que el campo no venga en la petición (checkbox sin marcar), pero entonces `validated()` tampoco lo incluye. Por eso lo añadimos explícitamente con `$request->boolean('realizada')`, que siempre devuelve `true` o `false` independientemente de si el campo llegó o no. Es el mismo método para crear y para actualizar — ya no necesitamos las otras variantes (`input('realizada') ? 1 : 0`, `has('realizada')`...) que vimos antes de tener validación.

---

## Mensajes de éxito y errores

### Mostrar mensajes de éxito

En `index.blade.php`, añadimos antes del listado:

```blade
@if (session('success'))
    <div class="alert alert-success">
        <p>{{ session('success') }}</p>
    </div>
@endif
```

### Mostrar errores de validación

Para resaltar el campo con error y mostrar el mensaje:

```blade
<input name="titulo"
       class="form-control @error('titulo') is-invalid @enderror"
       value="{{ old('titulo') }}">

@error('titulo')
    <div class="text-danger">
        <p>{{ $message }}</p>
    </div>
@enderror
```

### Recordar valores enviados con `old()`

Al volver al formulario tras un error, los campos pierden su valor. Para recuperarlos usamos `old()`:

```blade
{{-- Input de texto --}}
<input name="titulo" value="{{ old('titulo') }}">

{{-- Textarea (sin saltos de línea entre las etiquetas, como vimos antes) --}}
<textarea name="descripcion">{{ old('descripcion') }}</textarea>
```

!!! tip "Quitar `required` del HTML"
    Para que Laravel pueda ejecutar la validación en `NotaRequest`, es necesario **quitar los atributos `required`** del HTML del formulario. Si los dejamos, el navegador impide el envío y Laravel nunca recibe los datos para validar.

---

## Confirmación de eliminación con modal Bootstrap

### Modal en `index.blade.php`

Añadimos al final de la vista:

```blade title="index.blade.php"
<!-- Modal Confirmación Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1"
     aria-labelledby="modalEliminarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro que deseas eliminar la nota
                <strong id="tituloNota"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <form id="formEliminar" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Sí, eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalEliminar = document.getElementById('modalEliminar');
        modalEliminar.addEventListener('show.bs.modal', function (event) {
            var button    = event.relatedTarget;
            var notaId    = button.getAttribute('data-id');
            var titulo    = button.getAttribute('data-titulo');
            document.getElementById('tituloNota').textContent = titulo;
            document.getElementById('formEliminar').action = '/nota/eliminar/' + notaId;
        });
    });
</script>
```

---

## Adelanto: relaciones entre modelos

!!! info "Esto lo veremos en detalle en UD5.6"
    Las dos secciones siguientes no se aplican al proyecto de `notas` que acabamos de construir — son un adelanto de las **relaciones entre modelos**, que veremos en profundidad en el próximo tema. Los ejemplos usan `categories` y la relación `product`-`user` que ya conocéis de UD5.4 (el modelo `Product`), para que cuando lleguéis a UD5.6 estas piezas ya os resulten familiares.

### Claves ajenas

Las claves ajenas se definen usando `foreignId()` o `foreign()`:

```php
// Método 1: foreignId (recomendado)
$table->foreignId('category_id')
      ->constrained()          // referencia a tabla categories
      ->onDelete('cascade');   // eliminar en cascada

// Método 2: foreign (más control)
$table->unsignedBigInteger('category_id');
$table->foreign('category_id')
      ->references('id')
      ->on('categories')
      ->onDelete('cascade');
```

!!! tip "Convención de nombres"
    Laravel busca automáticamente la tabla relacionada asumiendo que el nombre de la columna foránea sigue la convención `{tabla_singular}_id`. Por ejemplo, `category_id` referencia a `categories`. Si tu columna no sigue esta convención, usa el método 2 con `references()` y `on()`.

### Tabla puente para relaciones Muchos a Muchos

Las relaciones muchos a muchos requieren una **tabla pivote** intermedia con las claves foráneas de ambas tablas:

```php
Schema::create('product_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamps();

    // Evitar duplicados
    $table->unique(['product_id', 'user_id']);
});
```

!!! warning "Nombre de la tabla pivote"
    La tabla pivote debe combinar los dos nombres en **orden alfabético** separados por `_`. En este caso `product_user` es correcto porque "product" va antes que "user" alfabéticamente. Esto permite que Laravel reconozca la tabla automáticamente al definir las relaciones en los modelos.

---

## Resumen de rutas del CRUD

| Método HTTP | URL | Nombre | Acción |
|---|---|---|---|
| GET | `/` | `nota.index` | Listar todas las notas |
| GET | `/nota/crear` | `nota.crear` | Mostrar formulario de creación |
| POST | `/nota/guardar` | `nota.guardar` | Guardar nueva nota |
| GET | `/nota/mostrar/{nota}` | `nota.mostrar` | Ver detalle de una nota |
| GET | `/nota/editar/{nota}` | `nota.editar` | Mostrar formulario de edición |
| PUT | `/nota/actualizar/{nota}` | `nota.actualizar` | Actualizar nota existente |
| DELETE | `/nota/eliminar/{nota}` | `nota.eliminar` | Eliminar nota |
