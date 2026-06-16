# UD2 - Funciones y Recursividad en PHP

---

## 2.4.1. Funciones

La mejor forma de mantener un programa extenso es construirlo a partir de **pequeñas piezas independientes** llamadas **funciones**. Permiten dividir el código en unidades autónomas, reutilizables y más fáciles de mantener.

```php
function nombreFuncion($param1, $param2) {
    // cuerpo de la función
}
```

---

### 2.4.1.1 Función sin parámetros ni retorno

```php
function saludo() {   // función sin parámetros
    printf("<h3>Hola que tal</h3>");
}

// Llamada a la función:
saludo();
```

---

### 2.4.1.2 Función con parámetros

Los parámetros son las variables que recibe la función entre paréntesis:

```php
function suma($num1, $num2) {   // función con parámetros
    echo $num1 + $num2;
}

// Llamada pasando los valores a sumar:
suma(4, 5.5);
```

---

### 2.4.1.3 Función con `return` (retorno de valores)

Una función puede **devolver un resultado** usando `return`:

```php
function sumar($num1, $num2) {
    return ($num1 + $num2);   // devuelve la suma
}

// La variable $res recibe lo que devuelve la función:
$res = sumar(5, 32);
echo $res;   // 37
```

---

### 2.4.1.4 Parámetros con valores por defecto

Se pueden definir **valores por defecto** para los parámetros, de modo que si no se pasan al llamar a la función, toman ese valor automáticamente.

!!! warning "Los parámetros con valor por defecto siempre al final"
    Deben colocarse después de los parámetros obligatorios, y hay que respetar el orden al omitirlos.

```php
function datosAlumno($nombre, $telefono, $nota = 3, $aNacimiento = 2005) {
    // ...
}

// Pasando todos los parámetros:
datosAlumno("Paco Guirau", "654 987 321", 9, 1980);

// Omitiendo $aNacimiento → toma el valor 2005 por defecto:
datosAlumno("Paco Guirau", "654 987 321", 5);

// Omitiendo $nota y $aNacimiento → toman 3 y 2005 por defecto:
datosAlumno("Paco Guirau", "654 987 321");
```

---

### 2.4.1.5 Paso de parámetros por referencia (`&`)

Por defecto, PHP pasa los parámetros **por valor**: la función trabaja con una copia y el original no cambia. Si queremos que la función **modifique directamente** la variable original, la pasamos **por referencia** añadiendo `&` delante del parámetro:

```php
function duplicarValor(&$num) {   // & → paso por referencia
    $num *= 2;
}

$num = 5;
duplicarValor($num);
echo $num;   // 10 → la variable original ha cambiado
```

!!! info "Por valor vs por referencia"
    | Modo | Sintaxis | La variable original... |
    |---|---|---|
    | Por valor (defecto) | `function f($x)` | **No cambia** |
    | Por referencia | `function f(&$x)` | **Sí cambia** |

---

## 2.4.2. Manejo de excepciones

Una **excepción** es un error que ocurre durante la ejecución del programa. PHP permite capturarlas con `try-catch-finally` para que el programa no se detenga inesperadamente.

```php
try {
    // código que puede lanzar una excepción
} catch (TipoExcepcion $e) {
    // código que se ejecuta si ocurre la excepción
} finally {
    // código que se ejecuta SIEMPRE (con o sin excepción)
}
```

!!! info "Bloques opcionales"
    Se puede omitir `catch` o `finally`, pero **no ambos** a la vez.

### 2.4.2.1 Ejemplo: división por cero

```php
function dividir($a, $b) {
    if ($b == 0) {
        throw new Exception("No se puede dividir por cero.");
    }
    return $a / $b;
}

$resultado = 0;

try {
    // código que puede lanzar una excepción
    $resultado = dividir(10, 0);

} catch (Exception $e) {
    // capturamos la excepción y mostramos el mensaje
    echo "Ocurrió un error: " . $e->getMessage();

} finally {
    // se ejecuta siempre, tanto si hay excepción como si no
    echo $resultado;
}
```

**Salida:**
```
Ocurrió un error: No se puede dividir por cero.
0
```

---

## 2.4.3. Recursividad

Una **función recursiva** es aquella que **se llama a sí misma** durante su ejecución. Es la base de los algoritmos *Divide y Vencerás*.

```php
function funcRecursiva($listaParametros) {
    // ...
    funcRecursiva($listaParametros);  // llamada a sí misma
    // ...
}
```

!!! warning "Condición de parada obligatoria"
    Toda función recursiva **debe tener una condición de parada** (caso base) que detenga las llamadas. Sin ella, la función se llama infinitamente hasta agotar la memoria.

---

### 2.4.3.1 Ejemplo: factorial

El factorial se define como:
```
n! = n × (n-1) × (n-2) × ... × 1
0! = 1
```

```php
function factorial($num) {
    if ($num == 0)             // caso base: 0! = 1
        return 1;
    else                       // llamada recursiva: n * factorial(n-1)
        return $num * factorial($num - 1);
}

echo factorial(5);   // 120
echo factorial(3);   // 6
```

---

### 2.4.3.2 Cómo se ejecuta paso a paso

Llamada a `factorial(3)`:

```
factorial(3)
│
├─► return 3 * factorial(2)
│             │
│             ├─► return 2 * factorial(1)
│             │             │
│             │             ├─► return 1 * factorial(0)
│             │             │             │
│             │             │             └─► return 1   ← caso base
│             │             │
│             │             └─► return 1  (1 × 1)
│             │
│             └─► return 2  (2 × 1)
│
└─► return 6  (3 × 2)
```

El resultado final es **6**.

---

## Resumen de la unidad

| Concepto | PHP |
|---|---|
| Declarar función | `function nombre($params) { }` |
| Función sin retorno | No usa `return` |
| Función con retorno | `return $valor;` |
| Parámetro por defecto | `function f($x, $y = 10)` |
| Paso por referencia | `function f(&$x)` → modifica el original |
| Lanzar excepción | `throw new Exception("mensaje")` |
| Capturar excepción | `try { } catch (Exception $e) { }` |
| Siempre se ejecuta | bloque `finally` |
| Función recursiva | Se llama a sí misma; necesita caso base |
| Caso base | Condición que detiene la recursión |
