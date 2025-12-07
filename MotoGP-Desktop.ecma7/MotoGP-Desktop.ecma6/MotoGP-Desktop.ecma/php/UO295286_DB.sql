CREATE DATABASE IF NOT EXISTS `UO295286_DB`;
USE `UO295286_DB`;

CREATE TABLE IF NOT EXISTS Usuarios (
  codigo_usuario INT UNSIGNED PRIMARY KEY,
  profesion      VARCHAR(100) NOT NULL,
  edad           TINYINT UNSIGNED NOT NULL,
  genero         ENUM('Masculino','Femenino','Otro') NOT NULL,
  pericia        TINYINT UNSIGNED NOT NULL CHECK (pericia BETWEEN 1 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Resultados (
  id_resultado        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo_usuario      INT UNSIGNED NOT NULL,
  dispositivo         ENUM('Ordenador','Tableta','Telefono') NOT NULL,
  tiempo              INT UNSIGNED NOT NULL CHECK (tiempo > 0),
  completado          BOOLEAN NOT NULL,
  comentarios_usuario TEXT,
  propuestas          TEXT,
  valoracion          TINYINT UNSIGNED NOT NULL CHECK (valoracion BETWEEN 0 AND 10),
  FOREIGN KEY (codigo_usuario) REFERENCES Usuarios(codigo_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Observaciones (
  id_observacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo_usuario INT UNSIGNED NOT NULL,
  comentario     TEXT NOT NULL,
  FOREIGN KEY (codigo_usuario) REFERENCES Usuarios(codigo_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
