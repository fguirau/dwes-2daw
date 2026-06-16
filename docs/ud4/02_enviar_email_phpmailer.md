# UD4.1 – Enviar email con PHP utilizando PHPMailer

Para enviar emails con PHP hacemos uso de la librería **PHPMailer** (puedes descargarla de Aules y copiar la carpeta en la raíz de tu proyecto).

A continuación, creamos un nuevo archivo `.php` en la raíz del proyecto con el siguiente contenido:

```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

// Creo una instancia; con `true` activamos excepciones
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host      = 'smtp.gmail.com';
    $mail->SMTPAuth  = true;
    $mail->Username  = 'tu_email_de_gmail@gmail.com';

    // Esta contraseña se crea desde tu cuenta de Google, solo si tienes
    // activado el inicio de sesión en 2 pasos:
    // https://myaccount.google.com/apppasswords
    $mail->Password  = 'esta_es_una_pass_de_aplicacion_NO_TU_CONTRA';

    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('tu_email@gmail.com', 'Paco Profe FP');
    $mail->addAddress('destinatario@direccion_email');

    $mail->isHTML(true);
    $mail->Subject = 'Correo HTML con PHPMailer';
    $mail->Body    = '<h1>Hola</h1><p>Correo enviado desde PHP con PHPMailer</p>';

    $mail->send();
    echo 'Correo enviado correctamente';

} catch (Exception $e) {
    echo 'Error al enviar el correo: ' . $mail->ErrorInfo;
}
?>
```

!!! warning "Contraseña de aplicación de Google"
    El campo `Password` **no** es tu contraseña habitual de Gmail. Debes generar una **contraseña de aplicación** desde tu cuenta de Google, opción disponible solo si tienes activada la verificación en dos pasos. Puedes crearla en: [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
