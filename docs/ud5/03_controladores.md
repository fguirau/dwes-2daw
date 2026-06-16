# UD5.3 – Framework Laravel: Controladores

Los controladores son el componente que conecta las rutas con los modelos y las vistas. Con este tema completaremos el ciclo de creación de una aplicación web en Laravel siguiendo el patrón **MVC**.

---

## Preparando el entorno

1. Crea (o reutiliza del tema anterior) una base de datos llamada `laravel`.
2. Resetea las migraciones si las hubiera:

```bash
php artisan migrate:reset
```

3. Modifica la migración `create_users_table.php` añadiendo los campos `age`, `address` y `zipCode`:

```php
// database/migrations/xxxx_xx_xx_xxxxxx_create_users_table.php

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->unsignedInteger('age')->default(18);
    $table->string('address')->nullable();
    $table->unsignedBigInteger('zipCode')->nullable();
    $table->rememberToken();
    $table->timestamps();
});
```

4. Ejecuta las migraciones:

```bash
php artisan migrate
```

---

## Creación del primer circuito MVC

Vamos a crear una aplicación completa que muestre una lista de usuarios, pasando por todos los elementos del patrón MVC.

### Crear un Controlador

Un controlador recibe las solicitudes de los usuarios, consulta los modelos si necesita datos, y devuelve una respuesta adecuada (generalmente una vista).

Los controladores se nombran con el sufijo `Controller` y se ubican en `app/Http/Controllers/`. Para crearlo usamos Artisan:

```bash
php artisan make:controller UserController
```

Esto genera el archivo `app/Http/Controllers/UserController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    //
}
```

### Implementación básica: método `index()`

Añadimos un método `index()` con un mensaje de depuración inicial:

```php
public function index() {
    dd('Hola desde UserController@index');
}
```

### Asociar la ruta con el controlador

En `routes/web.php`:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', [UserController::class, 'index'])->name('user.index');
```

Accede a `http://localhost:8080/` y comprueba que aparece el mensaje de depuración.

---

## Crear la vista de usuarios

Creamos la carpeta `resources/views/users/` y dentro el archivo `index.blade.php`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuarios</title>
</head>
<body>
  <h1>Lista de usuarios</h1>
</body>
</html>
```

Actualizamos el controlador para que devuelva esta vista:

```php
public function index() {
    return view('users.index');
}
```

---

## Trabajar desde el controlador con el Modelo

### 1. Recuperar datos con `all()`

Importamos el modelo `User` al controlador y usamos `User::all()` para obtener todos los usuarios:

```php
use App\Models\User;

public function index() {
    $usuarios = User::all();
    return view('users.index');
}
```

### 2. Pasar datos a la vista

Para que la vista tenga acceso a los datos, debemos pasarlos en el segundo parámetro de `view()`. La forma recomendada es con `compact()`:

```php
// Forma explícita (array asociativo)
return view('users.index', ['usuarios' => $usuarios]);

// Forma simplificada con compact() — equivalente
return view('users.index', compact('usuarios'));

// Pasando múltiples variables
return view('users.index', compact('usuarios', 'var1', 'var2'));
```

El método `index()` completo queda así:

```php
public function index() {
    $usuarios = User::all();
    return view('users.index', compact('usuarios'));
}
```

### 3. Modificar la vista para mostrar datos

Usamos las directivas Blade para iterar y mostrar los datos:

```html
<h1>Listado de Usuarios</h1>

@if ($usuarios->isEmpty())
  <p>No hay usuarios disponibles.</p>
@else
  <ul>
    @foreach ($usuarios as $usuario)
      <li>{{ $loop->iteration }}. {{ $usuario->name }} ({{ $usuario->age }} años)</li>
    @endforeach
  </ul>
@endif
```

La variable especial **`$loop`** proporciona información sobre el estado del bucle:

| Propiedad | Descripción |
|---|---|
| `$loop->iteration` | Número de iteración actual (desde 1). |
| `$loop->index` | Índice actual del bucle (desde 0). |
| `$loop->count` | Número total de elementos. |
| `$loop->first` | `true` si es el primer elemento. |
| `$loop->last` | `true` si es el último elemento. |
| `$loop->remaining` | Cuántos elementos quedan por recorrer. |
| `$loop->depth` | Profundidad del bucle (en bucles anidados). |

#### Uso de `@switch`

Blade permite usar `@switch` para comprobaciones múltiples. Por ejemplo, clasificar usuarios por edad:

```html
@foreach ($usuarios as $usuario)
  @switch(true)
    @case($usuario->age < 18)
      <p>{{ $usuario->name }} es menor de edad.</p>
      @break
    @case($usuario->age >= 18 && $usuario->age <= 65)
      <p>{{ $usuario->name }} es adulto.</p>
      @break
    @default
      <p>{{ $usuario->name }} es jubilado.</p>
  @endswitch
