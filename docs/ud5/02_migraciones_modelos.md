# UD5.2 – Framework Laravel: Migraciones y Modelos

## Introducción a las migraciones

Las **migraciones** son una forma de controlar la estructura de la base de datos de tu aplicación a través de archivos PHP versionados. Permiten crear, modificar o eliminar tablas de forma organizada y controlada.

!!! info "Importante"
    Las migraciones funcionan como un "control de versiones" para la base de datos, similar a Git pero para el esquema de datos.

Hay que evitar modificar la base de datos directamente. En su lugar, se deben crear migraciones para reflejar los cambios. De esta forma se mantiene un historial de cambios y se facilita la colaboración entre desarrolladores.

---

## Configuración de la BD en Laravel

Laravel utiliza el archivo **`.env`** para definir los datos de conexión a la base de datos.

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_base_datos
DB_USERNAME=usuario
DB_PASSWORD=contraseña
```

| Variable | Descripción |
|---|---|
| `DB_CONNECTION` | Tipo de base de datos (`mysql`, `pgsql`, `sqlite`, etc.) |
| `DB_HOST` | Dirección del servidor de base de datos |
| `DB_PORT` | Puerto de conexión a la base de datos |
| `DB_DATABASE` | Nombre de la base de datos |
| `DB_USERNAME` | Usuario para conectarse a la base de datos |
| `DB_PASSWORD` | Contraseña del usuario |

!!! warning "Seguridad"
    El archivo `.env` está en la raíz del proyecto y **nunca** debe subirse al repositorio Git (por eso está incluido en `.gitignore`). La información sensible (contraseñas, claves de API, tokens...) vive únicamente en `.env`.

### El archivo `.env.example`

Laravel incluye, en la raíz del proyecto, un archivo `.env.example` que actúa como **plantilla**: contiene las mismas claves que `.env` (`DB_CONNECTION`, `DB_HOST`, etc.) pero sin datos sensibles — valores vacíos, de ejemplo o genéricos. A diferencia de `.env`, **este archivo sí se sube al repositorio**, para que cualquier persona que clone el proyecto sepa qué variables necesita configurar.

El flujo habitual es:

1. Al clonar o desplegar el proyecto, copiamos la plantilla: `cp .env.example .env`
2. Rellenamos `.env` con los datos reales (usuario, contraseña, claves...). Este archivo no se sube al repositorio.
3. Si en algún momento añades una variable nueva a tu `.env` (por ejemplo, una clave de API), añade también esa misma clave a `.env.example`, pero con un valor vacío o de ejemplo, para que el resto del equipo sepa que existe.

### Bases de datos soportadas

Por defecto, Laravel soporta MySQL/MariaDB, PostgreSQL, SQLite y SQL Server.

Para este proyecto usaremos MySQL. Crea un nuevo proyecto llamado `libreta` y configura el `.env` con estos parámetros:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=alumno
DB_PASSWORD=alumno
```

Asegúrate de tener creada en MySQL una base de datos llamada `laravel` y un usuario `alumno` con todos los privilegios sobre ella:

```sql
GRANT ALL PRIVILEGES ON laravel.* TO 'alumno'@'localhost';
FLUSH PRIVILEGES;
```

!!! note "Si tu MySQL corre en un contenedor Docker"
    Si la base de datos es un servicio Docker (por ejemplo, un `docker-compose.yml` con un servicio llamado `mysql`), `DB_HOST` debe ser el nombre de ese servicio (`DB_HOST=mysql`) en lugar de `127.0.0.1`, porque la aplicación accede a través de la red interna de Docker. En ese caso, el `GRANT` también debe adaptarse: la conexión no llegará como `'alumno'@'localhost'`, sino desde otra dirección de esa red, por lo que normalmente se usa `'alumno'@'%'` (cualquier host).

---

## Migraciones por defecto en Laravel

Cuando creamos un proyecto Laravel, ya existen varias migraciones iniciales en `database/migrations/`:

| Migración | Tablas que crea |
|---|---|
| `0001_01_01_000000_create_users_table.php` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table.php` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table.php` | `jobs`, `job_batches`, `failed_jobs` |

Cada archivo incluye una **marca de tiempo** en su nombre que asegura que las migraciones se apliquen en el orden correcto.

!!! info "¿Y la tabla `personal_access_tokens`?"
    Esa tabla la crea **Sanctum**, y no viene instalada por defecto en un proyecto nuevo. Se añade al ejecutar `php artisan install:api`, que es el mismo comando que genera el fichero `routes/api.php` del que hablamos en UD5.1. Si tu proyecto necesita una API con autenticación por tokens, ese es el momento de ejecutarlo.

