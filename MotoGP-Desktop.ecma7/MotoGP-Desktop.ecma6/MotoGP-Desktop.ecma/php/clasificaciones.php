<?php
class Clasificacion
{
    private $documento;
    private $xml;

    public function __construct()
    {
        $this->documento = "../xml/circuitoEsquema.xml";
        $this->xml = null;
    }

    public function consultar()
    {
        if (is_readable($this->documento)) {
            $datos = file_get_contents($this->documento);

            if ($datos !== false) {
                $datosSinNamespace = str_replace('xmlns="http://www.uniovi.es"', '', $datos);

                $this->xml = new SimpleXMLElement($datosSinNamespace);
            }
        }
    }

    public function obtenerGanador()
    {
        if ($this->xml === null) {
            return null;
        }

        if (!isset($this->xml->resultado)) {
            return null;
        }

        $resultado = $this->xml->resultado;

        $nombre = "";
        $tiempo = "";

        if (isset($resultado->vencedor)) {
            $nombre = (string)$resultado->vencedor;
        }

        if (isset($resultado->tiempo)) {
            $tiempo = (string)$resultado->tiempo;
        }

        if ($nombre === "" && $tiempo === "") {
            return null;
        }

        return array(
            "nombre" => $nombre,
            "tiempo" => $tiempo
        );
    }

    public function obtenerClasificacionMundial()
    {
        $resultado = array();

        if ($this->xml === null) {
            return $resultado;
        }

        if (!isset($this->xml->clasificados_mundial)) {
            return $resultado;
        }

        $clasificacion = $this->xml->clasificados_mundial;

        if (!isset($clasificacion->piloto)) {
            return $resultado;
        }

        foreach ($clasificacion->piloto as $piloto) {
            $posicion = "";
            $nombre = "";

            if (isset($piloto['posicion'])) {
                $posicion = (string)$piloto['posicion'];
            }

            $nombre = trim((string)$piloto);

            $resultado[] = array(
                "posicion" => $posicion,
                "nombre"   => $nombre
            );
        }

        return $resultado;
    }
}

$clasificacion = new Clasificacion();
$clasificacion->consultar();
$ganador = $clasificacion->obtenerGanador();
$mundial = $clasificacion->obtenerClasificacionMundial();
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>MotoGP-Clasificaciones</title>
    <meta name="author" content="Fernando Begega Suarez" />
    <meta name="description" content="Página que contiene las clasificaciones de las carreras" />
    <meta name="keywords" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="../multimedia/imagenes/icon.ico" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css" />
</head>

<body>
    <header>
        <h1><a href="../index.html">MotoGP Desktop</a></h1>
        <nav>
            <a href="../index.html" title="Inicio">Inicio</a>
            <a href="../piloto.html" title="Información piloto">Piloto</a>
            <a href="../circuito.html" title="Información circuito">Circuito</a>
            <a href="../meteorologia.html" title="Información meteorología">Meteorología</a>
            <a href="clasificaciones.php" title="Información clasificaciones" class="active">Clasificaciones</a>
            <a href="../juegos.html" title="Información juegos">Juegos</a>
            <a href="../ayuda.html" title="Ayuda">Ayuda</a>
        </nav>
    </header>

    <p><a href="../index.html">Inicio</a> >> <strong>Clasificaciones</strong></p>
    <h2>Clasificaciones: Gran Premio de las Americas</h2>

    <?php if ($ganador !== null) { ?>
        <h3>Ganador del Gran Premio</h3>
        <p>Piloto ganador:
            <?php echo htmlspecialchars($ganador["nombre"]); ?>
        </p>
        <p>Tiempo empleado:
            <?php echo htmlspecialchars($ganador["tiempo"]); ?>
        </p>
    <?php } else { ?>
        <p>No se ha podido obtener la información del ganador de la carrera.</p>
    <?php } ?>

    <h3>Clasificación del mundial tras el</h3>

    <?php if (!empty($mundial)) { ?>
        <table>
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Piloto</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($mundial as $fila) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($fila["posicion"]); ?></td>
                    <td><?php echo htmlspecialchars($fila["nombre"]); ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No se ha podido leer la clasificación del mundial.</p>
    <?php } ?>

</body>
</html>
