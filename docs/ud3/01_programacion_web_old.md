# UD3 – Programación Web

## Formularios

A la hora de enviar un formulario, debemos tener claro cuándo usar **GET** o **POST**.

- **GET**: los parámetros se pasan en la URL (los podemos ver en la URL).
    - Máximo de 2048 caracteres y solo en ASCII.
    - Permite almacenar la dirección completa en el marcador o historial.
- **POST**: los parámetros se envían de manera oculta (aunque no encriptada).
    - Sin límite de datos, permite datos binarios.

Para los siguientes apartados, utilizaremos el siguiente ejemplo de formulario (recuerda incluir los archivos necesarios para utilizar Bootstrap en tu página):

```html
<form name="registro" method="get" action="miform.php">
  <div class="mb-3">
    <label for="nombre" class="form-label">Nombre</label>
    <input type="text" class="form-control" id="nombre"
           aria-describedby="nombreHelp" name="nombre" required>
  </div>
  <div class="mb-3">
    <label for="email" class="form-label">Email address</label>
    <input type="email" class="form-control" id="email"
           aria-describedby="emailHelp" required name="email">
    <div id="emailHelp" class="form-text">Nunca compartiremos tu email con nadie.</div>
  </div>
  <div class="mb-3">
    <label for="password" class="form-label">Password</label>
    <input type="password" class="form-control" id="password" name="password" required>
  </div>
  <button type="submit" class="btn btn-primary w-100" name="enviar">Enviar</button>
</form>
```

---

## $_GET

En nuestro primer ejemplo, vamos a ver cómo "viajan", y cómo se "reciben", los campos del formulario enviado a través del método GET.

Al pulsar en enviar, podemos ver cómo en la URL de destino aparecen los campos que hemos pasado con sus correspondientes valores:

```
/miform.php?nombre=Paco+Guirau&email=fj.guiraulopez%40edu.gva.es&password=12345&enviar=
```

Para recoger los datos, accedemos al array adecuado dependiendo del método del formulario:

```php
<?php
$par = $_GET["parametro_get"];
$par = $_POST["parametro_post"];
```

En este primer ejemplo hemos utilizado el método GET, así pues, en nuestro archivo `miform.php`, recogeremos los datos del formulario con el array `$_GET`:

```php
<?php
// El parámetro entre "" es el nombre dado al input en la página form_example.php
$nombre   = $_GET["nombre"];
$email    = $_GET["email"];
$password = $_GET["password"];
?>

<h1>Hola, <?= $nombre ?></h1>
<p>El email pasado es: <strong><?= $email ?></strong></p>
<p>Y la contraseña es: <strong><?= $password ?></strong></p>
```

---

## $_POST

Para utilizar el método POST, sólo debemos cambiar en el formulario inicial (`form_example.php`) el campo `method` y ponerlo a `post`:

```html
<form name="registro" method="post" action="miform.php">
  <div class="mb-3"> …
```

Y en la página donde recibimos los campos del formulario, debemos cambiar `$_GET` por `$_POST`:

```php
<?php
// El parámetro entre "" es el nombre dado al input en la página form_example.php
$nombre   = $_POST["nombre"];
$email    = $_POST["email"];
$password = $_POST["password"];
?>
```

Al pulsar en enviar, podemos ver cómo en la URL de destino ya **no** aparecen los campos que hemos pasado.

---

## Validación

Para la validación, es muy importante implementar una **validación doble**:

- Por un lado, en el **cliente** mediante JavaScript.
- Por otro lado, en el **servidor**, antes de procesar la lógica de negocio, conviene volver a validar los datos por seguridad.

```php
<?php
if (isset($_GET["parametro_get"])) {
    $par = $_GET["parametro_get"];
    // comprobar si $par tiene el formato adecuado, su valor, etc...
}
```

En nuestro ejemplo ya hemos utilizado la implementación en el cliente, al usar un formulario HTML5 con campos del tipo específico de datos solicitado y con el atributo `required`.

Ahora vamos a pasar a implementar la validación en el servidor.

Antes de nada, utilizamos la función de PHP **`isset()`**, para verificar si una variable ha sido definida y no es `null`. Además, también podemos asegurarnos de que no esté en blanco con **`empty()`**. En nuestro código de ejemplo, modificamos el código de esta forma:

