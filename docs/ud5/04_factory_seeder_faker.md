# UD5.4 – Framework Laravel: Factory, Seeder y Faker

## Introducción

En los temas anteriores aprendimos a crear tablas con migraciones y a manipular datos manualmente. Sin embargo, en muchos proyectos reales necesitamos:

- Crear **datos iniciales**, como usuarios, roles o productos básicos.
- Generar **datos masivos de prueba** para desarrollo y pruebas funcionales.

Laravel nos ofrece tres herramientas clave para ello:

| Herramienta | Propósito |
|---|---|
| **Seeders** | Insertar datos conocidos y permanentes. |
| **Factories** | Generar datos aleatorios de forma automática. |
| **Faker** | Crear contenido realista (nombres, textos, precios...). |

---

## Preparación del entorno

Puedes continuar sobre el proyecto del tema anterior (limpiando los controladores, migraciones y vistas que ya no necesites) o crear un proyecto nuevo:

```bash
composer create-project laravel/laravel example-app
cd example-app
```

Configura el archivo `.env` con los mismos datos que usamos en UD5.2:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=alumno
DB_PASSWORD=alumno
```

!!! info "Si es un proyecto nuevo"
    Recuerda crear la base de datos y conceder los permisos al usuario `alumno`, igual que hicimos en UD5.2:

    ```sql
    CREATE DATABASE laravel;
    GRANT ALL PRIVILEGES ON laravel.* TO 'alumno'@'localhost';
    FLUSH PRIVILEGES;
    ```

Ejecuta la migración inicial:

```bash
php artisan migrate
```

---

## Crear la tabla y el modelo Product

Creamos el modelo y su migración a la vez:

```bash
php artisan make:model Product -m
```

Editamos la migración para definir la estructura de la tabla:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50);
    $table->string('short_description', 200);
    $table->text('description');
    $table->decimal('price', 8, 2)->default(20);
    $table->timestamps();
});
```

!!! info "`description` como `text`, no `string`"
    Como vimos en UD5.2, `text()` no admite longitud porque está pensado para contenidos largos de tamaño variable. La descripción de un producto es justo ese caso: si la guardáramos como `string('description', 500)`, cualquier descripción que supere los 500 caracteres —algo que tarde o temprano pasará al generar muchos datos de prueba con Faker— provocaría un error `Data too long for column 'description'`. Usando `text()` evitamos ese límite por completo.

!!! warning "Por qué `decimal` y no `float` para precios"
    `FLOAT`/`DOUBLE` almacenan los números en coma flotante binaria, que no puede representar exactamente la mayoría de cantidades decimales (`0.1 + 0.2` no es exactamente `0.3`). Con el tiempo esto produce errores de redondeo en sumas, totales o descuentos — justo lo que no quieres en el precio de un producto o en el total de un carrito. Para dinero, usa siempre `decimal(precisión, escala)`, que almacena el valor exacto. `decimal('price', 8, 2)` permite hasta 8 cifras en total, 2 de ellas decimales (hasta 999999.99).

Editamos el modelo `Product.php` para permitir asignación masiva en todos los campos:

```php
class Product extends Model
{
    protected $guarded = [];
}
```

!!! info "`$guarded = []`: sin restricciones de asignación masiva"
    En UD5.2 vimos `$fillable` (lista blanca) y `$guarded` (lista negra) como alternativas. `$guarded = []` es el caso extremo de la lista negra: una lista vacía de campos prohibidos significa que **todos** los campos son asignables masivamente, sin excepción. Para `Product` es razonable, porque no tiene campos sensibles (contraseñas, roles, permisos...) y nos ahorra mantener una lista de `$fillable`. Pero no lo uses por defecto en modelos como `User`, donde sí hay campos que no deberían poder rellenarse sin control desde un formulario.

Ejecutamos la migración:

```bash
php artisan migrate
```

---

## Circuito MVC para productos

Creamos el controlador:

```bash
php artisan make:controller ProductController
```

