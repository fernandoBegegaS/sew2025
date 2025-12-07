<?php
// cronometro_vista.php
require 'cronometro_logica.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Datos que describen el documento -->
    <meta charset="UTF-8" />
    <title>MotoGP-Juegos</title>
    <meta name ="author" content ="Fernando Begega Suarez"/>
    <meta name ="description" content ="Pagina con diferentes juegos relacionados con motoGP" />
    <meta name ="keywords" content ="" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="../multimedia/imagenes/icon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css">
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css">
</head>
<body>
    <header>
        <h1> <a href="../index.html">MotoGP Desktop</a></h1>
    <nav>
		<a href="../index.html" title="Inicio">Inicio</a>
		<a href="../piloto.html" title="Información piloto">Piloto</a>
        <a href="../circuito.html" title="Información circuito">Circuito</a>
		<a href="../meteorologia.html" title="Información meteorología">Meteorología</a>
        <a href="clasificaciones.php" title="Información clasificaiones">Clasificaciones</a>
		<a href="../juegos.html" title="Información juegos"  class="active">Juegos</a>
        <a href="../ayuda.html" title="Ayuda">Ayuda</a>
	</nav>
    </header>
    <p><a href="../index.html">Inicio</a> >> <a href="../juegos.html">Juegos</a> >> <strong>Cronometro php</strong></p>
    <h2>Cronómetro</h2>
    <form method="post" action="">
        <button type="submit" name="accion" value="arrancar">Arrancar</button>
        <button type="submit" name="accion" value="parar">Parar</button>
        <button type="submit" name="accion" value="mostrar">Mostrar</button>
    </form>

    <?php if ($tiempoMostrado !== ""): ?>
        <p>Tiempo transcurrido:
            <?= htmlspecialchars($tiempoMostrado, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
</body>
</html>
