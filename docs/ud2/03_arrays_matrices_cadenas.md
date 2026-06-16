# UD2 - Arrays, Matrices y Manipulación de Cadenas

---

## 2.3.1. Arrays

Un **array** es una estructura que permite almacenar varios datos en una sola variable, accediendo a ellos mediante un índice. En PHP existen tres tipos:

| Tipo | Descripción |
|---|---|
| **Numérico** | El índice es un número. Empieza en `0` |
| **Asociativo** | El índice es una clave con nombre (par clave-valor) |
| **Mixto** | Combina índices numéricos y asociativos (arrays multidimensionales) |

---

### 2.3.1.1 Arrays numéricos — declaración

```php
// Forma 1: con array()
$dias = array("Lunes", "Martes", "Miércoles");

// Forma 2: indicando índices a mano
$dias[0] = "Lunes";
$dias[1] = "Martes";
$dias[2] = "Miércoles";

// Forma 3: corchetes vacíos (añade al final)
$dias[] = "Lunes";
$dias[] = "Martes";
$dias[] = "Miércoles";

// Forma 4: sintaxis corta con []
$vocales = ['a', 'e', 'i', 'o', 'u'];
```

---

### 2.3.1.2 Acceder a elementos

```php
echo $dias[1];   // Martes (el primer índice es 0)
```

---

### 2.3.1.3 Recorrer un array

**Con `for` y `count()`:**

```php
$numeros = array(10, 8, 6, 20, 18, 32, 45);

for ($i = 0; $i < count($numeros); $i++) {
    echo "Elemento $i: $numeros[$i] <br />";
}
```

**Con `foreach`** (más limpio cuando no necesitas el índice):

```php
$dias = array("Lunes", "Martes", "Miércoles");

foreach ($dias as $dia) {
    echo "$dia <br />";
}
```

---

### 2.3.1.4 Funciones principales de arrays numéricos

| Función | Descripción |
|---|---|
| `count($array)` | Devuelve el número de elementos |
| `array_push($array, $item)` | Añade `$item` al final |
| `$item = array_pop($array)` | Elimina y devuelve el último elemento |
| `in_array($item, $array)` | Comprueba si `$item` existe en el array (`true`/`false`) |
| `$item = array_shift($array)` | Elimina y devuelve el primer elemento; **reindexa** el array |
| `unset($array[n])` | Elimina el elemento del índice `n` **sin reindexar** |
| `sort($array)` | Ordena los elementos |
| `print_r($array)` | Muestra el contenido completo del array (útil para depurar) |

```php
$frutas = ["sandía", "melocotón", "manzana"];

array_push($frutas, "naranjas");         // añadimos al final
$ultFruta = array_pop($frutas);          // eliminamos el último

if (in_array("naranjas", $frutas))
    echo "<p>Quedan naranjas</p>";
else
    echo "<p>No quedan naranjas</p>";
```

!!! warning "Diferencia entre `array_shift` y `unset`"
    ```php
    // array_shift: elimina el primero y REINDEXA (los índices cambian)
    $miArray = ['manzana', 'banana', 'naranja'];
    $primero = array_shift($miArray);
    print_r($miArray); // Array ( [0] => banana [1] => naranja )

    // unset: elimina pero CONSERVA los índices (puede dejar huecos)
    $miArray = [0 => 'manzana', 1 => 'banana', 2 => 'naranja'];
    unset($miArray[0]);
    print_r($miArray); // Array ( [1] => banana [2] => naranja )
    ```

---

## 2.3.2. Arrays asociativos

Cada elemento es un par **clave → valor**. Se accede a los datos por su clave, no por posición numérica.

```php
$capitales = [
    "España"   => "Madrid",
    "Portugal" => "Lisboa",
    "Italia"   => "Roma",
    "Francia"  => "Paris"
];

// Añadir un elemento
$capitales["Alemania"] = "Berlín";

// Acceder por clave
echo "La capital de Francia es {$capitales["Francia"]} <br />";
```

!!! warning "No uses `[]` sin clave en arrays asociativos"
    `$capitales[] = "Budapest"` añade el valor con la clave `0`, mezclando tipos. Especifica siempre la clave: `$capitales["Hungría"] = "Budapest"`.

**Recorrer con `foreach` usando clave y valor:**

```php
foreach ($capitales as $pais => $capital) {
    echo "$pais : $capital <br />";
}
```

**Funciones útiles para arrays asociativos:**

| Función | Descripción |
|---|---|
| `array_keys($array)` | Devuelve todas las claves |
| `count($array)` | Número de pares clave-valor |
| `isset($array["clave"])` | Comprueba si existe la clave |
| `unset($array["clave"])` | Elimina el elemento con esa clave |
| `sort($array)` | Ordena por valores |

---

## 2.3.3. Matrices (arrays bidimensionales)

Una **matriz** es un array de arrays. Se accede a cada elemento con `$matriz[fila][columna]`. Los índices empiezan en `0`.

```
           Col 0  Col 1  Col 2
Fila 0:     25     15     12
Fila 1:     11      5     22
Fila 2:      4     18      7
```
El elemento `[2][1]` tiene el valor `18`.

---

### 2.3.3.1 Declaración

