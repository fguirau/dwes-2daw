# UD2 - Estructuras de Control en PHP

---

## 2.2.1. Estructuras de selección

### 2.2.1.1 Estructura `if`

Evalúa una condición. Si es verdadera, ejecuta las sentencias del bloque.

```php
if (condicion) {
    // sentencias si condición es verdadera
}
```

!!! info "Llaves opcionales"
    Si solo hay **una sentencia**, las llaves `{}` son opcionales. Aun así, es buena práctica usarlas siempre.

```php
$edad = 19;

// Con llaves
if ($edad > 17) {
    printf("Eres mayor de edad");
}

// Sin llaves (equivalente)
if ($edad > 17)
    printf("Eres mayor de edad");
```

---

### 2.2.1.2 Estructura `if-else`

Si la condición es verdadera ejecuta el bloque `if`; si es falsa, ejecuta el bloque `else`.

```php
if (condicion) {
    // sentencias si verdadero
} else {
    // sentencias si falso
}
```

```php
$edad = 19;

if ($edad > 17) {
    printf("Eres mayor de edad");
} else {
    printf("Todavía no eres mayor de edad");
}
```

---

### 2.2.1.3 Operador condicional `?:`

Forma compacta de un `if-else` para **asignar un valor** según una condición:

```php
$x = (condicion) ? valor1 : valor2;
```

```php
$edad = 19;
$mensaje = ($edad > 17) ? "Eres mayor de Edad" : "Todavía no eres mayor de edad";
echo $mensaje;
```

---

### 2.2.1.4 Estructura `if - else if - else`

Permite encadenar múltiples condiciones. Se evalúan en orden y se ejecuta el primer bloque cuya condición sea verdadera.

```php
if (condicion1) {
    // sentencias si condición1 es verdadera
} else if (condicion2) {
    // sentencias si condición2 es verdadera
} else if (condicion3) {
    // sentencias si condición3 es verdadera
} else {
    // sentencias si ninguna condición es verdadera
}
```

```php
$x = 10;

if ($x == 10) {
    printf("X es igual a 10");
} else if ($x == 20) {
    printf("X es igual a 20");
} else if ($x == 30) {
    printf("X es igual a 30");
} else {
    printf("X no es igual ni a 10, ni a 20, ni a 30.");
}
```

---

### 2.2.1.5 Estructura `switch`

Se usa cuando una variable puede tomar **varios valores concretos** y para cada uno hay que ejecutar sentencias distintas.

```php
switch (variable) {
    case 1:
        // sentencias
        break;
    case 2:
        // sentencias
        break;
    default:
        // sentencias si no coincide ningún case
}
```

!!! warning "No olvides el `break`"
    Sin `break`, la ejecución **continúa** en el siguiente `case` aunque no coincida (*fall-through*).

```php
$x = 10;

switch ($x) {
    case 10:
        echo "X es igual a 10.";
        break;
    case 20:
        echo "X es igual a 20.";
        break;
    case 30:
        echo "X es igual a 30.";
        break;
    default:
        echo "X no es igual ni a 10, ni a 20, ni a 30.";
}
```

Este ejemplo es **equivalente** al `if-else if-else` del apartado anterior.

---

### 2.2.1.6 Estructura `match` (PHP 8+)

Alternativa moderna y más segura al `switch`. Realiza **comparaciones estrictas** (tipo y valor) y **devuelve un valor directamente**, sin necesidad de `break`.

```php
$x = 10;
$res = match ($x) {
    10      => "X es igual a 10",
    20      => "X es igual a 20",
    30      => "X es igual a 30",
    default => "X no es igual ni a 10, ni a 20, ni a 30."
};
echo $res;
```

```php
$estado = 'pendiente';
$mensaje = match ($estado) {
    'pendiente'   => "La tarea está en progreso.",
    'completado'  => "La tarea se completó.",
    default       => "Estado desconocido.",
};
echo $mensaje;   // La tarea está en progreso
```

!!! info "Diferencias entre `switch` y `match`"
    | | `switch` | `match` |
    |---|---|---|
    | Comparación | Laxa (`==`) | Estricta (`===`) |
    | Necesita `break` | ✅ Sí | ❌ No |
    | Devuelve valor | ❌ No | ✅ Sí |
    | Disponible desde | PHP 4 | PHP 8.0 |

---

## 2.2.2. Estructuras de repetición

### 2.2.2.1 Bucles `while`

