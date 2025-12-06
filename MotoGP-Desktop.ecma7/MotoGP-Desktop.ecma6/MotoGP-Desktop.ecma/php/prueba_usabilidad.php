<?php
session_start();
require 'cronometro_logica.php';

$conn = new mysqli("localhost", "DBUSER2025", "DBPSWD2025", "UO295286_DB");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Estado de la prueba
$prueba_iniciada   = isset($_SESSION['prueba_iniciada']) ? $_SESSION['prueba_iniciada'] : false;
$prueba_finalizada = isset($_SESSION['prueba_finalizada']) ? $_SESSION['prueba_finalizada'] : false;

// --- Botón INICIAR PRUEBA ---
if (isset($_POST['iniciar'])) {
    $cron = new Cronometro();
    $cron->arrancar();
    $_SESSION['cron'] = serialize($cron);
    $_SESSION['prueba_iniciada'] = true;
    $_SESSION['prueba_finalizada'] = false;
    $prueba_iniciada = true;
    $prueba_finalizada = false;
}

// --- Botón FINALIZAR PRUEBA (primer paso) ---
if (isset($_POST['terminar'])) {

    if (isset($_SESSION['cron'])) {
        $cron = unserialize($_SESSION['cron']);
        $cron->parar();
        $tiempo = $cron->getTiempo();
        $_SESSION['tiempo_prueba'] = $tiempo;
    }

    // Guardamos solo las respuestas a las preguntas
    $_SESSION['datos_prueba'] = array(
        'pregunta1' => isset($_POST['pregunta1']) ? $_POST['pregunta1'] : '',
        'pregunta2' => isset($_POST['pregunta2']) ? $_POST['pregunta2'] : '',
        'pregunta3' => isset($_POST['pregunta3']) ? $_POST['pregunta3'] : '',
        'pregunta4' => isset($_POST['pregunta4']) ? $_POST['pregunta4'] : '',
        'pregunta5' => isset($_POST['pregunta5']) ? $_POST['pregunta5'] : '',
        'pregunta6' => isset($_POST['pregunta6']) ? $_POST['pregunta6'] : '',
        'pregunta7' => isset($_POST['pregunta7']) ? $_POST['pregunta7'] : ''
    );

    $_SESSION['prueba_iniciada'] = true;
    $_SESSION['prueba_finalizada'] = true;
    $prueba_iniciada = true;
    $prueba_finalizada = true;
}

