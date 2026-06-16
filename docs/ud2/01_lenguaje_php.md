# UD2 - El Lenguaje PHP

> Sintaxis básica, variables, tipos de datos, operadores y comentarios.

---

## 2.1.1 ¿Qué es PHP?

PHP es un lenguaje de programación de propósito general especialmente orientado al desarrollo web. Sus características principales son:

- El código se **ejecuta en el servidor** (en Apache mediante `mod_php`)
- El cliente recibe únicamente el **resultado generado** tras interpretar el código, nunca el código fuente PHP
- Los archivos deben tener extensión **`.php`**

---

## 2.1.2. Estructura de un archivo PHP — Código embebido

PHP interpreta el texto del archivo `.php` hasta que encuentra el delimitador `<?php`, que indica el inicio de código PHP. A esto se le llama **código embebido**: PHP convive con HTML en el mismo archivo.

```php
<!DOCTYPE html>
<html>
<head>
    <title>Prueba PHP</title>
</head>
<body>
    <?php echo '<p>Hola mundo</p>'; ?>
</body>
</html>
```

!!! info "Reglas básicas de sintaxis"
    - Los bloques de código PHP se escriben entre `<?php` y `?>`
    - Cada sentencia termina con `;`

---

## 2.1.3. Nuestro primer programa

```php
<?php
    echo "Bienvenid@ a la programación PHP";
?>
```

**Salida:**
```
Bienvenid@ a la programación PHP
```

!!! warning "Recuerda guardar con extensión `.php`"
    Un archivo `.html` no ejecuta PHP. El servidor (Apache, Nginx...) solo interpreta PHP en archivos con extensión `.php`.

---

## 2.1.4. Mostrar información por pantalla

### 2.1.4.1 `echo`, `print` y `printf`

Las tres sentencias siguientes son **equivalentes**: todas muestran el mismo mensaje:

```php
echo "Bienvenid@ a la programación PHP";
print "Bienvenid@ a la programación PHP";
printf("Bienvenid@ a la programación PHP");
```

| Sentencia | Comportamiento |
|---|---|
| `echo` | Sentencia de salida estándar. La más usada en PHP |
| `print` | Muestra en la misma línea; usa `<br>` para saltar de línea |
| `printf` | Salida con formato (como el `printf` de C) |

### 2.1.4.2 Atajo `<?= ?>`

Cuando solo necesitas hacer un `echo`, puedes usar la forma abreviada `<?= ?>`:

```php
// Estas dos líneas son equivalentes:
<?php echo "Bienvenid@ a la programación PHP"; ?>
<?= "Bienvenid@ a la programación PHP"; ?>
```

### 2.1.4.3 Concatenar valores con `.`

En PHP el operador de concatenación es el punto `.`, no el `+`:

```php
$resultado = 4;
echo "El resultado es: " . $resultado;   // El resultado es: 4

// También puedes interpolar variables dentro de comillas dobles:
echo "El resultado es $resultado";        // El resultado es: 4
```

!!! info "Comillas simples vs dobles"
    - Comillas **dobles** `"..."` → las variables dentro se sustituyen por su valor
    - Comillas **simples** `'...'` → el texto se muestra literalmente, sin sustituir variables

### 2.1.4.4 `printf` con formato

`printf` funciona igual que en C: el primer parámetro es la cadena con especificadores de formato, y los siguientes son los valores:

| Especificador | Tipo |
|---|---|
| `%d` | Entero (`int`, `long`) |
| `%f` | Real (`float`, `double`) |
| `%s` | Cadena de texto |

```php
$nombre = "Pedro";
$edad   = 30;
printf("Mi nombre es %s y tengo %d años.", $nombre, $edad);
// Mi nombre es Pedro y tengo 30 años.

$numero_decimal = 123.456;
printf("El valor es: %f\n", $numero_decimal);
// El valor es: 123.456000

$precio = 19.99;
printf("El precio es: %.2f€\n", $precio);  // 2 decimales
// El precio es: 19.99€
```