```php
// Matriz numérica
$edades = array(
    array(26, 18, 34),   // Fila 0
    array(8,  7,  15),   // Fila 1
    array(3,  13, 23)    // Fila 2
);

// Matriz con claves asociativas (más legible)
$gente = array(
    array('nombre' => 'Luis',   'edad' => 14),  // Fila 0
    array('nombre' => 'Silvia', 'edad' => 48)   // Fila 1
);
```

### 2.3.3.2 Acceder a elementos

```php
echo $edades[0][1];          // 18
echo $edades[2][0];          // 3

echo $gente[0]['nombre'];    // Luis
echo $gente[1]['edad'];      // 48
```

### 2.3.3.3 Recorrer una matriz

**Con `foreach` anidado:**

```php
foreach ($edades as $fila) {          // recorre las filas
    foreach ($fila as $valor) {       // recorre las columnas
        echo $valor . " ";
    }
    echo "<br>";
}
```

**Con `for` anidado:**

```php
for ($fila = 0; $fila < count($edades); $fila++) {
    for ($col = 0; $col < count($edades[0]); $col++) {
        echo $edades[$fila][$col] . " ";
    }
    echo "<br>";
}
```

---

## 2.3.4. Números aleatorios

PHP ofrece varias funciones para generar números aleatorios:

| Función | Descripción | Uso recomendado |
|---|---|---|
| `random_int($min, $max)` | Número entero criptográficamente seguro | Seguridad, tokens, contraseñas |
| `mt_rand($min, $max)` | Número entero rápido (Mersenne Twister) | Juegos, simulaciones |
| `rand($min, $max)` | Número entero básico | Usos generales simples |

```php
// Seguro para uso criptográfico
$numero_seguro = random_int(1, 100);
echo $numero_seguro;

// Rápido para uso general
$numero_mt   = mt_rand(1, 100);
$numero_rand = rand(1, 100);
```

!!! tip "¿Cuál usar?"
    Para **seguridad** (tokens, OTP, contraseñas): `random_int()`.
    Para **juegos o simulaciones**: `mt_rand()` o `rand()`.

---

## 2.3.5. Manipulación de cadenas (String)

Una **cadena** es una sucesión de caracteres alfanuméricos. Se declara con comillas dobles `"..."` o simples `'...'`:

```php
$saludo      = "Hola";
$otro_saludo = 'Holi';
```

La **concatenación** se hace con el operador `.`:

```php
$saludo  = "Hola";
$nombre  = "Mundo";
$mensaje = $saludo . " " . $nombre . "!";  // "Hola Mundo!"
```

---

### 2.3.5.1 Funciones principales de cadenas

| Función | Descripción | Ejemplo |
|---|---|---|
| `strlen($s)` | Longitud de la cadena | `strlen("Hola")` → `4` |
| `str_word_count($s)` | Número de palabras | `str_word_count("Hola mundo")` → `2` |
| `strtolower($s)` | Convierte a minúsculas | `strtolower("HOLA")` → `"hola"` |
| `strtoupper($s)` | Convierte a mayúsculas | `strtoupper("hola")` → `"HOLA"` |
| `strrev($s)` | Invierte la cadena | `strrev("Hola")` → `"aloH"` |
| `str_replace($buscar, $nuevo, $s)` | Reemplaza subcadena | ver ejemplo |
| `substr($s, $inicio, $longitud)` | Extrae una subcadena | `substr("Hola", 1, 2)` → `"ol"` |
| `strpos($s, $buscar)` | Posición de la primera aparición | `strpos("Hola", "l")` → `2` |
| `trim($s)` | Elimina espacios al inicio y final | `trim("  hola  ")` → `"hola"` |

```php
$texto = "Este es un ejemplo de cadena.";
$numeroPalabras = str_word_count($texto);   // 6

$frase     = "El gato que está triste y azul.";
$nuevaFrase = str_replace("azul", "blanco", $frase);
// "El gato que está triste y blanco."

$s1 = "Una cadena de texto";
echo strtoupper($s1);   // UNA CADENA DE TEXTO

$s2 = "Otra cadena de texto";
echo strrev($s2);       // otxet ed anedac artO
```

!!! info "Más funciones de string"
    La documentación completa está en [es.php.net/manual/es/ref.strings.php](https://es.php.net/manual/es/ref.strings.php).

---

## Resumen de la unidad

| Concepto | PHP |
|---|---|
| Declarar array | `$a = array(1, 2, 3)` / `$a = [1, 2, 3]` |
| Acceder a elemento | `$a[0]` |
| Tamaño del array | `count($a)` |
| Añadir al final | `array_push($a, $item)` / `$a[] = $item` |
| Eliminar último | `array_pop($a)` |
| ¿Existe el elemento? | `in_array($item, $a)` |
| Recorrer | `foreach ($a as $elemento)` |
| Array asociativo | `$a = ["clave" => "valor"]` |
| Recorrer asociativo | `foreach ($a as $clave => $valor)` |
| Matriz | `$m[fila][col]` |
| Número aleatorio seguro | `random_int($min, $max)` |
| Longitud cadena | `strlen($s)` |
| Reemplazar en cadena | `str_replace($buscar, $nuevo, $s)` |
| Mayúsculas / minúsculas | `strtoupper($s)` / `strtolower($s)` |