// --- Botón ENVIAR RESULTADOS (segundo paso) ---
if (isset($_POST['enviar'])) {
    if (isset($_SESSION['datos_prueba']) && isset($_SESSION['tiempo_prueba'])) {
        $datos  = $_SESSION['datos_prueba'];
        $tiempo = $_SESSION['tiempo_prueba'];

        // Datos personales (segunda pantalla)
        $codigo     = isset($_POST['codigo']) ? (int)$_POST['codigo'] : 0;
        $profesion  = isset($_POST['profesion']) ? $_POST['profesion'] : '';
        $edad       = isset($_POST['edad']) ? (int)$_POST['edad'] : 0;
        $genero     = isset($_POST['genero']) ? $_POST['genero'] : '';
        $pericia    = isset($_POST['pericia']) ? $_POST['pericia'] : '';
        $dispositivo= isset($_POST['dispositivo']) ? $_POST['dispositivo'] : '';

        $comentarios   = isset($_POST['comentarios']) ? $_POST['comentarios'] : '';
        $propuestas    = isset($_POST['propuestas']) ? $_POST['propuestas'] : '';
        $valoracion    = isset($_POST['valoracion']) ? (int)$_POST['valoracion'] : null;
        $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';

        // Insert en Usuarios
        $stmt = $conn->prepare("INSERT INTO Usuarios (codigo_usuario, profesion, edad, genero, pericia) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $codigo, $profesion, $edad, $genero, $pericia);
        $stmt->execute();
        $stmt->close();

        // Insert en Resultados
        $completado    = 1;
        $tiempo_entero = (int)round($tiempo);
        $stmt = $conn->prepare("INSERT INTO Resultados (codigo_usuario, dispositivo, tiempo, completado, comentarios_usuario, propuestas, valoracion) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isisssi", $codigo, $dispositivo, $tiempo_entero, $completado, $comentarios, $propuestas, $valoracion);
        $stmt->execute();
        $stmt->close();

        // Insert en Observaciones (si hay)
        if ($observaciones !== '') {
            $stmt = $conn->prepare("INSERT INTO Observaciones (codigo_usuario, comentario) VALUES (?, ?)");
            $stmt->bind_param("is", $codigo, $observaciones);
            $stmt->execute();
            $stmt->close();
        }

        // Limpiar sesión
        unset($_SESSION['cron']);
        unset($_SESSION['tiempo_prueba']);
        unset($_SESSION['datos_prueba']);
        unset($_SESSION['prueba_iniciada']);
        unset($_SESSION['prueba_finalizada']);
        unset($_SESSION['cronometro_inicio']);
        unset($_SESSION['cronometro_tiempo']);

        echo("Prueba finalizada");
        exit();
    }
}

// Valores por defecto para rellenar el formulario
$valores = array(
    'codigo'      => '',
    'profesion'   => '',
    'edad'        => '',
    'genero'      => '',
    'pericia'     => '',
    'dispositivo' => '',
    'pregunta1'   => '',
    'pregunta2'   => '',
    'pregunta3'   => '',
    'pregunta4'   => '',
    'pregunta5'   => '',
    'pregunta6'   => '',
    'pregunta7'   => ''
);

if (isset($_SESSION['datos_prueba'])) {
    foreach ($valores as $clave => $valor) {
        if (isset($_SESSION['datos_prueba'][$clave])) {
            $valores[$clave] = $_SESSION['datos_prueba'][$clave];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de usabilidad</title>
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

    <!-- PREGUNTAS: visibles SIEMPRE.
         Antes de finalizar: required.
         Después de finalizar: readonly. -->

    <p>
        <label>Pregunta 1: ¿Cuál es la cilindrada de las MotoGP actuales?<br>
            <input type="text" name="pregunta1"
                   value="<?php echo htmlspecialchars($valores['pregunta1']); ?>"
                   <?php echo $prueba_finalizada ? 'readonly' : 'required'; ?>>
        </label>
    </p>

    <p>
        <label>Pregunta 2: ¿Cuál es el nombre del piloto actual de MotoGP con más campeonatos mundiales?<br>
            <input type="text" name="pregunta2"
                   value="<?php echo htmlspecialchars($valores['pregunta2']); ?>"
                   <?php echo $prueba_finalizada ? 'readonly' : 'required'; ?>>
        </label>
    </p>

    <p>
        <label>Pregunta 3: ¿Qué tipo de neumáticos utilizan las motocicletas en MotoGP?<br>
            <input type="text" name="pregunta3"
                   value="<?php echo htmlspecialchars($valores['pregunta3']); ?>"
                   <?php echo $prueba_finalizada ? 'readonly' : 'required'; ?>>
        </label>
    </p>

    <p>
        <label>Pregunta 4: ¿Cuántos cilindros suele tener el motor de una MotoGP?<br>
            <input type="text" name="pregunta4"
                   value="<?php echo htmlspecialchars($valores['pregunta4']); ?>"
                   <?php echo $prueba_finalizada ? 'readonly' : 'required'; ?>>
        </label>
    </p>

    <p>
        <label>Pregunta 5: Nombra un circuito famoso que aparece en el calendario de MotoGP.<br>
            <input type="text" name="pregunta5"
                   value="<?php echo htmlspecialchars($valores['pregunta5']); ?>"
                   <?php echo $prueba_finalizada ? 'readonly' : 'required'; ?>>
        </label>
    </p>

    <p>
        <label>Pregunta 6: ¿En qué año se celebró el primer Campeonato del Mundo de MotoGP?<br>
            <input type="text" name="pregunta6"
                   value="<?php echo htmlspecialchars($valores['pregunta6']); ?>"
                   <?php echo $prueba_finalizada ? 'readonly' : 'required'; ?>>
        </label>
    </p>

    <p>
        <label>Pregunta 7: ¿Cuál es la categoría anterior a MotoGP dentro del Mundial de Motociclismo?<br>
            <input type="text" name="pregunta7"
                   value="<?php echo htmlspecialchars($valores['pregunta7']); ?>"
                   <?php echo $prueba_finalizada ? 'readonly' : 'required'; ?>>
        </label>
    </p>

    <?php if ($prueba_finalizada): ?>

        <!-- DATOS PERSONALES: solo se ven DESPUÉS de Finalizar prueba -->

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
                    <option value="Otro" <?php if ($valores['genero'] === 'Otro') echo 'selected'; ?>>Otro</option>
                </select>
            </label>
        </p>

        <p>
            <label>Pericia informática:
                <select name="pericia" required>
                    <option value="">Seleccione</option>
                    <option value="Baja" <?php if ($valores['pericia'] === 'Baja') echo 'selected'; ?>>Baja</option>
                    <option value="Media" <?php if ($valores['pericia'] === 'Media') echo 'selected'; ?>>Media</option>
                    <option value="Alta" <?php if ($valores['pericia'] === 'Alta') echo 'selected'; ?>>Alta</option>
                </select>
            </label>
        </p>

        <p>
            <label>Dispositivo:
                <select name="dispositivo" required>
                    <option value="">Seleccione</option>
                    <option value="Ordenador" <?php if ($valores['dispositivo'] === 'Ordenador') echo 'selected'; ?>>Ordenador</option>
                    <option value="Tableta" <?php if ($valores['dispositivo'] === 'Tableta') echo 'selected'; ?>>Tableta</option>
                    <option value="Teléfono" <?php if ($valores['dispositivo'] === 'Teléfono') echo 'selected'; ?>>Teléfono</option>
                </select>
            </label>
        </p>

        <!-- COMENTARIOS Y VALORACIÓN (segunda pantalla) -->

        <p>
            <label>Comentarios del usuario:<br>
                <textarea name="comentarios" rows="4"></textarea>
            </label>
        </p>

        <p>
            <label>Propuestas de mejora:<br>
                <textarea name="propuestas" rows="4"></textarea>
            </label>
        </p>

        <p>
            <label>Valoración de la aplicación (0 a 10):
                <input type="number" name="valoracion" min="0" max="10" required>
            </label>
        </p>

        <p>
            <label>Comentarios del observador:<br>
                <textarea name="observaciones" rows="4"></textarea>
            </label>
        </p>

        <p>
            <button type="submit" name="enviar">Enviar resultados</button>
        </p>

    <?php else: ?>

        <!-- Primera pantalla: solo preguntas + botón finalizar -->

        <p>
            <button type="submit" name="terminar">Finalizar prueba</button>
        </p>

    <?php endif; ?>

</form>

<?php endif; ?>

</body>
</html>
