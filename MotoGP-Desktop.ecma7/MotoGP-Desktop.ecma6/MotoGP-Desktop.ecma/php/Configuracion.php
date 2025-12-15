<?php
class Configuracion {
    private $conn;
    private $dbName = "UO295286_DB";

    public function __construct() {
        // Conexión al servidor (sin BD de momento)
        $this->conn = new mysqli("localhost", "DBUSER2025", "DBPSWD2025");

        if ($this->conn->connect_error) {
            die("Conexión fallida: " . $this->conn->connect_error);
        }

        // Charset recomendado
        $this->conn->set_charset("utf8mb4");

        // Intentar seleccionar la BD; si no existe, crearla desde el SQL
        if (!$this->conn->select_db($this->dbName)) {
            $this->crearBaseDeDatosDesdeSQL();
            if (!$this->conn->select_db($this->dbName)) {
                die("No se pudo seleccionar la base de datos tras crearla.");
            }
        }
    }

    /**
     * Lee el archivo UO295286_DB.sql y ejecuta todo su contenido.
     * El .sql debe contener los CREATE DATABASE/TABLES que ya tienes.
     */
    private function crearBaseDeDatosDesdeSQL() {
        // Ajusta la ruta si el SQL está en otro sitio
        $rutaSQL =  "UO295286_DB.sql";

        if (!file_exists($rutaSQL)) {
            die("No se ha encontrado el archivo SQL: " . $rutaSQL);
        }

        $sql = file_get_contents($rutaSQL);
        if ($sql === false) {
            die("No se ha podido leer el archivo SQL.");
        }

        if ($this->conn->multi_query($sql) === false) {
            die("Error al ejecutar el script SQL: " . $this->conn->error);
        }

        // Limpiar todos los posibles resultados intermedios
        do {
            if ($resultado = $this->conn->store_result()) {
                $resultado->free();
            }
        } while ($this->conn->more_results() && $this->conn->next_result());
    }
    public function reiniciar() {
        $this->conn->query("SET FOREIGN_KEY_CHECKS=0");
        $this->conn->query("TRUNCATE TABLE Observaciones");
        $this->conn->query("TRUNCATE TABLE Resultados");
        $this->conn->query("TRUNCATE TABLE Usuarios");
        $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
        return "Base de datos reiniciada correctamente.";
    }
    public function eliminar() {
        $this->conn->query("DROP DATABASE UO295286_DB");
        return "Base de datos eliminada correctamente.";
    }
public function exportarCSV() {
    $sql = "SELECT 
                u.codigo_usuario,
                u.profesion,
                u.edad,
                u.genero,
                u.pericia,
                r.dispositivo,
                r.tiempo,
                r.completado,
                r.comentarios_usuario,
                r.propuestas,
                r.valoracion,
                o.comentario AS comentario_facilitador
            FROM Usuarios u
            LEFT JOIN Resultados r ON u.codigo_usuario = r.codigo_usuario
            LEFT JOIN Observaciones o ON u.codigo_usuario = o.codigo_usuario";

    $result = $this->conn->query($sql);

    if (!$result) {
        die("Error al obtener los datos para CSV: " . $this->conn->error);
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=datosUsabilidad.csv');

    $output = fopen('php://output', 'w');

    // Cabecera del CSV (usamos ; como separador)
    fputcsv($output, array(
        'CodigoUsuario',
        'Profesion',
        'Edad',
        'Genero',
        'Pericia',
        'Dispositivo',
        'Tiempo',
        'Completado',
        'ComentariosUsuario',
        'Propuestas',
        'Valoracion',
        'ComentarioFacilitador'
    ), ';');

    // Filas de datos
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array_values($row), ';');
    }

    fclose($output);
    exit();
}

}
?>
