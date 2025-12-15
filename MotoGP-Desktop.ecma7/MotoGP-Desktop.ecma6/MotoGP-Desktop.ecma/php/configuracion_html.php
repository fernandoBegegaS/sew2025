<?php
require 'Configuracion.php';
$config  = new Configuracion();
$mensaje = "";

// Procesar acciones ANTES del HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Exportar: solo CSV, sin HTML
    if (isset($_POST['exportar'])) {
        $config->exportarCSV(); // dentro hace exit();
    }

    if (isset($_POST['reiniciar'])) {
        $mensaje = $config->reiniciar(); // usa el return
    }

    // Eliminar BD
    if (isset($_POST['eliminar'])) {
        $mensaje = $config->eliminar();  // usa el return
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración Test</title>
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css">
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css">
    <link rel="icon" href="../multimedia/imagenes/configuracion.ico" type="image/x-icon">
</head>
<body>
<main>
    <h1>Configuración de la base de datos de pruebas de usabilidad</h1>

    <?php if ($mensaje !== ""): ?>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form method="post">
        <button type="submit" name="reiniciar">Reiniciar base de datos</button>
        <button type="submit" name="eliminar">Eliminar base de datos</button>
        <button type="submit" name="exportar">Exportar datos a CSV</button>
    </form>
</main>
</body>
</html>
