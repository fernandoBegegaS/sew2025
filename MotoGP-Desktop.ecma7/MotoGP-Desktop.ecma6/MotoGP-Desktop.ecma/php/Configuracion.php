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

    public function reiniciar(): string {
        $this->conn->query("SET FOREIGN_KEY_CHECKS=0");
        $this->conn->query("TRUNCATE TABLE Observaciones");
        $this->conn->query("TRUNCATE TABLE Resultados");
        $this->conn->query("TRUNCATE TABLE Usuarios");
        $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
        return "Base de datos reiniciada correctamente.";
    }

    public function eliminar(): string {
        $db = $this->dbName;
        $this->conn->query("DROP DATABASE IF EXISTS `$db`");
        return "Base de datos eliminada correctamente.";
    }

}
?>