Ejecuta un bloque de código **mientras** la condición sea verdadera. La condición se evalúa **antes** de cada iteración, por lo que si es falsa desde el principio, el bloque no se ejecuta ni una vez.

```php
while (condicion) {
    // sentencias que se repiten mientras la condición sea verdadera
}
```

```php
$num = 1;
while ($num <= 10) {
    print $num;
    $num++;
}
// Muestra: 1 2 3 4 5 6 7 8 9 10
```

---

### 2.2.2.2 Bucles `do-while`

Igual que `while`, pero la condición se evalúa **al final**. Garantiza que el bloque se ejecute **al menos una vez**.

```php
do {
    // sentencias que se ejecutan al menos una vez
} while (condicion);
```

```php
$num = 1;
do {
    print $num;
    $num++;
} while ($num <= 10);
// Muestra: 1 2 3 4 5 6 7 8 9 10
```

!!! info "¿Cuándo usar `do-while`?"
    Cuando necesitas que el bloque se ejecute **al menos una vez**. Caso típico: validar la entrada del usuario — preguntas y luego compruebas si la respuesta es válida.

---

### 2.2.2.3 Bucles `for`

El bucle `for` concentra en una sola línea los tres elementos de control: **inicialización**, **condición** e **incremento**.

```php
for (inicializacion; condicion; iteracion) {
    // sentencias que se repiten mientras la condición sea verdadera
}
```

Orden de ejecución:
1. Se ejecuta la **inicialización** (solo una vez)
2. Se evalúa la **condición** → si es falsa, termina el bucle
3. Se ejecutan las **sentencias**
4. Se ejecuta la **iteración** (incremento/decremento)
5. Se vuelve al paso 2

```php
for ($num = 1; $num <= 10; $num++) {
    print $num;
}
// Muestra: 1 2 3 4 5 6 7 8 9 10
```
### 2.2.2.4 Bucles `foreach`

El bucle `foreach` al igual que el `for` concentra en una sola línea los tres elementos de control: **inicialización**, **condición** e **incremento**.
La función `range(1, 10)` genera de forma automática un array con los números del 1 al 10 para que el bucle pueda recorrerlos, y los asigna en cada iteración a la variable `num`.
```php
foreach (range(1, 10) as $num) {
    print $num;
}
// Muestra: 1 2 3 4 5 6 7 8 9 10
```
!!! tip "Comparativa de bucles"
    Los tres ejemplos anteriores producen el mismo resultado. La elección depende del contexto:

    | Bucle | Úsalo cuando... |
    |---|---|
    | `for`, `foreach` | Sabes de antemano cuántas veces va a iterar |
    | `while` | No sabes cuántas veces va a iterar |
    | `do-while` | Necesitas que se ejecute al menos una vez |

---

## 2.2.3. Sentencias de control de bucles

### 2.2.3.1 Sentencia `break`

Fuerza la **salida inmediata** del bucle, independientemente de la condición. Válido en `while`, `do-while`, `for` y `switch`.

```php
for ($num = 1; $num <= 10; $num++) {
    if ($num == 5)    // Si num es 5...
        break;        // ...salimos del bucle automáticamente
    print $num;
}
// Muestra: 1 2 3 4
```

---

### 2.2.3.2 Sentencia `continue`

Omite el resto de las sentencias de la iteración actual y **pasa a la siguiente iteración**.

```php
for ($num = 1; $num <= 10; $num++) {
    if ($num == 5)
        continue;   // Omitimos el código siguiente y pasamos a la siguiente iteración
    print $num;
}
// Muestra: 1 2 3 4 6 7 8 9 10
```

!!! info "Diferencia entre `break` y `continue`"
    - `break` → **termina** el bucle completamente
    - `continue` → **salta** la iteración actual y continúa con la siguiente

---

## Resumen de la unidad

| Estructura | Uso |
|---|---|
| `if` | Ejecuta si la condición es verdadera |
| `if-else` | Dos caminos: verdadero o falso |
| `?:` | Asignación condicional compacta |
| `if-else if-else` | Múltiples condiciones encadenadas |
| `switch` | Múltiples valores concretos de una variable |
| `match` | Como `switch` pero estricto y devuelve valor (PHP 8+) |
| `while` | Repite mientras se cumpla la condición (0 o más veces) |
| `do-while` | Repite mientras se cumpla la condición (1 o más veces) |
| `for` | Repetición con contador conocido |
| `break` | Sale del bucle o del `switch` inmediatamente |
| `continue` | Salta a la siguiente iteración |