@endforeach
```

---

## Crear datos de prueba

Para tener datos con los que probar, creamos un método `create()` en el controlador que inserta usuarios directamente en la BD.

Importamos la clase `Hash` para encriptar contraseñas:

```php
use Illuminate\Support\Facades\Hash;
```

El método crea tres usuarios usando dos técnicas distintas y luego redirige al listado:

```php
public function create() {
    // Técnica 1: instanciar el modelo y llamar a save()
    $usuario = new User();
    $usuario->name     = 'María García';
    $usuario->email    = 'mgarcia@example.com';
    $usuario->password = Hash::make('123456');
    $usuario->age      = 30;
    $usuario->address  = 'Calle Mayor 1';
    $usuario->zipCode  = 28080;
    $usuario->save();

    // Técnica 2: método create() del modelo (asignación masiva)
    User::create([
        'name'     => 'Juan Pérez',
        'email'    => 'jperez@example.com',
        'password' => Hash::make('password'),
        'age'      => 25,
        'address'  => 'Calle Falsa 123',
        'zipCode'  => 28080
    ]);

    User::create([
        'name'     => 'José Flores',
        'email'    => 'jflores@example.com',
        'password' => Hash::make('flores'),
        'age'      => 25,
        'address'  => 'Calle Falsa 123',
        'zipCode'  => 28080
    ]);

    // Redirigir al listado usando el nombre de ruta
    return redirect()->route('user.index');
}
```

!!! warning "Recuerda: `$fillable` en el modelo"
    Para usar `User::create()` debes añadir los campos al array `$fillable` del modelo `User`. De lo contrario, Eloquent bloqueará la asignación masiva:

    ```php
    protected $fillable = ['name', 'email', 'password', 'age', 'address', 'zipCode'];
    ```

!!! tip "Buena práctica"
    En la redirección usamos el **nombre de la ruta** (`user.index`) en lugar de la URL. Si la URL cambia en el futuro, el controlador no necesitará modificaciones.

Añadimos la ruta en `routes/web.php`:

```php
Route::get('/create', [UserController::class, 'create'])->name('user.create');
```

Accede a `http://localhost:8080/create`. Se crearán los usuarios y serás redirigido automáticamente al listado.

---

## Consultas avanzadas con Eloquent

### `where()`

Filtra resultados añadiendo una cláusula `WHERE` a la consulta:

```php
$usuarios = User::where('age', '>=', 18)->get();
```

Operadores disponibles:

| Operador | Descripción |
|---|---|
| `=` | Igual |
| `!=` | Diferente |
| `>` | Mayor que |
| `>=` | Mayor o igual que |
| `<` | Menor que |
| `<=` | Menor o igual que |
| `LIKE` | Coincide con patrón |
| `NOT LIKE` | No coincide con patrón |

### Encadenar `where()`

Equivale a añadir múltiples condiciones con `AND`:

```php
$usuarios = User::where('age', '>=', 18)
                ->where('zipCode', '=', 28080)
                ->get();
```

### Referencia de métodos de consulta

| Método | Descripción | Ejemplo |
|---|---|---|
| `whereBetween()` | Filtra por rango de valores. | `User::whereBetween('age', [18, 30])->get()` |
| `whereIn()` | Filtra por lista de valores. | `User::whereIn('zipCode', [28080, 28081])->get()` |
| `orWhere()` | Añade condición con `OR`. | `User::orWhere('age', '<', 18)->get()` |
| `orderBy()` | Ordena los resultados. | `User::orderBy('name')->get()` |
| `first()` | Devuelve el primer resultado. | `User::orderBy('name')->first()` |
| `find()` | Busca por ID. | `User::find(1)` |
| `findOrFail()` | Busca por ID, lanza excepción si no existe. | `User::findOrFail(1)` |

