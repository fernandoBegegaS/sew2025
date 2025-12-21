<?php
class Configuracion {
    private mysqli $conn;
    private string $dbName = "UO295286_DB";

    public function __construct() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->conn = new mysqli("localhost", "DBUSER2025", "DBPSWD2025");
        $this->conn->set_charset("utf8mb4");

        try {
            $this->conn->select_db($this->dbName);
        } catch (mysqli_sql_exception $e) {

            $this->crearBaseDeDatos();
            $this->conn->select_db($this->dbName);

            $this->crearTablasDesdeSQL("UO295286_DB.sql");
        }
    }

    private function crearBaseDeDatos(): void {
        $db = $this->dbName;
        $this->conn->query(
            "CREATE DATABASE IF NOT EXISTS `$db`
             CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci"
        );
    }

    private function crearTablasDesdeSQL(string $rutaSQL): void {
        if (!is_readable($rutaSQL)) {
            throw new RuntimeException("No se ha encontrado/lechable el SQL: " . $rutaSQL);
        }

        $sql = file_get_contents($rutaSQL);
        if ($sql === false) {
            throw new RuntimeException("No se ha podido leer el archivo SQL.");
        }

        $this->conn->multi_query($sql);

        do {
            if ($res = $this->conn->store_result()) {
                $res->free();
            }
        } while ($this->conn->more_results() && $this->conn->next_result());
    }

public function reiniciar() {

    try {
        $this->conn->select_db($this->dbName);
    } catch (mysqli_sql_exception $e) {
        $this->crearBaseDeDatos();
        $this->conn->select_db($this->dbName);
        $this->crearTablasDesdeSQL(__DIR__ . "/UO295286_DB.sql");
        return "Base de datos creada y lista (ya estaba vacía).";
    }

    $this->conn->query("SET FOREIGN_KEY_CHECKS=0");

    try {
        try {
            $this->conn->query("TRUNCATE TABLE Respuestas");
            $this->conn->query("TRUNCATE TABLE Observaciones");
            $this->conn->query("TRUNCATE TABLE Resultados");
            $this->conn->query("TRUNCATE TABLE Usuarios");
            return "Base de datos reiniciada correctamente.";
        } catch (mysqli_sql_exception $e) {
            $this->crearTablasDesdeSQL(__DIR__ . "/UO295286_DB.sql");
            return "Tablas recreadas y base de datos lista (ya estaba limpia).";
        }
    } finally {
        $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
    }
}

    public function eliminar() {
        $db = $this->dbName;
        $this->conn->query("DROP DATABASE IF EXISTS `$db`");
        return "Base de datos eliminada correctamente.";
    }

public function exportarCSV() {

    while (ob_get_level()) { ob_end_clean(); }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="datosUsabilidad.csv"');

    $out = fopen('php://output', 'w');
    if (!$out) { exit; }

    fwrite($out, "\xEF\xBB\xBF");
    $d = ';';
    $n = 10; 

    $head = array(
        'CódigoUsuario','Profesión','Edad','Género','Pericia',
        'IdResultado','Dispositivo','Tiempo','Completado',
        'ComentariosUsuario','Propuestas','Valoración','ComentarioFacilitador'
    );
    for ($i = 1; $i <= $n; $i++) { $head[] = "P$i"; }
    fputcsv($out, $head, $d);

    $cases = array();
    for ($i = 1; $i <= $n; $i++) { $cases[] = "MAX(CASE WHEN num_pregunta=$i THEN respuesta END) AS p$i"; }

    $pcols = array();
    for ($i = 1; $i <= $n; $i++) { $pcols[] = "resp.p$i"; }

    $sql = "
        SELECT
            u.codigo_usuario, u.profesion, u.edad, u.genero, u.pericia,
            r.id_resultado, r.dispositivo, r.tiempo, r.completado,
            r.comentarios_usuario, r.propuestas, r.valoracion,
            o.comentario_facilitador,
            " . implode(", ", $pcols) . "
        FROM Usuarios u
        LEFT JOIN Resultados r ON u.codigo_usuario = r.codigo_usuario
        LEFT JOIN (
            SELECT codigo_usuario, GROUP_CONCAT(comentario SEPARATOR ' | ') AS comentario_facilitador
            FROM Observaciones
            GROUP BY codigo_usuario
        ) o ON u.codigo_usuario = o.codigo_usuario
        LEFT JOIN (
            SELECT id_resultado, " . implode(", ", $cases) . "
            FROM Respuestas
            GROUP BY id_resultado
        ) resp ON resp.id_resultado = r.id_resultado
        ORDER BY u.codigo_usuario, r.id_resultado
    ";

    $res = $this->conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {

            $line = array(
                $row['codigo_usuario'],
                $row['profesion'],
                $row['edad'],
                $row['genero'],
                $row['pericia'],
                $row['id_resultado'],
                $row['dispositivo'],
                $row['tiempo'],
                (!empty($row['completado']) ? '1' : '0'),
                $row['comentarios_usuario'],
                $row['propuestas'],
                $row['valoracion'],
                $row['comentario_facilitador']
            );

            for ($i = 1; $i <= $n; $i++) { $line[] = $row["p$i"]; }

            fputcsv($out, $line, $d);
        }
    }

    fclose($out);
    exit;
}


}
?>
