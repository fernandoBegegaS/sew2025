<?php
require 'cronometro_logica.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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


    private function conectarBD() {
        
        try {
            $this->conn = new mysqli("localhost", "DBUSER2025", "DBPSWD2025", "UO295286_DB");
            $this->conn->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            
            error_log("Error BD ({$e->getCode()}): {$e->getMessage()}");
    
            
            $this->reiniciarSesion();
    
            
            $this->mostrarPantallaErrorBD((int)$e->getCode());
            exit();
        }
    }

private function reiniciarSesion() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

private function mostrarPantallaErrorBD(int $errno) {
    $mensaje = ($errno === 1049)
        ? "No existe la base de datos necesaria (UO295286_DB) o no está disponible en este servidor."
        : "No se ha podido establecer conexión con la base de datos en este momento.";

    $self = htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8');

    echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error de base de datos</title>
    <meta name ="author" content ="Fernando Begega Suarez"/>
    <meta name ="description" content ="Página error base de datos" />
    <meta name ="keywords" content ="" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Pantalla de error cuando no existe o no está disponible la base de datos de la prueba de usabilidad.">
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css">
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css">
    <link rel="icon" href="../multimedia/imagenes/configuracion.ico" type="image/x-icon">
</head>
<body>
    <main>
        <h1>Error de base de datos</h1>
        <p>La base de datos no existe</p>
        <p>Acceda a la opcion de "Administracion de base de datos" en el menu de "Juegos" y pulse la opcion "Reinicia base de datos" </p>
        <p>Se ha reiniciado la sesión de la prueba para evitar guardar datos incompletos.</p>
        <p><a href="{$self}">Reintentar</a></p>
    </main>
</body>
</html>
HTML;
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
        if (isset($_SESSION['cron'])) {
            $cron = unserialize($_SESSION['cron']);
            $cron->parar();
            $tiempo = $cron->getTiempo();
            $_SESSION['tiempo_prueba'] = $tiempo;
        }

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

    $codigo      = isset($_POST['codigo']) ? (int)$_POST['codigo'] : 0;
    $profesion   = isset($_POST['profesion']) ? $_POST['profesion'] : '';
    $edad        = isset($_POST['edad']) ? (int)$_POST['edad'] : 0;
    $genero      = isset($_POST['genero']) ? $_POST['genero'] : '';
    $pericia     = isset($_POST['pericia']) ? (int)$_POST['pericia'] : 0;
    $dispositivo = isset($_POST['dispositivo']) ? $_POST['dispositivo'] : '';

    $comentarios   = isset($_POST['comentarios']) ? $_POST['comentarios'] : '';
    $propuestas    = isset($_POST['propuestas']) ? $_POST['propuestas'] : '';
    $valoracion    = isset($_POST['valoracion']) ? (int)$_POST['valoracion'] : 0;
    $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';

    $tiempo_entero = (int) round($tiempo);
    $completado    = 1;

    $datosPrueba = $_SESSION['datos_prueba'];

    $this->conn->begin_transaction();

    try {
        
        $stmt = $this->conn->prepare(
            "INSERT INTO Usuarios (codigo_usuario, profesion, edad, genero, pericia)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                profesion = VALUES(profesion),
                edad      = VALUES(edad),
                genero    = VALUES(genero),
                pericia   = VALUES(pericia)"
        );
        $stmt->bind_param("isisi", $codigo, $profesion, $edad, $genero, $pericia);
        $stmt->execute();
        $stmt->close();

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
        $id_resultado = $stmt->insert_id; 
        $stmt->close();

        $stmtR = $this->conn->prepare(
            "INSERT INTO Respuestas (id_resultado, num_pregunta, respuesta)
             VALUES (?, ?, ?)"
        );

        $num = 0;
        $resp = '';
        $stmtR->bind_param("iis", $id_resultado, $num, $resp);

        foreach ($datosPrueba as $clave => $valor) {
            if (preg_match('/^pregunta(\d+)$/', $clave, $m)) {
                $num  = (int)$m[1];
                $resp = (string)$valor;
                $stmtR->execute();
            }
        }
        $stmtR->close();

        if ($observaciones !== '') {
            $stmt = $this->conn->prepare(
                "INSERT INTO Observaciones (codigo_usuario, comentario)
                 VALUES (?, ?)"
            );
            $stmt->bind_param("is", $codigo, $observaciones);
            $stmt->execute();
            $stmt->close();
        }

        $this->conn->commit();

    } catch (mysqli_sql_exception $e) {
        $this->conn->rollback();

        $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><title>Error</title></head><body>";
        echo "<h1>Error guardando resultados</h1><p>$msg</p>";
        echo "</body></html>";
        exit;
    }

    unset($_SESSION['cron'], $_SESSION['tiempo_prueba'], $_SESSION['datos_prueba'],
          $_SESSION['prueba_iniciada'], $_SESSION['prueba_finalizada'],
          $_SESSION['cronometro_inicio'], $_SESSION['cronometro_tiempo']);

    session_unset();
    session_destroy();

    echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de usabilidad finalizada</title>
    <meta name ="author" content ="Fernando Begega Suarez"/>
    <meta name ="description" content ="Página que se muestra tras finalizar la prueba de usabilidad" />
    <meta name ="keywords" content ="" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css">
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css">
    <link rel="icon" href="../multimedia/imagenes/configuracion.ico" type="image/x-icon">
</head>
<body>
    <h1>Resultados guardados correctamente</h1>
</body>
</html>
HTML;
    exit;
}


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

        $valores = array(
            'codigo'      => '',
            'profesion'   => '',
            'edad'        => '',
            'genero'      => '',
            'pericia'     => '',
            'dispositivo' => ''
        );

        for ($i = 0; $i < $this->numPreguntas; $i++) {
            $clave = 'pregunta' . ($i + 1);
            $valores[$clave] = '';
        }

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
