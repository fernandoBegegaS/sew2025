<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Cronometro {
    private $inicio;
    private $tiempo;

    public function __construct() {
        $this->inicio = null;
        $this->tiempo = 0.0;

        if (isset($_SESSION["cronometro_inicio"])) {
            $this->inicio = $_SESSION["cronometro_inicio"];
        }
        if (isset($_SESSION["cronometro_tiempo"])) {
            $this->tiempo = $_SESSION["cronometro_tiempo"];
        }
    }

    public function arrancar() {
        $this->inicio = microtime(true);
        $_SESSION["cronometro_inicio"] = $this->inicio;
    }

    public function parar() {
        if ($this->inicio !== null) {
            $ahora = microtime(true);
            $this->tiempo = $ahora - $this->inicio;
            $_SESSION["cronometro_tiempo"] = $this->tiempo;
            unset($_SESSION["cronometro_inicio"]);
            $this->inicio = null;
        }
    }

    public function mostrar() {
        $total = $this->tiempo;
        if ($total < 0) {
            $total = 0.0;
        }
        $min = floor($total / 60);
        $seg = floor($total % 60);
        $dec = floor(($total - $min * 60 - $seg) * 10);
        return sprintf("%02d:%02d.%d", $min, $seg, $dec);
    }

    public function getTiempo() {
        return $this->tiempo;
    }
}


$cronometro = new Cronometro();
$tiempoMostrado = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["accion"])) {
        if ($_POST["accion"] === "arrancar") {
            $cronometro->arrancar();
        } elseif ($_POST["accion"] === "parar") {
            $cronometro->parar();
        } elseif ($_POST["accion"] === "mostrar") {
            $tiempoMostrado = $cronometro->mostrar();
        }
    }
}
