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
    <meta name ="keywords" content ="prueba, usabilidad, preguntas, comentarios, opinión, calificación" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css"/>
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css"/>
    <link rel="icon" href="../multimedia/imagenes/configuracion.ico" type="image/x-icon"/>
</head>
<body>
        
    

    <main>
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
      $idCampo     = 'f_' . $nombreCampo;
      $valorCampo  = $valores[$nombreCampo] ?? '';
  ?>
    <p>
      <label for="<?php echo $idCampo; ?>">
        Pregunta <?php echo $numero; ?>:
        <?php echo htmlspecialchars($textoPregunta); ?>
      </label>
      <input
        type="text"
        id="<?php echo $idCampo; ?>"
        name="<?php echo $nombreCampo; ?>"
        value="<?php echo htmlspecialchars($valorCampo); ?>"
        <?php echo $prueba_finalizada ? 'readonly' : 'required'; ?>>
    </p>
  <?php endforeach; ?>

  <?php if ($prueba_finalizada): ?>

    <p>
      <label for="f_codigo">Código de usuario:</label>
      <input type="number" id="f_codigo" name="codigo"
             value="<?php echo htmlspecialchars($valores['codigo'] ?? ''); ?>"
             required>
    </p>

    <p>
      <label for="f_profesion">Profesión:</label>
      <input type="text" id="f_profesion" name="profesion"
             value="<?php echo htmlspecialchars($valores['profesion'] ?? ''); ?>"
             required>
    </p>

    <p>
      <label for="f_edad">Edad:</label>
      <input type="number" id="f_edad" name="edad"
             value="<?php echo htmlspecialchars($valores['edad'] ?? ''); ?>"
             required>
    </p>

    <p>
      <label for="f_genero">Género:</label>
      <select id="f_genero" name="genero" required>
        <option value="">Seleccione</option>
        <option value="Masculino" <?php if (($valores['genero'] ?? '') === 'Masculino') echo 'selected'; ?>>Masculino</option>
        <option value="Femenino"  <?php if (($valores['genero'] ?? '') === 'Femenino')  echo 'selected'; ?>>Femenino</option>
        <option value="Otro"      <?php if (($valores['genero'] ?? '') === 'Otro')      echo 'selected'; ?>>Otro</option>
      </select>
    </p>

    <p>
      <label for="f_pericia">Pericia informática (1 a 10):</label>
      <input type="number" id="f_pericia" name="pericia" min="1" max="10"
             value="<?php echo htmlspecialchars($valores['pericia'] ?? ''); ?>"
             required>
    </p>

    <p>
      <label for="f_dispositivo">Dispositivo:</label>
      <select id="f_dispositivo" name="dispositivo" required>
        <option value="">Seleccione</option>
        <option value="Ordenador" <?php if (($valores['dispositivo'] ?? '') === 'Ordenador') echo 'selected'; ?>>Ordenador</option>
        <option value="Tableta"   <?php if (($valores['dispositivo'] ?? '') === 'Tableta')   echo 'selected'; ?>>Tableta</option>
        <option value="Telefono"  <?php if (($valores['dispositivo'] ?? '') === 'Telefono')  echo 'selected'; ?>>Teléfono</option>
      </select>
    </p>

    <p>
      <label for="f_comentarios">Comentarios del usuario:</label>
      <textarea id="f_comentarios" name="comentarios" rows="4" cols="40"><?php
        echo htmlspecialchars($valores['comentarios'] ?? '');
      ?></textarea>
    </p>

    <p>
      <label for="f_propuestas">Propuestas de mejora:</label>
      <textarea id="f_propuestas" name="propuestas" rows="4" cols="40"><?php
        echo htmlspecialchars($valores['propuestas'] ?? '');
      ?></textarea>
    </p>

    <p>
      <label for="f_valoracion">Valoración de la aplicación (0 a 10):</label>
      <input type="number" id="f_valoracion" name="valoracion" min="0" max="10"
             value="<?php echo htmlspecialchars($valores['valoracion'] ?? ''); ?>"
             required>
    </p>

    <p>
      <label for="f_observaciones">Comentarios del observador:</label>
      <textarea id="f_observaciones" name="observaciones" rows="4" cols="40"><?php
        echo htmlspecialchars($valores['observaciones'] ?? '');
      ?></textarea>
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
</main>

</body>
</html>
