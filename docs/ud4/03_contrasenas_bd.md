# UD4.2 – Inserción y recuperación de contraseñas en la BD

## Inserción de contraseñas en la BD

Vamos a ver un ejemplo de cómo almacenar contraseñas en una base de datos MySQL usando PHP. Para ello, tenemos una BD llamada `ejemplo` con una tabla `clientes` con los siguientes campos:

| # | Nombre | Tipo | Cotejamiento |
|---|---|---|---|
| 1 | `id` | int(11) | — |
| 2 | `nombre` | varchar(50) | utf8mb4_general_ci |
| 3 | `email` | varchar(50) | utf8mb4_general_ci |
| 4 | `password` | varchar(255) | utf8mb4_general_ci |

El campo `password` tiene un tamaño de 255 caracteres por el siguiente motivo: vamos a utilizar la función de PHP:

```php
password_hash($password, PASSWORD_DEFAULT);
```

Esta función genera un **hash seguro** de la contraseña. El atributo `PASSWORD_DEFAULT` usa **bcrypt** actualmente en PHP 8.x, y puede cambiar a un algoritmo más largo en el futuro. El resultado es un string codificado en texto ASCII **siempre de 60 caracteres de longitud**. Podríamos ajustar el tamaño del campo sin problema, pero usando 255 evitaremos truncamientos si PHP cambia el algoritmo en versiones futuras.

!!! tip "Recomendación para proyectos profesionales"
    Usa siempre `VARCHAR(255)` para `password_hash` con `PASSWORD_DEFAULT`, así nunca te quedarás "corto" aunque PHP cambie el algoritmo en el futuro.

### Código de inserción

```php
<?php
try {
    // Para este ejemplo suponemos que estas variables vienen del formulario
    $nombre   = "Paco Guirau";
    $email    = "fj.guiraulopez@edu.gva.es";
    $password = "123456";

    // Encriptamos la contraseña antes de insertar
    $pass_encriptada = password_hash($password, PASSWORD_DEFAULT);

    // Preparamos y ejecutamos la consulta de inserción
    $sql  = "INSERT INTO clientes (nombre, email, password) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $email, $pass_encriptada]);

} catch (PDOException $e) {
    print 'Error: ' . htmlspecialchars($e->getMessage());
}
```

Al ejecutar este código, podemos ver en la BD cómo la contraseña se ha almacenado encriptada
con un hash de 60 caracteres (similar a `$2y$10$b3zELub3KxAbXEENUFlamOB4zXewwNmWDHv9tRjGekA...`).

---

### Comprobar email duplicado antes de insertar

Siempre debes comprobar si el email ya existe en la BD antes de intentar insertar un nuevo cliente. Además, en la consulta de comprobación usamos `SELECT id` en lugar de `SELECT *`, ya que no necesitamos todos los datos, solo saber si el registro existe:

```php
$<?php
// Usamos SELECT id en lugar de SELECT * porque solo necesitamos saber si existe
$check = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
$check->execute([$email]);

if ($check->fetch()) {
    // El email ya está en la BD -> mostrar mensaje de error
} else {
    $pass_encriptada = password_hash($password, PASSWORD_DEFAULT);

    $sql  = "INSERT INTO clientes (nombre, email, password) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $email, $pass_encriptada]);
}
```

---

## Comprobar usuario y contraseña contra la BD

Una vez visto cómo almacenar clientes en la BD, veamos cómo comprobar las credenciales de un
cliente cuando hace login.

El proceso es en dos pasos: primero comprobamos si el email existe, y solo entonces verificamos
la contraseña.

!!! warning "Importante: `session_start()` siempre al principio"
    La llamada a `session_start()` debe hacerse al inicio del script, antes de cualquier lógica,
    no dentro de un `if`. De lo contrario PHP puede lanzar errores si ya se ha enviado alguna
    salida al navegador.

```php
<?php
session_start(); // Siempre al principio del script

// Para este ejemplo suponemos que estas variables vienen del formulario de login
$email    = "fj.guiraulopez@edu.gva.es";
$password = "123456";

// Paso 1: comprobar si el email existe en la BD
$check = $pdo->prepare("SELECT id, nombre, email, password FROM clientes WHERE email = ?");
$check->execute([$email]);

if ($fila = $check->fetch(PDO::FETCH_ASSOC)) {
    // Paso 2: el email existe -> comprobamos la contraseña con password_verify()
    if (password_verify($password, $fila["password"])) {
        // Credenciales válidas -> guardamos datos en sesión
        $_SESSION["nombre"] = $fila["nombre"];
        $_SESSION["email"]  = $fila["email"];
        // ... redirigir al área privada

    } else {
        // Credenciales inválidas
        print 'Error: Email o contraseña incorrectos.';
    }

} else {
    // Credenciales inválidas (el email no existe)
    print 'Error: Email o contraseña incorrectos.';
}
```

!!! warning "Seguridad: no desvelar qué campo es incorrecto"
    Fíjate en que tanto si el email no existe como si la contraseña es incorrecta, mostramos
    **el mismo mensaje de error**. Si diéramos mensajes distintos ("el email no existe" vs
    "contraseña incorrecta"), un atacante podría averiguar qué emails están registrados en
    nuestra BD (*user enumeration*). Siempre usa un mensaje genérico.

---

### ¿Por qué `password_verify()` y no comparar directamente?

Cada vez que se llama a `password_hash()`, genera un hash diferente aunque la contraseña sea
la misma (incluye una **salt aleatoria**). Por eso no podemos comparar hashes directamente;
debemos usar `password_verify($password_introducida, $hash_guardado)`, que internamente extrae
la salt del hash almacenado y realiza la comparación correctamente.

---

### Reencriptación automática con `password_needs_rehash()`

Cuando PHP actualiza su algoritmo por defecto, las contraseñas almacenadas con el algoritmo
anterior siguen funcionando, pero sería recomendable actualizarlas. Para ello existe
`password_needs_rehash()`:

```php
<?php
if (password_verify($password, $fila["password"])) {

    // Comprobamos si el hash fue generado con el algoritmo actual
    if (password_needs_rehash($fila["password"], PASSWORD_DEFAULT)) {
        $nuevo_hash = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare("UPDATE clientes SET password = ? WHERE id = ?");
        $update->execute([$nuevo_hash, $fila["id"]]);
    }

    // ... continuar con el login normal
}
```

!!! info "¿Cuándo ocurre esto?"
    En la práctica esto es poco frecuente, pero es una buena costumbre incluirlo en proyectos
    profesionales para garantizar que las contraseñas siempre estén protegidas con el algoritmo
    más seguro disponible.