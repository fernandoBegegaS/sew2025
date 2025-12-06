<?php
class Configuracion {
    private $conn;
    public function __construct() {
        $this->conn = new mysqli("localhost", "DBUSER2025", "DBPSWD2025", "UO295286_DB");
        if ($this->conn->connect_error) {
            die("Conexión fallida: " . $this->conn->connect_error);
        }
    }
    public function reiniciar() {
        $this->conn->query("SET FOREIGN_KEY_CHECKS=0");
        $this->conn->query("TRUNCATE TABLE Observaciones");
        $this->conn->query("TRUNCATE TABLE Resultados");
        $this->conn->query("TRUNCATE TABLE Usuarios");
        $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
        echo "Base de datos reiniciada correctamente.";
    }
    public function eliminar() {
        $this->conn->query("DROP DATABASE UO295286_DB");
        echo "Base de datos eliminada correctamente.";
    }
    public function exportarCSV() {
        $sql = "SELECT u.codigo_usuario, u.profesion, u.edad, u.genero, u.pericia, r.dispositivo, r.tiempo, r.completado, r.comentarios_usuario, r.propuestas, r.valoracion, o.comentario AS comentario_facilitador
                FROM Usuarios u
                LEFT JOIN Resultados r ON u.codigo_usuario = r.codigo_usuario
                LEFT JOIN Observaciones o ON u.codigo_usuario = o.codigo_usuario";
        $result = $this->conn->query($sql);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=datosUsabilidad.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('CódigoUsuario', 'Profesión', 'Edad', 'Género', 'Pericia', 'Dispositivo', 'Tiempo', 'Completado', 'ComentariosUsuario', 'Propuestas', 'Valoración', 'ComentarioFacilitador'));
        if ($result) {
            while($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
        }
        exit();
    }
}
?>
