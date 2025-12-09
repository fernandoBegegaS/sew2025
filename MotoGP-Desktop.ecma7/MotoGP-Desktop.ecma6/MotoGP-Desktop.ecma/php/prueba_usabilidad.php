<?php
// ---- SESIÓN ----
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'cronometro_logica.php';

class PruebaUsabilidad {

    private $conn;
    private $preguntas = array();
    private $numPreguntas = 0;

    private $prueba_iniciada = false;
    private $prueba_finalizada = false;

    public function __construct() {
        $this->conectarBD();
        $this->cargarPreguntas();
        $this->cargarEstadoDesdeSesion();
    }

    // ---------- CONEXIÓN BD Y PREGUNTAS ----------

    private function conectarBD() {
        $this->conn = new mysqli("localhost", "DBUSER2025", "DBPSWD2025", "UO295286_DB");
        if ($this->conn->connect_error) {
            die("Conexión fallida: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4");
    }

    private function cargarPreguntas() {
        $rutaPreguntas = "preguntas.txt";
        if (!is_readable($rutaPreguntas)) {
            die("No se ha podido leer el fichero de preguntas.");
        }
        $this->preguntas = file($rutaPreguntas, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->numPreguntas = count($this->preguntas);
    }

    private function cargarEstadoDesdeSesion() {
        $this->prueba_iniciada   = isset($_SESSION['prueba_iniciada']) ? $_SESSION['prueba_iniciada'] : false;
        $this->prueba_finalizada = isset($_SESSION['prueba_finalizada']) ? $_SESSION['prueba_finalizada'] : false;
    }

    // ---------- MANEJO DEL FORMULARIO ----------

    public function procesarPeticion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (isset($_POST['iniciar'])) {
            $this->iniciarPrueba();
        } elseif (isset($_POST['terminar'])) {
            $this->finalizarPrueba();
        } elseif (isset($_POST['enviar'])) {
            $this->enviarResultados();
        }

        // Actualizar flags internos después de tocar sesión
        $this->cargarEstadoDesdeSesion();
    }

    private function iniciarPrueba() {
        $cron = new Cronometro();
        $cron->arrancar();

        $_SESSION['cron'] = serialize($cron);
        $_SESSION['prueba_iniciada'] = true;
        $_SESSION['prueba_finalizada'] = false;

        $this->prueba_iniciada = true;
        $this->prueba_finalizada = false;
    }

    private function finalizarPrueba() {
        // Parar cronómetro y guardar tiempo
        if (isset($_SESSION['cron'])) {
            $cron = unserialize($_SESSION['cron']);
            $cron->parar();
            $tiempo = $cron->getTiempo();
            $_SESSION['tiempo_prueba'] = $tiempo;
        }

        // Guardar respuestas a todas las preguntas
        $datosPrueba = array();
        for ($i = 0; $i < $this->numPreguntas; $i++) {
            $clave = 'pregunta' . ($i + 1);
            $datosPrueba[$clave] = isset($_POST[$clave]) ? $_POST[$clave] : '';
        }

        $_SESSION['datos_prueba'] = $datosPrueba;

        $_SESSION['prueba_iniciada'] = true;
        $_SESSION['prueba_finalizada'] = true;

        $this->prueba_iniciada = true;
        $this->prueba_finalizada = true;
    }

    private function enviarResultados() {
        if (!isset($_SESSION['datos_prueba']) || !isset($_SESSION['tiempo_prueba'])) {
            return;
        }

        $tiempo = $_SESSION['tiempo_prueba'];

        // Datos personales (segunda pantalla)
        $codigo      = isset($_POST['codigo']) ? (int)$_POST['codigo'] : 0;
        $profesion   = isset($_POST['profesion']) ? $_POST['profesion'] : '';
        $edad        = isset($_POST['edad']) ? (int)$_POST['edad'] : 0;
        $genero      = isset($_POST['genero']) ? $_POST['genero'] : '';
        $pericia     = isset($_POST['pericia']) ? (int)$_POST['pericia'] : 0; // 1-10
        $dispositivo = isset($_POST['dispositivo']) ? $_POST['dispositivo'] : '';

        $comentarios   = isset($_POST['comentarios']) ? $_POST['comentarios'] : '';
        $propuestas    = isset($_POST['propuestas']) ? $_POST['propuestas'] : '';
        $valoracion    = isset($_POST['valoracion']) ? (int)$_POST['valoracion'] : null;
        $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';

        // Insert en Usuarios
        $stmt = $this->conn->prepare(
            "INSERT INTO Usuarios (codigo_usuario, profesion, edad, genero, pericia)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isisi", $codigo, $profesion, $edad, $genero, $pericia);
        $stmt->execute();
        $stmt->close();

        // Insert en Resultados
        $completado    = 1; // si en algún momento añades "abandona", aquí podría ir 0
        $tiempo_entero = (int) round($tiempo);

        $stmt = $this->conn->prepare(
            "INSERT INTO Resultados
             (codigo_usuario, dispositivo, tiempo, completado,
              comentarios_usuario, propuestas, valoracion)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "isiissi",
            $codigo,
            $dispositivo,
            $tiempo_entero,
            $completado,
            $comentarios,
            $propuestas,
            $valoracion
        );
        $stmt->execute();
        $stmt->close();

        // Insert en Observaciones (si hay)
        if ($observaciones !== '') {
            $stmt = $this->conn->prepare(
                "INSERT INTO Observaciones (codigo_usuario, comentario)
                 VALUES (?, ?)"
            );
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

        session_unset();   // vacía el array $_SESSION
        session_destroy();

        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MotoGP-Desktop - Prueba finalizada</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ventana de finalización de una prueba de usabilidad de MotoGP-Desktop.">
    <!-- Ajusta la ruta si tu PHP NO está dentro de /php/ -->
    <link rel="icon" href="../multimedia/imagenes/icon.ico" type="image/x-icon">
</head>
<body>
    <main>
        <h1>Prueba finalizada</h1>
        <p>La sesión de prueba ha terminado correctamente.</p>
        <p>Si esta ventana no se cierra automáticamente, puedes cerrarla manualmente desde el navegador.</p>
    </main>

    <script>
        // Intentamos cerrar la ventana solo si el navegador lo permite
        window.addEventListener("DOMContentLoaded", function () {
            window.close();
        });
    </script>
</body>
</html>
HTML;
        exit();
    }

    // ---------- GETTERS PARA LA VISTA ----------

    public function getPreguntas() {
        return $this->preguntas;
    }

    public function getNumPreguntas() {
        return $this->numPreguntas;
    }

    public function isPruebaIniciada() {
        return $this->prueba_iniciada;
    }

    public function isPruebaFinalizada() {
        return $this->prueba_finalizada;
    }

    public function getValoresFormulario() {
        // Valores por defecto
        $valores = array(
            'codigo'      => '',
            'profesion'   => '',
            'edad'        => '',
            'genero'      => '',
            'pericia'     => '',
            'dispositivo' => ''
        );

        // Campos de preguntas
        for ($i = 0; $i < $this->numPreguntas; $i++) {
            $clave = 'pregunta' . ($i + 1);
            $valores[$clave] = '';
        }

        // Rellenar desde sesión si existe
        if (isset($_SESSION['datos_prueba'])) {
            foreach ($_SESSION['datos_prueba'] as $clave => $valor) {
                if (array_key_exists($clave, $valores)) {
                    $valores[$clave] = $valor;
                }
            }
        }

        return $valores;
    }
}

// ======================================================
// CÓDIGO "CONTROLADOR" + VISTA
// ======================================================

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

        <!-- PREGUNTAS generadas desde el fichero -->
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

            <!-- DATOS PERSONALES (segunda pantalla) -->

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

            <!-- COMENTARIOS Y VALORACIÓN -->

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

            <!-- Primera pantalla: solo preguntas + botón finalizar -->
            <p>
                <button type="submit" name="terminar">Finalizar prueba</button>
            </p>

        <?php endif; ?>

    </form>

<?php endif; ?>

</body>
</html>
