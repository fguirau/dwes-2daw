# 📬 Reto: El formulario roto

!!! abstract "UD3 · Semana 9 · Formularios y `$_POST`"
    El equipo de GameShop ha subido a producción **5 formularios** escritos con prisas un viernes a las 14:55.
    Ninguno funciona como debería. Tu misión: **encontrar todos los errores antes que el resto de equipos** y ganar el máximo de XP.

## 🎯 Objetivos

- Distinguir cuándo usar `GET` y cuándo `POST`, y qué superglobal recoge cada uno.
- Relacionar el atributo `name` del HTML con la clave que lee PHP.
- Detectar errores de lógica típicos al procesar y validar formularios.
- Entender que **todo lo que llega del navegador es un `string`** y que **nunca hay que fiarse del cliente**.

## 🕹️ Reglas del juego

| | |
|---|---|
| **Formato** | Por parejas |
| **Tiempo** | 35 minutos de caza + 15 minutos de puesta en común |
| **Material** | Carpeta `formularios/` (descárgala del aula virtual) y esta página |
| **Pista** | Cada formulario tiene **al menos 5 errores**. Alguno tiene más… |

1. Copia la carpeta `formularios/` dentro de `htdocs` de XAMPP (por ejemplo, `htdocs/formulario-roto/`).
2. Abre cada formulario en el navegador (`http://localhost/formulario-roto/1_contacto.php`) y **pruébalo**: rellénalo bien, rellénalo mal, déjalo vacío…
3. Lee el código. Los números de línea de esta página coinciden con los de los archivos.
4. Apunta cada error en la **hoja de registro** (al final de la página).

!!! tip "Cómo cazar errores como un profesional"
    - Activa los errores en pantalla: comprueba que en `php.ini` tienes `display_errors = On` y `error_reporting = E_ALL`.
    - Abre las **DevTools → pestaña Red (Network)** y fíjate en qué método se usa y qué datos viajan en cada envío.
    - Mira la **barra de direcciones** después de enviar: ¿aparecen los datos en la URL?
    - Usa `var_dump($_POST);` o `var_dump($_GET);` al principio del archivo para ver qué llega *de verdad* al servidor.
    - Un formulario puede "parecer" que funciona y aun así tener errores: prueba **casos límite** (vacío, 0, negativos, textos raros, exactamente 18 años…).

## 🏆 Sistema de puntos (XP)

| Acción | XP |
|---|---|
| Error localizado (línea) **y** explicado (por qué falla) | **+10** |
| …y además propones la corrección correcta | **+5** |
| 🛡️ Error *bonus* de seguridad (hay pocos y están bien escondidos) | **+15** |
| 🔧 **Formulario reparado**: entregas una versión que funciona sin errores | **+20** por formulario |
| ❌ Falso positivo (apuntas algo que no es un error) | **−5** |

!!! warning "Cuidado con los falsos positivos"
    "No me gusta cómo está escrito" **no es un error**. Un error es algo que hace que el formulario no funcione,
    funcione mal, muestre avisos de PHP o sea inseguro. Si dudas, **pruébalo** antes de apuntarlo.

---

## Formulario 1 · Contacto con soporte

> Un cliente quiere escribir al equipo de soporte. Dicen que "le dan a Enviar y no pasa nada".

```php linenums="1" title="1_contacto.php"
<?php
// 🎮 GameShop · Formulario roto nº 1 · Contacto con soporte
$enviado = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre  = trim($_POST['nombre'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if ($nombre = '' || $email == '' || $mensaje == '') {
        $error = 'Todos los campos son obligatorios.';
    } else {
        $enviado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GameShop · Contacto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5" style="max-width: 600px">
    <h1 class="mb-4">📬 Contacta con soporte</h1>

    <?php if ($enviado): ?>
        <div class="alert alert-success">
            ¡Gracias, <?= $nombre ?>! Te responderemos lo antes posible.
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="get" action="">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" id="name" name="nombre" class="form-control">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="text" id="email" class="form-control">
        </div>
        <div class="mb-3">
            <label for="mensaje" class="form-label">Mensaje</label>
            <textarea id="mensaje" name="msg" rows="4" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
</body>
</html>
```

## Formulario 2 · Registro de usuario

> Nadie consigue registrarse. Ni siquiera el jefe, que tiene 18 años recién cumplidos.