Añadimos la ruta en `routes/web.php`:

```php title="web.php"
use App\Http\Controllers\ProductController;

Route::get('/products', [ProductController::class, 'index'])->name('product.index');
```

Método `index()` del controlador:

```php title="ProductController.php"
use App\Models\Product;
use Illuminate\View\View;

public function index(): View
{
    $products = Product::all();
    return view('products.index', compact('products'));
}
```

### Layout base (`layouts/app.blade.php`)

```html title="layouts/app.blade.php"
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Mi Aplicación')</title>
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body>
  <header>
    <h1>Mi Tienda</h1>
  </header>
  <main>
    @yield('content')
  </main>
  <footer>
    <p>&copy; {{ date('Y') }} Mi Tienda</p>
  </footer>
</body>
</html>
```

### Vista `resources/views/products/index.blade.php`

Usamos `@forelse` para mostrar un mensaje si no hay productos:

```html title="products/index.blade.php"
@extends('layouts.app')

@section('title', 'Products')

@section('content')
  <div class="product-container">
    @forelse ($products as $product)
      <div class="card">
        <h2>{{ $product->name }}</h2>
        <p>{{ $product->short_description }}</p>
        <p><strong>${{ $product->price }}</strong></p>
      </div>
    @empty
      <p>No hay productos disponibles.</p>
    @endforelse
  </div>
@endsection
```

!!! tip "¿Te suena de UD5.3?"
    `@forelse ... @empty ... @endforelse` es azúcar sintáctico para el patrón que ya usamos en UD5.3 con `$usuarios->isEmpty()`: recorre la colección con `@foreach`, y si está vacía, muestra el bloque `@empty` en su lugar. Es exactamente lo mismo, en una sola directiva.

### CSS (`public/assets/css/app.css`)

```css
.product-container {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.card {
  border: 1px solid #ccc;
  padding: 15px;
  width: 200px;
  box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
}
```

---

## Seeders

Los **seeders** permiten poblar la base de datos con datos iniciales que queremos tener siempre disponibles.

### Crear y registrar un Seeder

```bash
php artisan make:seeder ProductSeeder
```

Editamos el archivo `database/seeders/ProductSeeder.php`:

```php title="ProductSeeder.php"
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create([
            'name'              => 'Producto 1',
            'short_description' => 'Descripción del producto 1',
            'description'       => 'Descripción larga del producto 1',
            'price'             => 20.00,
        ]);
        Product::create([
            'name'              => 'Producto 2',
            'short_description' => 'Descripción del producto 2',
            'description'       => 'Descripción larga del producto 2',
            'price'             => 30.00,
        ]);
        Product::create([
            'name'              => 'Producto 3',
            'short_description' => 'Descripción del producto 3',
            'description'       => 'Descripción larga del producto 3',
            'price'             => 40.00,
        ]);
        Product::create([
            'name'              => 'Producto 4',
            'short_description' => 'Descripción del producto 4',
            'description'       => 'Descripción larga del producto 4',
            'price'             => 50.00,
        ]);
    }
}
```

Registramos el seeder en `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call(ProductSeeder::class);
}
```

Ejecutamos todos los seeders registrados:

```bash
php artisan db:seed
```

Para ejecutar un seeder específico sin pasar por `DatabaseSeeder`:

```bash
php artisan db:seed --class=ProductSeeder
```

Accede a `http://localhost:8000/products` y verás los 4 productos en la vista.

---

## Factories

Una **factory** es una clase que define cómo generar datos de prueba para un modelo. Las factories permiten crear cientos de registros automáticamente en segundos.

### Crear una Factory

```bash
php artisan make:factory ProductFactory --model=Product
```

Esto crea el archivo `database/factories/ProductFactory.php`.

### Factory inicial (con datos aleatorios simples)

Importamos `Str` y definimos el método `definition()`:

