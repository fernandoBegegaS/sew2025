<?php
require 'Configuracion.php';
$config  = new Configuracion();
$mensaje = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if (isset($_POST['exportar'])) {
        $config->exportarCSV(); 
    }

    if (isset($_POST['reiniciar'])) {
        $mensaje = $config->reiniciar(); 
    }

  
    if (isset($_POST['eliminar'])) {
        $mensaje = $config->eliminar(); 
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración Test</title>
    <meta name ="author" content ="Fernando Begega Suarez"/>
    <meta name ="description" content ="Página para la configuracion de la base de datos" />
    <meta name ="keywords" content ="eliminar, reiniciar, exportar, bd, administrar" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css"/>
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css"/>
    <link rel="icon" href="../multimedia/imagenes/configuracion.ico" type="image/x-icon"/>
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