```php linenums="1" title="2_registro.php"
<?php
// 🎮 GameShop · Formulario roto nº 2 · Registro de usuario
$errores = [];
$ok      = false;

if (isset($_POST['enviar'])) {
    $usuario = trim($_POST['usuario'] ?? '');
    $email   = trim($_post['email'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $pass2   = $_POST['password2'] ?? '';
    $edad    = (int) ($_POST['edad'] ?? 0);

    if ($usuario === '') {
        $errores = 'El nombre de usuario es obligatorio.';
    }
    if ($email === '') {
        $errores = 'El email es obligatorio.';
    }
    if (strlen($pass) > 8) {
        $errores = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($pass !== $pass2) {
        $errores = 'Las contraseñas no coinciden.';
    }
    if ($edad <= 18) {
        $errores = 'Debes ser mayor de edad para registrarte.';
    }

    if (empty($errores)) {
        $ok = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GameShop · Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5" style="max-width: 600px">
    <h1 class="mb-4">🕹️ Crea tu cuenta GameShop</h1>

    <?php if ($ok): ?>
        <div class="alert alert-success">¡Cuenta creada! Ya puedes iniciar sesión.</div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errores as $e): ?>
                    <li><?= $e ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="mb-3">
            <label for="usuario" class="form-label">Usuario</label>
            <input type="text" id="usuario" name="usuario" class="form-control">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="text" id="password" name="password" class="form-control">
        </div>
        <div class="mb-3">
            <label for="password2" class="form-label">Repite la contraseña</label>
            <input type="password" id="password2" name="password2" class="form-control">
        </div>
        <div class="mb-3">
            <label for="edad" class="form-label">Edad</label>
            <input type="number" id="edad" name="edad" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Registrarme</button>
    </form>
</body>
</html>
```

## Formulario 3 · Buscador del catálogo

> Si buscas "Elden" no aparece *Elden Ring*. Y eso es solo el principio.

```php linenums="1" title="3_busqueda.php"
<?php
// 🎮 GameShop · Formulario roto nº 3 · Buscador del catálogo
$juegos = [
    ['titulo' => 'Elden Ring',           'plataforma' => 'PS5',    'genero' => 'RPG',         'precio' => 59.99],
    ['titulo' => 'Zelda: Tears of the Kingdom', 'plataforma' => 'Switch', 'genero' => 'Aventura', 'precio' => 69.99],
    ['titulo' => 'Hades II',             'plataforma' => 'PC',     'genero' => 'Roguelike',   'precio' => 29.99],
    ['titulo' => 'EA Sports FC 26',      'plataforma' => 'PS5',    'genero' => 'Deportes',    'precio' => 79.99],
    ['titulo' => 'Mario Kart World',     'plataforma' => 'Switch', 'genero' => 'Carreras',    'precio' => 79.99],
    ['titulo' => 'Baldur\'s Gate 3',     'plataforma' => 'PC',     'genero' => 'RPG',         'precio' => 49.99],
    ['titulo' => 'Forza Horizon 5',      'plataforma' => 'Xbox',   'genero' => 'Carreras',    'precio' => 39.99],
    ['titulo' => 'Hollow Knight: Silksong', 'plataforma' => 'PC',  'genero' => 'Aventura',    'precio' => 19.99],
];

$q          = trim($_GET['q']);
$plataforma = $_GET['plat'] ?? '';
$generos    = $_GET['genero'] ?? [];

$resultados = array_filter($juegos, function ($j) use ($q, $plataforma, $generos) {
    if ($q !== '' && !stripos($j['titulo'], $q)) {
        return false;
    }
    if ($plataforma !== '' && $j['plataforma'] !== $plataforma) {
        return false;
    }
    if (!empty($generos) && !in_array($j['genero'], $generos)) {
        return false;
    }
    return true;
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GameShop · Buscar juegos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5" style="max-width: 800px">
    <h1 class="mb-4">🔍 Buscar en el catálogo</h1>

    <form method="get" action="buscar.php" class="mb-4">
        <div class="mb-3">
            <input type="text" name="q" value="<?= $q ?>" class="form-control" placeholder="Título del juego...">
        </div>
        <div class="mb-3">
            <select name="plataforma" class="form-select">
                <option value="">Todas las plataformas</option>
                <option value="PS5">PS5</option>
                <option value="Switch">Switch</option>
                <option value="Xbox">Xbox</option>
                <option value="PC">PC</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="me-3"><input type="checkbox" name="genero" value="RPG"> RPG</label>
            <label class="me-3"><input type="checkbox" name="genero" value="Aventura"> Aventura</label>
            <label class="me-3"><input type="checkbox" name="genero" value="Carreras"> Carreras</label>
            <label class="me-3"><input type="checkbox" name="genero" value="Roguelike"> Roguelike</label>
            <label class="me-3"><input type="checkbox" name="genero" value="Deportes"> Deportes</label>
        </div>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>

    <p><?= count($resultados) ?> resultado(s)</p>
    <ul class="list-group">
        <?php foreach ($resultados as $j): ?>
            <li class="list-group-item d-flex justify-content-between">
                <span><?= $j['titulo'] ?> <small class="text-muted">(<?= $j['plataforma'] ?>)</small></span>
                <strong><?= number_format($j['precio'], 2, ',', '.') ?> €</strong>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
```

## Formulario 4 · Valora un juego

> En cuanto entras, te da las gracias por tu valoración... sin haber valorado nada.

