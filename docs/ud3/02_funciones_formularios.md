# 3.2 – Funciones para procesar formularios

## Generar IDs únicos

Para generar IDs únicos utilizamos la función de PHP **`uniqid()`**.

Esta devuelve un string de longitud 13. Utiliza el timestamp Unix en microsegundos como prefijo. El parámetro de entropía añade bytes aleatorios adicionales. Si necesitamos más entropía, podemos pasar el segundo parámetro a `true`:

```php
uniqid('', true);
```

Con este parámetro, la nueva cadena generada tendrá una longitud de **23 caracteres**.

---

## Sanitizar y validar un campo email

Como hemos visto en los apuntes de la unidad, para prevenir los ataques XSS (Cross-Site Scripting) podemos utilizar la función `htmlspecialchars()`. Esta garantiza que cualquier cadena pasada se muestre como caracteres literales en lugar de interpretarse como etiquetas o código HTML, y debemos utilizarla en todos los campos de texto de nuestro formulario.

Para los campos de tipo email, PHP dispone de filtros específicos más precisos.

### Sanitizar con `FILTER_SANITIZE_EMAIL`
La sanitización elimina cualquier carácter que no esté permitido en una dirección de correo electrónico, como espacios, comas o ciertos caracteres especiales **`(/, |, #, (, ), `**etc.):
```php
<?php
<?php
$email = "  john.doe @ejemplo.com  ";

$emailSanitizado = filter_var($email, FILTER_SANITIZE_EMAIL);

echo "Original:   " . $email;           // "  john.doe @ejemplo.com  "
echo "Sanitizado: " . $emailSanitizado; // "john.doe@ejemplo.com"
?>
```
!!! warning
    ⚠️ Importante: **`FILTER_SANITIZE_EMAIL`** no protege contra **`XSS`**. Solo elimina caracteres inválidos para un email. Para mostrar cualquier dato del usuario en HTML, sigue usando **`htmlspecialchars()`**.


### Validar con **`FILTER_VALIDATE_EMAIL`**

Después de sanitizar, normalmente validaremos el correo electrónico para asegurarnos de que cumple con los estándares de formato. La validación comprueba que la cadena resultante tiene el formato correcto de una dirección de correo. Los requisitos que impone incluyen:

- Un **nombre de usuario** con letras, números, puntos, guiones o guiones bajos.
- El símbolo **`@`** como separador.
- Un **nombre de dominio** con letras, puntos o guiones.
- Una **extensión de dominio** de al menos dos caracteres.

Flujo completo: sanitizar y luego validar. En la práctica, ambos pasos se encadenan antes de procesar el formulario:

```php
<?php
$email = $_POST['email'] ?? '';
// 1. Sanitizamos: eliminamos caracteres no permitidos en un email
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

// 2. Validamos: comprobamos que el formato es correcto
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Email válido: " . htmlspecialchars($email);
} else {
    echo "El formato del email no es válido.";
}
?>
```

`FILTER_VALIDATE_EMAIL` comprueba si la cadena dada se ajusta al formato de una dirección de correo electrónico válida. Los estándares que impone incluyen:

- Un **nombre de usuario** que puede contener letras, números, puntos, guiones y guiones bajos.
- El símbolo **`@`** como separador.
- Un **nombre de dominio** que incluye letras y puede contener puntos o guiones.
- Una **extensión de dominio** que debe tener al menos dos caracteres de longitud y contener principalmente letras.

---

## Validar contraseñas con expresiones regulares

Para asegurarnos de que una contraseña cumple unos requisitos mínimos de seguridad, podemos usar `preg_match()` con una expresión regular:

```php
$contrasena    = "Contrasena_123";
$patron_seguro = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

if (preg_match($patron_seguro, $contrasena)) {
    echo "¡La contraseña es segura!";
} else {
    echo "La contraseña no cumple con los requisitos de seguridad.";
}
```

El patrón regex `$patron_seguro` se compone de varias partes:

| Parte | Descripción |
|---|---|
| `^` | Indica el inicio de la cadena. |
| `(?=.*[a-z])` | *(Lookahead)* Asegura al menos una letra **minúscula**. |
| `(?=.*[A-Z])` | *(Lookahead)* Asegura al menos una letra **mayúscula**. |
| `(?=.*\d)` | *(Lookahead)* Asegura al menos un **dígito** (`\d` equivale a `[0-9]`). |
| `(?=.*[\W_])` | *(Lookahead)* Asegura al menos un **carácter no alfanumérico** (símbolo especial). |
| `.{8,}` | Asegura una **longitud mínima de 8 caracteres**. |
| `$` | Indica el final de la cadena. |

---

## Redirigir a una página con JavaScript cada X segundos

Para redirigir al usuario transcurridos unos segundos (alternativa cliente al `header("Refresh:...")` de PHP):

```html
<script>
  // Calcula el retraso en milisegundos (8 segundos * 1000 ms)
  var retraso = 8000;

  // Redirige a la misma página, pero sin los parámetros GET
  window.setTimeout(function() {
    window.location.href = window.location.pathname;
  }, retraso);
</script>
```
