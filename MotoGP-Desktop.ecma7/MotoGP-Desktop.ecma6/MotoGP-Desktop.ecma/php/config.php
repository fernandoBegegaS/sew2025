<?php
require 'Configuracion.php';
$config = new Configuracion();
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
<?php
if (isset($_POST['reiniciar'])) {
    $config->reiniciar();
}
if (isset($_POST['eliminar'])) {
    $config->eliminar();
}
if (isset($_POST['exportar'])) {
    $config->exportarCSV();
}
?>
<form method="post">
    <button type="submit" name="reiniciar">Reiniciar base de datos</button><br><br>
    <button type="submit" name="eliminar">Eliminar base de datos</button><br><br>
    <button type="submit" name="exportar">Exportar datos a CSV</button>
</form>
</main>
</body>
</html>