---

## 2.1.5. Entrada de datos

En PHP, la entrada de datos del usuario generalmente se recibe a través de **formularios HTML**, procesados con las superglobales `$_GET`, `$_POST` o `$_REQUEST`. Lo veremos en profundidad en la siguiente unidad.

Para leer datos desde la **consola** (cuando PHP se ejecuta en el servidor), se usa `readline()`:

```php
<?php
    // En un script de consola
    $entrada = readline('Por favor, ingresa tu nombre: ');
    echo "Tu nombre es: " . $entrada . "\n";
?>
```

---

## 2.1.6. Comentarios

Los comentarios son ignorados por el intérprete y sirven para documentar el código.

```php
<?php
    // Este es un comentario de una sola línea
    # Este también es un comentario de una única línea

    /*
        Este es un comentario
        que ocupa varias líneas.
        Todo lo que hay entre /* y */ se ignora.
    */
?>
```

| Sintaxis | Tipo |
|---|---|
| `// texto` | Comentario de una línea |
| `# texto` | Comentario de una línea (alternativa) |
| `/* texto */` | Comentario multilínea |

---

## 2.1.7. Variables

### 2.1.7.1 Declaración

En PHP las variables **no se declaran** previamente como en Java. Se crean la primera vez que se les asigna un valor, y su tipo depende del valor asignado:

```php
$nombre_variable = valor;
```

- Siempre van precedidas del símbolo **`$`**
- El nombre debe comenzar por **letra** o **`_`**
- Puede contener letras, números y guiones bajos

```php
$edad    = 19;          // entero
$pi      = 3.14;        // float
$nombre  = "Paco";      // string
```

### 2.1.7.2 Tipos de datos

| Tipo | Descripción | Ejemplo |
|---|---|---|
| `boolean` | Verdadero o falso | `$esInfantil = TRUE;` |
| `integer` | Números enteros (decimal, octal con `0`, hex con `0x`) | `$edad = 12;` |
| `float` | Números con decimales | `$nota = 6.8;` |
| `string` | Cadenas de texto (comillas simples o dobles) | `$nombre = "Paco Guirau";` |

```php
<?php
    $nombre     = "Paco Guirau";
    $nota       = 6.8;
    $edad       = 12;
    $esInfantil = TRUE;

    echo "El alumno $nombre tiene una nota de $nota";
?>
```

### 2.1.7.3 Conversión entre tipos

PHP realiza conversiones automáticas en las versiones modernas, pero también hay funciones explícitas:

| Función | Conversión |
|---|---|
| `intval($var)` | Convierte a entero |
| `doubleval($var)` | Convierte a número real |
| `strval($var)` | Convierte a cadena de texto |

```php
<?php
    $textoEdad = "18";           // variable de tipo texto
    $edad = intval($textoEdad);  // variable de tipo numérico
?>
```

### 2.1.7.4 Estado de las variables

| Función | Descripción |
|---|---|
| `unset($var)` | Elimina la variable (como si nunca se hubiese creado) |
| `isset($var)` | Comprueba si la variable existe |
| `empty($var)` | Comprueba si una variable está vacía |

```php
<?php
    $var1 = "Ejemplo de uso";
    unset($var1);        // la variable deja de existir
    $var1 = "";
    echo empty($var1);   // muestra 1 (true), porque está vacía
?>
```

---

## 2.1.8. Constantes

Las constantes son variables cuyo **valor no cambia** durante la ejecución. Se declaran en **MAYÚSCULAS** por convención:

```php
define("NOMBRE_CONSTANTE", valor);   // forma clásica
const NOMBRE_CONSTANTE = valor;      // forma moderna
```

| Forma | Cuándo usar |
|---|---|
| `const` | Cuando conoces el valor desde el inicio (también válido en clases) |
| `define()` | Cuando necesitas declarar la constante de forma condicional o en tiempo de ejecución |

