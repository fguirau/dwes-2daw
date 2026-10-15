<?php
$tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

// 1. Validar tipo MIME real
$tipo_real = mime_content_type($_FILES['archivo']['tmp_name']);
if (!in_array($tipo_real, $tipos_permitidos)) {
    echo "<div class='alert alert-danger'>❌ Tipo de archivo no permitido.</div>";
    exit;
}

// 2. Generar nombre único
$extension  = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
$nombre_unico = bin2hex(random_bytes(8)) . '.' . $extension;

// 3. Mover a su ubicación definitiva
$ruta_final = "uploads/" . $nombre_unico;
if (move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta_final)) {
    echo "✅ Archivo subido correctamente.";
} else {
    echo "❌ Error al subir el archivo.";
}
?>