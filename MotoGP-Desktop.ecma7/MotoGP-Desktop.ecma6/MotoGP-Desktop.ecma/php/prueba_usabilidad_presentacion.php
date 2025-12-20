<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'PruebaUsabilidad.php';

$prueba = new PruebaUsabilidad();
$prueba->procesarPeticion();

$preguntas         = $prueba->getPreguntas();
$valores           = $prueba->getValoresFormulario();
$prueba_iniciada   = $prueba->isPruebaIniciada();
$prueba_finalizada = $prueba->isPruebaFinalizada();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de usabilidad</title>
    <meta name ="author" content ="Fernando Begega Suarez"/>
    <meta name ="description" content ="Página con las preguntas de la prueba de usabilidad" />
    <meta name ="keywords" content ="" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css">
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css">
    <link rel="icon" href="../multimedia/imagenes/configuracion.ico" type="image/x-icon">
</head>
<body>
<h1>Prueba de usabilidad de MotoGP Desktop</h1>

<?php if (!$prueba_iniciada): ?>

    <form method="post">
        <p><button type="submit" name="iniciar">Iniciar prueba</button></p>
    </form>

<?php else: ?>

    <form method="post">

        <?php foreach ($preguntas as $indice => $textoPregunta):
            $numero      = $indice + 1;
            $nombreCampo = 'pregunta' . $numero;
        ?>
            <p>
                <label>
                    Pregunta <?php echo $numero; ?>:
                    <?php echo htmlspecialchars($textoPregunta); ?><br>
                    <input
                        type="text"
                        name="<?php echo $nombreCampo; ?>"
                        value="<?php echo htmlspecialchars($valores[$nombreCampo]); ?>"
                        <?php echo $prueba_finalizada ? 'readonly' : 'required'; ?>>
                </label>
            </p>
        <?php endforeach; ?>

        <?php if ($prueba_finalizada): ?>


            <p>
                <label>Código de usuario:
                    <input type="number" name="codigo"
                           value="<?php echo htmlspecialchars($valores['codigo']); ?>"
                           required>
                </label>
            </p>

            <p>
                <label>Profesión:
                    <input type="text" name="profesion"
                           value="<?php echo htmlspecialchars($valores['profesion']); ?>"
                           required>
                </label>
            </p>

            <p>
                <label>Edad:
                    <input type="number" name="edad"
                           value="<?php echo htmlspecialchars($valores['edad']); ?>"
                           required>
                </label>
            </p>

            <p>
                <label>Género:
                    <select name="genero" required>
                        <option value="">Seleccione</option>
                        <option value="Masculino" <?php if ($valores['genero'] === 'Masculino') echo 'selected'; ?>>Masculino</option>
                        <option value="Femenino" <?php if ($valores['genero'] === 'Femenino') echo 'selected'; ?>>Femenino</option>
                        <option value="Otro"      <?php if ($valores['genero'] === 'Otro')      echo 'selected'; ?>>Otro</option>
                    </select>
                </label>
            </p>

            <p>
                <label>Pericia informática (1 a 10):
                    <input type="number" name="pericia" min="1" max="10"
                           value="<?php echo htmlspecialchars($valores['pericia']); ?>"
                           required>
                </label>
            </p>

            <p>
                <label>Dispositivo:
                    <select name="dispositivo" required>
                        <option value="">Seleccione</option>
                        <option value="Ordenador" <?php if ($valores['dispositivo'] === 'Ordenador') echo 'selected'; ?>>Ordenador</option>
                        <option value="Tableta"   <?php if ($valores['dispositivo'] === 'Tableta')   echo 'selected'; ?>>Tableta</option>
                        <option value="Telefono"  <?php if ($valores['dispositivo'] === 'Telefono')  echo 'selected'; ?>>Teléfono</option>
                    </select>
                </label>
            </p>


            <p>
                <label>Comentarios del usuario:<br>
                    <textarea name="comentarios" rows="4" cols="40"></textarea>
                </label>
            </p>

            <p>
                <label>Propuestas de mejora:<br>
                    <textarea name="propuestas" rows="4" cols="40"></textarea>
                </label>
            </p>

            <p>
                <label>Valoración de la aplicación (0 a 10):
                    <input type="number" name="valoracion" min="0" max="10" required>
                </label>
            </p>

            <p>
                <label>Comentarios del observador:<br>
                    <textarea name="observaciones" rows="4" cols="40"></textarea>
                </label>
            </p>

            <p>
                <button type="submit" name="enviar">Enviar resultados</button>
            </p>

        <?php else: ?>

            <p>
                <button type="submit" name="terminar">Finalizar prueba</button>
            </p>

        <?php endif; ?>

    </form>

<?php endif; ?>

</body>
</html>