```php title="ProductFactory.php"
use Illuminate\Support\Str;

public function definition(): array
{
    return [
        'name'              => Str::random(10),
        'short_description' => Str::random(30),
        'description'       => Str::random(50),
        'price'             => random_int(5, 100),
    ];
}
```

### Habilitar factories en el modelo

El modelo debe usar el trait `HasFactory`:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];
}
```

### Usar la factory en el Seeder

Modificamos `ProductSeeder.php` para generar 10 productos automáticamente:

```php title="ProductSeeder.php"
use App\Models\Product;

public function run(): void
{
    Product::factory()->count(10)->create();
}
```

Reiniciamos la base de datos y ejecutamos los seeders:

```bash
php artisan migrate:fresh
php artisan db:seed
```

---

## Faker

**Faker** genera datos aleatorios que simulan contenido real: nombres, frases, párrafos, precios... Laravel lo integra directamente en las factories a través de `$this->faker`.

### Métodos útiles de Faker

| Método | Descripción | Ejemplo |
|---|---|---|
| `name()` | Nombre completo | Juan Pérez |
| `email()` | Email realista | juan@mail.com |
| `sentence(n)` | Frase con n palabras | Lorem ipsum dolor sit amet. |
| `paragraph()` | Párrafo de texto | Lorem ipsum dolor sit amet... |
| `text($maxNbChars)` | Texto aleatorio de hasta `$maxNbChars` caracteres (longitud variable, no exacta) | Texto aleatorio |
| `word()` | Una palabra aleatoria | laptop |
| `words(n, true)` | n palabras unidas en una frase | quaint shiny gadget |
| `numberBetween(a, b)` | Número entero entre a y b | 47 |
| `randomFloat(decimales, a, b)` | Número decimal entre a y b | 47.32 |
| `randomElement([...])` | Valor aleatorio de un array | 'IT' |
| `boolean()` | `true` o `false` aleatorio | true |
| `date()` | Fecha aleatoria | 2023-01-15 |
| `company()` | Nombre de empresa | Grupo Pérez SL |

### Aplicar Faker a `ProductFactory`

```php
public function definition(): array
{
    return [
        'name'              => $this->faker->words(3, true),
        'short_description' => $this->faker->sentence(5),
        'description'       => $this->faker->paragraph(),
        'price'             => $this->faker->randomFloat(2, 10, 100),
    ];
}
```

Como `price` ahora es `decimal(8, 2)`, usamos `randomFloat(2, 10, 100)` en lugar de `numberBetween()`, para generar precios con dos decimales (`47.32`) en vez de enteros.

!!! tip "Nombres más temáticos con `randomElement()`"
    `$this->faker->words(3, true)` genera nombres genéricos tipo "quaint shiny gadget". Si tu proyecto es una tienda (por ejemplo, de videojuegos), puedes combinar tu propio vocabulario con `randomElement()` para que los datos de prueba encajen con la temática:

    ```php
    'name' => $this->faker->randomElement([
        'Shadow Quest', 'Pixel Racer', 'Galaxy Defenders',
        'Mystic Realms', 'Retro Arena', 'Neon Drift',
    ]) . ' ' . $this->faker->randomElement(['Deluxe', 'Edition', 'Remastered', 'II', 'Online']),
    ```

    De paso, repasamos `randomElement()`, que ya aparecía en la tabla anterior pero no se usaba en ningún ejemplo.

Reiniciamos la base de datos y ejecutamos seeders y migraciones en un solo comando:

```bash
php artisan migrate:fresh --seed
```

Los datos generados siguen siendo aleatorios, pero ahora tienen aspecto de contenido real, lo que resulta mucho más útil para prototipar vistas.

---

## Conclusiones

- Los **seeders** son útiles para poblar la base de datos con datos estáticos y conocidos.
- Las **factories** permiten generar cientos de registros en segundos.
- **Faker** mejora la calidad de los datos de prueba haciéndolos más realistas.
- Todo esto acelera el desarrollo y permite probar la aplicación con datos representativos desde el principio.