```php
define("PI", 3.14);
const IVA = 0.21;

echo PI, " ", IVA;   // Cuidado: las constantes NO llevan $
// 3.14 0.21
```

!!! warning "Las constantes no llevan `$`"
    A diferencia de las variables, las constantes se referencian sin el símbolo `$`.

---

## 2.1.9. Operadores aritméticos

| Operador | Operación | Ejemplo |
|---|---|---|
| `+` | Suma | `$var1 + $var2` |
| `-` | Resta | `$var1 - $var2` |
| `*` | Multiplicación | `$var1 * $var2` |
| `/` | División | `$var1 / $var2` |
| `%` | Resto | `$var1 % $var2` |
| `**` | Potencia | `$var1 ** $var2` |

```php
$c = $a + $b;    // suma
$b = $c - 2;     // resta
$a = $a * 3;     // multiplicación
$c = $c / 2;     // división
$b = $b % 2;     // resto
$c = $c ** $a;   // potencia
```

---

## 2.1.10. Operadores de incremento y decremento

| Operador | Nombre | Efecto |
|---|---|---|
| `$var++` | Postincremento | Usa el valor y **después** incrementa |
| `++$var` | Preincremento | **Primero** incrementa, luego usa el valor |
| `$var--` | Postdecremento | Usa el valor y **después** decrementa |
| `--$var` | Predecremento | **Primero** decrementa, luego usa el valor |

```php
// Postincremento: primero asigna, luego incrementa
$a = 2; $b = 3;
$a = $b++;
printf("El valor de a es %d", $a);  // a=3
printf("El valor de b es %d", $b);  // b=4

// Preincremento: primero incrementa, luego asigna
$a = 2; $b = 3;
$a = ++$b;
printf("El valor de a es %d", $a);  // a=4
printf("El valor de b es %d", $b);  // b=4
```

```php
// Postdecremento: primero asigna, luego decrementa
$a = 2; $b = 3;
$a = $b--;
printf("El valor de a es %d", $a);  // a=3
printf("El valor de b es %d", $b);  // b=2

// Predecremento: primero decrementa, luego asigna
$a = 2; $b = 3;
$a = --$b;
printf("El valor de a es %d", $a);  // a=2
printf("El valor de b es %d", $b);  // b=2
```

---

## 2.1.11. Operaciones abreviadas

Cuando una variable aparece a ambos lados de una operación, se puede usar **notación abreviada**:

```php
$a = $a * 3;   // forma completa
$a *= 3;       // forma abreviada — equivalente
```

| Abreviada | Equivale a |
|---|---|
| `$a += $b` | `$a = $a + $b` |
| `$a -= $b` | `$a = $a - $b` |
| `$a *= $b` | `$a = $a * $b` |
| `$a /= $b` | `$a = $a / $b` |
| `$a %= $b` | `$a = $a % $b` |
| `$a .= $b` | `$a = $a . $b` (concatenación) |

```php
$a = 2; $b = 3; $c = 4; $d = 5;

$b += $a;   // $b = $b + $a → b=5
$a *= 2;    // $a = $a * 2 → a=4
$c /= 2;    // $c = $c / 2 → c=2
$d %= 3;    // $d = $d % 3 → d=2
```

---

## Resumen de la unidad

| Concepto | PHP |
|---|---|
| Bloque PHP | `<?php ... ?>` |
| Mostrar texto | `echo`, `print`, `printf` |
| Atajo echo | `<?= "texto" ?>` |
| Concatenar | operador `.` |
| Interpolar variable | `"Hola $nombre"` (comillas dobles) |
| Declarar variable | `$nombre = valor;` |
| Declarar constante | `define("PI", 3.14)` / `const PI = 3.14` |
| Comentario línea | `//` o `#` |
| Comentario bloque | `/* ... */` |
| Potencia | `**` |
| Incremento post | `$a++` |
| Incremento pre | `++$a` |
| Op. abreviada | `$a *= 2` |
