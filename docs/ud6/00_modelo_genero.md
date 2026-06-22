# Complemento UD6 — Modelo Genero

Antes de trabajar con la API de géneros necesitamos crear el modelo `Genero` completo: migración, modelo Eloquent, relación con `Producto` y datos de prueba.

---

## 1. Migración

```bash
php artisan make:migration create_generos_table
```

```php
// database/migrations/xxxx_xx_xx_create_generos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generos');
    }
};
```

!!! warning "Orden de las migraciones"
    La tabla `generos` debe crearse **antes** que la tabla `productos`, porque `productos` tendrá una clave foránea `genero_id` que referencia a `generos`. Verifica que el timestamp del nombre de archivo de `create_generos_table` sea anterior al de `create_productos_table`. Si no lo es, renombra el archivo ajustando la fecha.

---

## 2. Añadir `genero_id` a la tabla `productos`

Si la tabla `productos` ya existe sin la columna `genero_id`, crea una migración adicional:

```bash
php artisan make:migration add_genero_id_to_productos_table
```

```php
// database/migrations/xxxx_xx_xx_add_genero_id_to_productos_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('genero_id')
                  ->nullable()
                  ->constrained('generos')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Genero::class);
            $table->dropColumn('genero_id');
        });
    }
};
```

!!! info "`nullable()` y `nullOnDelete()`"
    Definimos `genero_id` como nullable para que los productos existentes no rompan la migración al añadir la columna. `nullOnDelete()` hace que si se borra un género, sus productos queden con `genero_id = null` en lugar de borrar en cascada.

Si la tabla `productos` **aún no existe**, añade directamente `genero_id` en `create_productos_table`:

```php
$table->foreignId('genero_id')->nullable()->constrained('generos')->nullOnDelete();
```

Ejecuta las migraciones:

```bash
php artisan migrate
```

---

## 3. Modelo Genero

```bash
php artisan make:model Genero
```

```php
// app/Models/Genero.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Genero extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    /**
     * Un género tiene muchos productos.
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
```

---

## 4. Relación inversa en el modelo Producto

Añade la relación `belongsTo` en el modelo `Producto`:

```php
// app/Models/Producto.php

use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Dentro de la clase Producto:

/**
 * Un producto pertenece a un género.
 */
public function genero(): BelongsTo
{
    return $this->belongsTo(Genero::class);
}
```

---

## 5. Seeder de géneros

```bash
php artisan make:seeder GeneroSeeder
```

```php
// database/seeders/GeneroSeeder.php

namespace Database\Seeders;

use App\Models\Genero;
use Illuminate\Database\Seeder;

class GeneroSeeder extends Seeder
{
    public function run(): void
    {
        $generos = [
            'Acción',
            'Aventura',
            'RPG',
            'Deportes',
            'Estrategia',
            'Simulación',
            'Terror',
            'Lucha',
        ];

        foreach ($generos as $nombre) {
            Genero::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
```

Registra el seeder en `DatabaseSeeder`:

```php
// database/seeders/DatabaseSeeder.php

public function run(): void
{
    $this->call([
        GeneroSeeder::class,
        ProductoSeeder::class, // debe ir después de GeneroSeeder
    ]);
}
```

Ejecuta el seeder:

```bash
php artisan db:seed --class=GeneroSeeder
```

O si quieres resetear y resembrar todo:

```bash
php artisan migrate:fresh --seed
```

---

## 6. Factory de géneros (opcional, para tests)

```bash
php artisan make:factory GeneroFactory --model=Genero
```

```php
// database/factories/GeneroFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GeneroFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->randomElement([
                'Acción', 'Aventura', 'RPG', 'Deportes',
                'Estrategia', 'Simulación', 'Terror', 'Lucha',
                'Puzzle', 'Carreras', 'Plataformas',
            ]),
        ];
    }
}
```

---

## 7. Verificación

Comprueba que todo funciona:

```bash
# Las tablas existen y tienen la estructura correcta
php artisan tinker
>>> \App\Models\Genero::all()
>>> \App\Models\Genero::first()->productos
```

Y desde Thunder Client:

```
GET http://localhost:8000/api/generos
```

Debe devolver el listado de géneros con `200 OK`.