### Ejemplos de consultas avanzadas

```php
// Con AND
$usuarios = User::where('age', '>=', 18)
                ->where('zipCode', '=', 28080)->get();

// Con OR
$usuarios = User::where('age', '>=', 18)
                ->orWhere('zipCode', '=', 28080)->get();

// Con BETWEEN
$usuarios = User::whereBetween('age', [18, 30])->get();

// Con LIKE (nombre empieza por "Juan")
$usuarios = User::where('name', 'like', 'Juan%')->get();

// Con NOT LIKE
$usuarios = User::where('name', 'not like', 'Juan%')->get();

// Con whereIn
$usuarios = User::whereIn('zipCode', [28080, 28081, 28082])->get();

// Campos nulos / no nulos
$usuarios = User::whereNull('address')->get();
$usuarios = User::whereNotNull('address')->get();

// Con orWhereIn
$usuarios = User::where('age', '>=', 18)
                ->orWhereIn('zipCode', [28080, 28081])->get();

// Filtrar por fecha completa
$usuarios = User::whereDate('created_at', '2025-10-08')->get();

// Filtrar por día del mes
$usuarios = User::whereDay('created_at', 8)->get();

// Filtrar por mes
$usuarios = User::whereMonth('created_at', '10')->get();
```

Para probar estas consultas, sustituye la línea `User::all()` en el método `index()`:

```php
public function index() {
    $usuarios = User::where('age', '>=', 18)
                    ->where('zipCode', '=', 28080)
                    ->get();
    return view('users.index', compact('usuarios'));
}
```

---

## Acceso alternativo a la base de datos

Para casos más complejos donde Eloquent no sea suficiente, Laravel ofrece dos alternativas.

### SQL puro con `DB::raw()`

```php
use Illuminate\Support\Facades\DB;

// Consulta simple
$usuarios = DB::select(DB::raw('SELECT * FROM users'));

// Con JOIN
$usuarios = DB::select(DB::raw(
    'SELECT users.*, posts.title FROM users
     INNER JOIN posts ON users.id = posts.user_id'
));
```

### Query Builder con `DB::table()`

`DB::table()` devuelve una instancia del Query Builder, que permite construir consultas de forma fluida sin escribir SQL puro:

```php
$usuarios = DB::table('users')->get();
$usuarios = DB::table('users')->where('age', '>=', 18)->get();
```

Métodos disponibles:

| Método | Descripción | Ejemplo |
|---|---|---|
| `insert()` | Inserta un registro. | `DB::table('users')->insert([...])` |
| `update()` | Actualiza un registro. | `DB::table('users')->where('id', 1)->update([...])` |
| `delete()` | Elimina un registro. | `DB::table('users')->where('id', 1)->delete()` |
| `get()` | Recupera todos los registros. | `DB::table('users')->get()` |
| `find()` | Busca por ID. | `DB::table('users')->find(1)` |
| `first()` | Recupera el primer registro. | `DB::table('users')->first()` |
| `where()` | Filtra por condición. | `DB::table('users')->where('age', '>=', 18)->get()` |
| `orderBy()` | Ordena los resultados. | `DB::table('users')->orderBy('name')->get()` |
| `count()` | Cuenta registros. | `DB::table('users')->count()` |
| `sum()` | Suma un campo. | `DB::table('users')->sum('age')` |
| `avg()` | Promedio de un campo. | `DB::table('users')->avg('age')` |
| `max()` | Valor máximo de un campo. | `DB::table('users')->max('age')` |
| `min()` | Valor mínimo de un campo. | `DB::table('users')->min('age')` |

### Comparativa de técnicas

| Técnica | Ventajas | Inconvenientes |
|---|---|---|
| **Eloquent** | Código limpio y fácil de mantener. | Menos control sobre el SQL generado. |
| **DB Raw** | Control total del SQL. | Mayor riesgo de errores o inyecciones SQL. |
| **DB Table** | Punto intermedio: estructura clara, control aceptable. | — |

---

## Resumen

En este tema hemos completado el ciclo MVC completo en Laravel:

- Creación de **controladores** con Artisan.
- Conexión de **rutas → controlador → modelo → vista**.
- Recuperación y filtrado de datos con **Eloquent**.
- Alternativas de acceso a BD: **SQL puro** y **Query Builder**.