```php
if (isset($_POST["nombre"]) && !empty($_POST["nombre"])) {
    $nombre = $_POST["nombre"];
} else {
    echo "<div class='alert alert-error' role='alert'>❌ Error: Debe rellenar el campo nombre correctamente</div>";
    header("Refresh:5; url=form_example.php");
}
```

Así, si alguien intenta enviar nuestro formulario sin introducir un nombre, mostraría el mensaje de error y, tras 5 segundos, volvería a la página que contiene el formulario.

---

## Sanitizar datos introducidos por usuarios

Cualquier dato introducido en la página por los usuarios lo tenemos que considerar **poco seguro** y, por lo tanto, nunca debemos confiar en que no puede resultar en un ataque.

En PHP podemos usar mecanismos que nos aseguren que los datos volcados en la página son inofensivos; por ejemplo, convirtiendo cualquier valor de una cadena en sus caracteres especiales de HTML. Esto lo podemos hacer con la función **`htmlspecialchars()`**.

### Ataque XSS (Cross-Site Scripting)

Veamos primero el problema de estos ataques. Un usuario malintencionado podría introducir en el campo *Nombre* algo como:

```
<script>alert('hola')</script>
```

Sin protección, ese script se ejecutaría en el navegador de cualquier persona que visite la página. Para solucionarlo, envolvemos la entrada del usuario con `htmlspecialchars()`:

```php
if (isset($_POST["nombre"]) && !empty($_POST["nombre"])) {
    $nombre = htmlspecialchars($_POST["nombre"]);
} else {
    echo "<div class='alert alert-error' role='alert'>❌ Error: Debe rellenar el campo nombre correctamente</div>";
    header("Refresh:5; url=form_example.php");
}
```

De este modo, si alguien escribe `<script>alert('hola')</script>`, la función `htmlspecialchars()` lo mostrará como:

```
&lt;script&gt;alert(&#039;hola&#039;)&lt;/script&gt;
```

Obtenemos así una respuesta libre de ejecución de código malicioso.

---

## Prevenir ataques CSRF (Cross-Site Request Forgery)

**Falsificación de petición en sitios cruzados.**

La finalidad de estos ataques es acceder desde un dominio distinto al nuestro a la página de nuestro dominio web desde la cual procesamos un determinado formulario. Para evitar este tipo de ataques podemos colocar este `if` antes de procesar el contenido:

```php
if (parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) != $_SERVER['HTTP_HOST']) {
    echo "<div class='alert alert-error' role='alert'>❌ Error: Debe rellenar el formulario en nuestra web</div>";
    header("Refresh:2; url=form_example.php");
}
```

---

## Recoger datos de selección múltiple

En el caso de que tengamos algún campo con múltiples valores enviados (por ejemplo, una lista de selección múltiple o a través de checkboxes), el campo del formulario tendrá este aspecto:

Agregamos el atributo `multiple` al `<select>` y el atributo `name` debe terminar con `[]` (como un array):

```html
<select name="mis_opciones[]" multiple>
  <option value="valor1">Opción 1</option>
  <option value="valor2">Opción 2</option>
  <option value="valor3">Opción 3</option>
</select>
```

En PHP accedemos a la variable como un array y la recorremos con un bucle `foreach` para obtener cada valor seleccionado:

```php
$opciones_seleccionadas = $_POST['mis_opciones'];

foreach ($opciones_seleccionadas as $opcion) {
    echo "Opción seleccionada: " . $opcion . "<br>";
}
```

---

## Enviar archivos al servidor desde un formulario

Para poder enviar archivos a un servidor desde un formulario, añadimos un campo `<input>` de tipo `file`. Además, debemos añadir a la etiqueta `<form>` el atributo `enctype` con el valor `multipart/form-data` para hacer saber al navegador y al servidor que en ese formulario se van a enviar datos de un archivo:

```html
<form action="procesar.php" method="post" enctype="multipart/form-data">
  <input type="file" name="archivo">
  <input type="submit" value="Enviar">
</form>
```

Una vez recibido el formulario por el servidor, el fichero se almacena en una carpeta temporal y debemos moverlo a su ubicación definitiva con la función `move_uploaded_file()`. La variable global **`$_FILES`** contiene toda la información de los archivos que se han subido con el formulario:

```php
// Ruta donde quieres guardar el archivo
$directorio_destino      = "uploads/";
$nombre_archivo_temporal = $_FILES["archivo"]["tmp_name"];
$nombre_archivo_original = $_FILES["archivo"]["name"];
$ruta_final              = $directorio_destino . $nombre_archivo_original;

// Mueve el archivo temporal a la carpeta de destino
if (move_uploaded_file($nombre_archivo_temporal, $ruta_final)) {
    echo "El archivo se ha subido correctamente.";
} else {
    echo "Error al subir el archivo.";
}
```

!!! warning "Importante"
    Debes asegurarte de generar **nombres de ficheros únicos** o tendrás problemas con la subida de archivos (un archivo nuevo podría sobreescribir uno existente con el mismo nombre).

---

## Cookies

Las cookies en PHP son pequeños archivos de texto que se guardan en el navegador del usuario para almacenar datos y recordar información entre visitas. Se crean con la función **`setcookie()`** y se leen desde PHP a través de la variable superglobal **`$_COOKIE`**. Se usan comúnmente para identificar usuarios, almacenar preferencias o recordar datos como los artículos de un carrito de compras.

### Creación de una cookie

Se usa la función `setcookie()` para crear una cookie. El primer parámetro es el nombre y el segundo es el valor. El tercer parámetro opcional es la fecha de caducidad:

```php
<?php
// Crea una cookie llamada 'nombre_usuario' con el valor 'Paco'
setcookie("nombre_usuario", "Paco");

// Crea una cookie que caducará en 30 días
$expiracion = time() + (30 * 24 * 60 * 60); // 30 días en segundos
setcookie("recuerdame", "si", $expiracion);

echo "Las cookies han sido configuradas.";
?>
```

### Cómo leer una cookie

Se accede al valor de una cookie a través del array superglobal `$_COOKIE`, utilizando el nombre de la cookie como índice:

```php
<?php
// Verifica si la cookie 'nombre_usuario' está definida
if (isset($_COOKIE["nombre_usuario"])) {
    echo "Hola, " . $_COOKIE["nombre_usuario"] . "! Bienvenido de nuevo.";
} else {
    echo "Hola, invitado.";
}
?>
```

### Eliminar una cookie

Para eliminar una cookie, se utiliza la función `setcookie()` con el mismo nombre de la cookie, pero estableciendo la fecha de caducidad en el pasado y sin valor:

```php
<?php
// Para eliminar la cookie 'nombre_usuario'
setcookie("nombre_usuario", "", time() - 3600); // time() - 3600 pone la fecha en el pasado
?>
```

---

## Variables de Sesión

Las sesiones son otro mecanismo de intercambio de información entre navegador y servidor, cuyo tiempo de vida es igual a toda la visita de un usuario a una web (en cuanto el usuario cierra sesión, desconecta o cierra el navegador, se pierde la información).

Las principales diferencias entre las sesiones y las cookies son que **las sesiones no se almacenan en ficheros en el disco del cliente**, sino que se guarda un registro de ellas en el servidor. Cada cliente accede a su propia sesión a través de un identificador único —una clave alfanumérica— que se intercambian cliente y servidor en cada petición.

### ¿Cómo funcionan?

1. **Iniciar la sesión**: Se llama a la función `session_start()` al principio de cualquier script para iniciar o continuar una sesión (normalmente en todas las páginas del sitio web).
2. **Crear una cookie**: `session_start()` envía automáticamente una cookie al navegador del usuario con un ID único (`PHPSESSID`).
3. **Crear un archivo en el servidor**: El servidor crea un archivo (por ejemplo, `sess_e7hvo8s1j8dqsljdps1fo1uni5`) con este ID para almacenar las variables de sesión.
4. **Almacenar datos**: Para guardar datos, se usa la variable superglobal `$_SESSION`, que funciona como un array asociativo.
5. **Acceder a los datos**: En cualquier otra página del mismo sitio, al volver a llamar a `session_start()`, se puede acceder a los mismos datos a través de `$_SESSION`.

### Principales operaciones

```php
session_start();                    // inicia o carga la sesión
session_id();                       // obtiene el id (valor de PHPSESSID)
$_SESSION["clave"] = valor;         // asignamos valor a la sesión
session_destroy();                  // destruye la sesión
unset($_SESSION["clave"]);          // elimina una clave
```

### Ejemplo de uso

```php
<?php
session_start();                              // arrancamos las variables de sesión
$_SESSION["ies"] = "IES Torrevigia";          // asignamos valor a la sesión ies
$instituto = $_SESSION["ies"];                // guardar valor de sesión en variable
echo "Estamos en el $instituto";
?>
```