### Ejecutar migraciones

```bash
php artisan migrate
```

Esto ejecutará todas las migraciones pendientes y creará las tablas correspondientes (`users`, `sessions`, `cache`, `jobs`, `failed_jobs`, etc.) en la base de datos `laravel`.

---

## La tabla `migrations`

Además de las tablas del proyecto, Laravel crea una tabla especial llamada `migrations` que lleva el registro de qué migraciones se han ejecutado. Sus campos son:

- **`id`**: Identificador único de la migración.
- **`migration`**: Nombre de la migración.
- **`batch`**: Número de lote al que pertenece. Se incrementa con cada ejecución de `php artisan migrate`, lo que permite revertir un grupo de migraciones a la vez.

Ejemplo del contenido tras la primera ejecución:

| id | migration | batch |
|---|---|---|
| 1 | 0001_01_01_000000_create_users_table | 1 |
| 2 | 0001_01_01_000001_create_cache_table | 1 |
| 3 | 0001_01_01_000002_create_jobs_table | 1 |

Cada vez que ejecutas `php artisan migrate`, Laravel consulta esta tabla y solo aplica las migraciones que aún no están registradas. Si vuelves a ejecutarlo sin cambios, verás: `INFO  Nothing to migrate.`

!!! tip "La analogía con Git, un poco más allá"
    - `up()` es como aplicar un commit: el cambio que quieres introducir en la base de datos.
    - `down()` es como revertir ese commit: deshace exactamente lo que hizo `up()`.
    - La tabla `migrations` es el equivalente al historial de commits ya aplicados.
    - El campo `batch` agrupa las migraciones ejecutadas juntas en una misma llamada a `php artisan migrate`, como si fueran "el mismo push".
    - `php artisan migrate:rollback` deshace el último lote (batch), igual que revertirías el último conjunto de cambios.

    Por ejemplo, si más adelante creamos la migración de `notes` y ejecutamos `php artisan migrate`, aparecerá una nueva fila con `batch = 2`:

    | id | migration | batch |
    |---|---|---|
    | 4 | 2026_01_10_000000_create_notes_table | 2 |

---

## Crear una nueva migración

!!! info "Creación de componentes Laravel"
    Para crear la mayoría de componentes de Laravel (migraciones, modelos, controladores, etc.) se usa el comando `php artisan make:componente`. **Nunca** debes crear estos archivos manualmente, ya que Laravel proporciona una forma rápida y sencilla de crearlos con la estructura correcta.

Es importante seguir la convención de nombres: el nombre de la migración debe comenzar por la **acción** (`create`, `update`, `delete`, etc.) seguida del nombre del componente. Laravel añadirá automáticamente la marca de tiempo al nombre del fichero.

```bash
php artisan make:migration create_notes_table
```

Esto crea un archivo como `2026_01_10_000000_create_notes_table.php` en `database/migrations/`.

### Definir la estructura de una tabla

El archivo de migración contiene dos métodos:

- **`up()`**: define la estructura de la tabla que se va a crear.
- **`down()`**: revierte los cambios realizados por `up()`.

Las clases importadas al inicio del archivo son:

| Use | Descripción |
|---|---|
| `Illuminate\Database\Migrations\Migration` | Clase base para las migraciones. |
| `Illuminate\Database\Schema\Blueprint` | Clase para definir la estructura de la tabla. |
| `Illuminate\Support\Facades\Schema` | Clase para interactuar con el esquema de la BD. |

Ejemplo del método `up()` para la tabla `notes`:

```php
public function up(): void
{
    Schema::create('notes', function (Blueprint $table) {
        $table->id();
        $table->string('title', 255);
        $table->text('description');
        $table->boolean('done')->default(false);
        $table->timestamps();
    });
}
```

Campos especiales:

- **`id()`** crea una clave primaria autoincremental.
- **`timestamps()`** crea los campos `created_at` y `updated_at` automáticamente.

!!! info "Los campos son NOT NULL por defecto"
    En Laravel, cualquier campo definido en una migración es **obligatorio (`NOT NULL`) por defecto**. Si quieres permitir que un campo quede vacío, añade el modificador `->nullable()`. No existe un modificador `notNullable()`: simplemente, no escribas `nullable()` y el campo ya será obligatorio.

### Modificadores de campos

Los modificadores se encadenan al tipo de campo para añadir restricciones:

| Modificador | Descripción |
|---|---|
| `nullable()` | Permite que el campo sea nulo. |
| `default(value)` | Establece un valor por defecto. |
| `unique()` | Establece el campo como único. |
| `index()` | Crea un índice para el campo. |
| `foreign()` | Define una clave foránea. |
| `unsigned()` | Establece el campo como sin signo. |
| `after(column)` | Coloca el campo después de otro campo. |
| `before(column)` | Coloca el campo antes de otro campo. |
| `primary()` | Establece el campo como clave primaria. |

Los modificadores se pueden combinar:

```php
$table->string('direccion', 75)->unique();
```

Una vez definida la estructura, ejecutamos la migración:

```bash
php artisan migrate
```

---

## Revertir una migración (`down()`)

Para eliminar una tabla no debes borrarla directamente desde la BD; usa el comando de rollback para que Laravel actualice también la tabla `migrations`:

```bash
php artisan migrate:rollback
```

Esto revierte el **último lote** de migraciones ejecutado. Para recuperar la tabla, vuelve a ejecutar `php artisan migrate`.

### Referencia de comandos de migración

| Comando | Descripción |
|---|---|
| `php artisan migrate` | Ejecuta migraciones pendientes. |
| `php artisan migrate:rollback` | Deshace la última migración ejecutada. |
| `php artisan migrate:reset` | Revierte todas las migraciones. |
| `php artisan migrate:refresh` | Resetea y vuelve a ejecutar todas las migraciones. |
| `php artisan migrate:fresh` | Borra todas las tablas y ejecuta todas las migraciones. |

Parámetros útiles:

- `php artisan migrate:rollback --step=N` → revierte N migraciones.
- `php artisan migrate --batch=N` → ejecuta migraciones de un lote específico.
- `php artisan migrate --path=/ruta/a/migracion` → ejecuta una migración específica.
- `php artisan migrate --pretend` → simula la ejecución sin aplicarla.

---

## Crear una migración para actualizar una tabla

Para añadir un campo `author` a la tabla `notes` existente:

```bash
php artisan make:migration update_notes_table
```

En el método `up()`:

```php
Schema::table('notes', function (Blueprint $table) {
    $table->string('author')->after('description')->nullable();
});
```

En el método `down()`:

```php
Schema::table('notes', function (Blueprint $table) {
    $table->dropColumn('author');
});
```

Aplicamos la migración:

```bash
php artisan migrate
```

Para deshacer el cambio:

```bash
php artisan migrate:rollback
```

---

## Introducción a los Modelos en Laravel

Los **modelos** son una parte fundamental de Laravel. Cada modelo representa una tabla en la base de datos y nos permite interactuar con ella mediante **Eloquent ORM** sin necesidad de escribir consultas SQL complejas. Los archivos se encuentran en `app/Models/`.

!!! tip "Los modelos como traductores"
    Piensa en el modelo como un **traductor** entre PHP y SQL: cada fila de la tabla `notes` se convierte en un objeto `Note` con propiedades (`$note->title`, `$note->description`...). Cuando modificas esas propiedades y llamas a `save()`, Eloquent traduce esos cambios a una instrucción SQL (`INSERT` o `UPDATE`). Tú trabajas con objetos PHP; Eloquent se encarga de la traducción.

### Convenciones de nombres

- El modelo `Note` representa la tabla `notes`.
- El modelo `User` representa la tabla `users`.

El nombre del modelo es **singular** y en `CamelCase`; el nombre de la tabla es **plural** y en `snake_case`. Laravel usa estas convenciones para relacionar automáticamente modelos con tablas.

!!! info "camel-case"
    Fíjate que el nombre del modelo está en `CamelCase` y el nombre de la tabla está en `snake_case` y en plural. Laravel utiliza estas convenciones para relacionar los modelos con las tablas de la base de datos.

### Crear un modelo

```bash
php artisan make:model Note
```

Esto crea `app/Models/Note.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    //
}
```

El modelo `Note` hereda de `Model`, la clase base de Eloquent, que proporciona métodos como `save()`, `delete()`, `find()`, etc.

### Asociar el modelo a una tabla personalizada

Si hemos seguido las convenciones, la asociación es automática. Si la tabla tiene un nombre diferente, lo especificamos:

```php
class Note extends Model
{
    protected $table = 'app_notas';
}
```

---

## Operaciones básicas con Eloquent

### Crear un registro

```php
$note = new Note();
$note->title       = 'Mi primera nota';
$note->description = 'Descripción de la nota';
$note->done        = false;
$note->save();
```

### Eliminar un registro

```php
$note = Note::find(1); // Busca la nota con ID 1
if ($note) {
    $note->delete();
    return 'Nota eliminada';
}
return 'Nota no encontrada';
```

### ¿Dónde probamos este código?

Si aún no hemos visto controladores, podemos crear rutas temporales en `routes/web.php`:

```php
use App\Models\Note;

// Ruta para crear una nota
Route::get('/crear-nota', function () {
    $note = new Note();
    $note->title       = 'Mi primera nota';
    $note->description = 'Descripción de la nota';
    $note->done        = false;
    $note->save();
    return 'Nota creada';
});

// Ruta para eliminar una nota
Route::get('/eliminar-nota', function () {
    $note = Note::find(1);
    if ($note) {
        $note->delete();
        return 'Nota eliminada';
    }
    return 'Nota no encontrada';
});
```

Accede a `http://localhost:8000/crear-nota` o `http://localhost:8000/eliminar-nota` para probarlos.

!!! warning "Esto es solo para probar Eloquent"
    Estas rutas usan `Route::get()` para poder probarlas simplemente visitando la URL desde el navegador, lo cual es muy útil para comprobar que Eloquent funciona. Pero **no es así como se haría en una aplicación real**: crear o eliminar datos con una petición GET es peligroso, porque cualquier enlace, un robot de búsqueda o el botón "atrás" del navegador podrían disparar la acción sin que el usuario lo pretenda. Cuando trabajemos con controladores y formularios, estas operaciones se harán con `POST` / `DELETE` y protección CSRF, como vimos en UD3.

---

## Propiedades importantes en los Modelos

Estas propiedades personalizan el comportamiento del modelo:

| Propiedad | Descripción |
|---|---|
| `$fillable` | Lista de campos que pueden ser asignados masivamente. |
| `$guarded` | Lista de campos que **no** pueden ser asignados masivamente. |
| `$casts` | Convierte automáticamente tipos de campos (booleanos, fechas, etc.). |
| `$hidden` | Campos que no se mostrarán en respuestas JSON. |

### Asignación masiva: `$fillable` vs `$guarded`

`$fillable` y `$guarded` son dos formas **alternativas** de controlar la asignación masiva (qué campos se pueden rellenar de golpe con `fill()`, o al crear un registro a partir de un array de datos, por ejemplo los de un formulario). Son opuestas:

- `$fillable` es una **lista blanca**: solo los campos indicados se pueden rellenar masivamente.
- `$guarded` es una **lista negra**: todos los campos excepto los indicados se pueden rellenar masivamente.

!!! warning "Usa una u otra, no las dos a la vez"
    Si defines `$fillable` con contenido, Laravel ya prioriza esa lista y `$guarded` deja de tener efecto práctico en ese modelo. Elige una estrategia y sé consistente.

La opción más habitual, y la que usaremos en este curso, es `$fillable`. Ejemplo completo para el modelo `Note`:

```php
protected $fillable = ['title', 'description', 'done', 'deadline'];
protected $hidden   = ['created_at', 'updated_at'];

protected $casts = [
    'done'     => 'boolean',
    'deadline' => 'date',
];
```

La propiedad `$casts` convierte automáticamente los campos al tipo indicado. Por ejemplo, con `'deadline' => 'date'`, el campo se convierte en un objeto **Carbon** (librería PHP para fechas), lo que permite usar métodos como `format()`, `addDays()`, etc.

Como alternativa —sin combinarla con `$fillable` en el mismo modelo— `$guarded` permite el camino contrario: indicar qué campos **no** se pueden rellenar masivamente, dejando el resto abierto:

```php
protected $guarded = ['id'];
```

### Clave primaria personalizada

Si la clave primaria de la tabla no se llama `id`, puedes indicarlo:

```php
protected $primaryKey = 'note_id';
```

!!! warning "Claves primarias compuestas"
    A veces se necesita que la clave primaria de una tabla esté formada por varias columnas (por ejemplo, `note_id` + `user_id`). Eloquent **no soporta esto de forma nativa**: `$primaryKey` espera el nombre de una sola columna, y pasarle un array rompe operaciones como `find()` o `save()`. Si en algún proyecto necesitas algo así, habría que sobrescribir varios métodos del modelo o usar un paquete externo (como `compoships`). Por ahora, asumiremos que cada tabla con la que trabajamos tiene una clave primaria simple.

---

## Relación entre Modelos y Migraciones

- Cada **modelo** representa una tabla.
- Cada **migración** modifica la estructura de una tabla.
- Se pueden crear modelo y migración a la vez con el flag `-m`:

```bash
php artisan make:model NewNote -m
```

Esto crea simultáneamente `app/Models/NewNote.php` y la migración correspondiente para la tabla `new_notes`.

!!! info "Importante"
    No siempre que creamos una migración necesitamos un modelo, pero siempre que trabajamos con un modelo debemos tener su tabla correspondiente.