```php linenums="1" title="4_valoracion.php"
<?php
// 🎮 GameShop · Formulario roto nº 4 · Valora un juego
$juegos = ['Elden Ring', 'Hades II', 'Mario Kart World', 'Baldur\'s Gate 3'];

$errores    = [];
$juego      = $_POST['juego'] ?? '';
$puntuacion = (int) ($_POST['puntuacion'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');

if ($puntuacion < 1 && $puntuacion > 5) {
    $errores[] = 'La puntuación debe estar entre 1 y 5 estrellas.';
}
if (!isset($comentario)) {
    $errores[] = 'Escribe un comentario sobre el juego.';
}

if (empty($errores)) {
    // Aquí se guardaría la valoración...
    header('Location: gracias.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GameShop · Valorar juego</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5" style="max-width: 600px">
    <h1 class="mb-4">⭐ Valora un juego</h1>

    <?php foreach ($errores as $e): ?>
        <div class="alert alert-danger"><?= $e ?></div>
    <?php endforeach; ?>

    <form method="post" action="">
        <div class="mb-3">
            <label for="juego" class="form-label">Juego</label>
            <select id="juego" name="juego" class="form-select">
                <?php foreach ($juegos as $j): ?>
                    <option value="<?= $j ?>"><?= $j ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <p class="form-label mb-1">Puntuación</p>
            <label class="me-2"><input type="radio" name="p1" value="1"> ⭐</label>
            <label class="me-2"><input type="radio" name="p2" value="2"> ⭐⭐</label>
            <label class="me-2"><input type="radio" name="p3" value="3"> ⭐⭐⭐</label>
            <label class="me-2"><input type="radio" name="p4" value="4"> ⭐⭐⭐⭐</label>
            <label class="me-2"><input type="radio" name="p5" value="5"> ⭐⭐⭐⭐⭐</label>
        </div>
        <div class="mb-3">
            <label for="comentario" class="form-label">Comentario</label>
            <textarea id="comentario" name="comentario" rows="4" class="form-control"
                      value="<?= $comentario ?>"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Enviar valoración</button>
    </form>
</body>
</html>
```

## Formulario 5 · Comprar con código promocional

> Contabilidad dice que hay pedidos con importes muy raros. Prueba a comprar 3 unidades con envío urgente y el código `GAMER10`.

```php linenums="1" title="5_pedido.php"
<?php
// 🎮 GameShop · Formulario roto nº 5 · Comprar con código promocional
$producto = ['id' => 8, 'titulo' => 'Hollow Knight: Silksong', 'precio' => 19.99];

$resumen = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $precio   = $_POST['precio'];
    $cantidad = $_POST['cantidad'] ?? 1;
    $envio    = $_POST['envio'] ?? 'normal';
    $codigo   = strtoupper(trim($_POST['codigo'] ?? ''));

    $total = $precio * $cantidad;

    // Oferta 3x2: si compras 3, una sale gratis
    if ($cantidad === 3) {
        $total -= $precio;
    }

    if ($envio === 'Urgente') {
        $total += 4.95;
    }

    // Código GAMER10: 10 % de descuento
    if ($codigo === 'GAMER10') {
        $descuento = $total * 10 / 100;
        $total = $descuento;
    }

    $resumen = [
        'cantidad' => $cantidad,
        'envio'    => $envio,
        'total'    => $total,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GameShop · Comprar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5" style="max-width: 600px">
    <h1 class="mb-2">🛒 <?= $producto['titulo'] ?></h1>
    <p class="lead"><?= number_format($producto['precio'], 2, ',', '.') ?> € · <span class="badge bg-success">3x2</span></p>

    <?php if ($resumen): ?>
        <div class="alert alert-info">
            <?= $resumen['cantidad'] ?> unidad(es) · envío <?= $resumen['envio'] ?><br>
            <strong>Total: <?= number_format($resumen['total'], 2, ',', '.') ?> €</strong>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="precio" value="<?= $producto['precio'] ?>">
        <div class="mb-3">
            <label for="cantidad" class="form-label">Cantidad</label>
            <input type="number" id="cantidad" name="cantidad" value="1" min="1" max="10" class="form-control">
        </div>
        <div class="mb-3">
            <label for="envio" class="form-label">Envío</label>
            <select id="envio" name="envio" class="form-select">
                <option value="normal">Normal (gratis)</option>
                <option value="urgente">Urgente (+4,95 €)</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="codigo" class="form-label">Código promocional</label>
            <input type="text" id="codigo" name="codigo" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Comprar</button>
    </form>
</body>
</html>
```


---

## 📝 Hoja de registro

Copia esta tabla en un documento y rellénala con tu pareja. Una fila por error.

| Formulario | Línea(s) | ¿Qué falla? | ¿Por qué? | Corrección propuesta |
|:---:|:---:|---|---|---|
| 1 | | | | |
| 1 | | | | |
| … | | | | |

!!! success "¿Has terminado antes de tiempo?"
    Elige el formulario que más te haya costado y **repáralo entero**. Si funciona a la primera en el ordenador del profe, son **+20 XP** para tu equipo.
