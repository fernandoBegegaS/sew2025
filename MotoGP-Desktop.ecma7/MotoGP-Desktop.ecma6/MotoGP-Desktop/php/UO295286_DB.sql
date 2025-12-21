CREATE DATABASE IF NOT EXISTS `UO295286_DB`;
USE `UO295286_DB`;

CREATE TABLE IF NOT EXISTS Usuarios (
  codigo_usuario INT UNSIGNED PRIMARY KEY,
  profesion      VARCHAR(100) NOT NULL,
  edad           TINYINT UNSIGNED NOT NULL,
  genero         ENUM('Masculino','Femenino','Otro') NOT NULL,
  pericia        TINYINT UNSIGNED NOT NULL,
  CHECK (pericia BETWEEN 1 AND 10)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Resultados (
  id_resultado        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo_usuario      INT UNSIGNED NOT NULL,
  dispositivo         ENUM('Ordenador','Tableta','Telefono') NOT NULL,
  tiempo              INT UNSIGNED NOT NULL,
  completado          BOOLEAN NOT NULL,
  comentarios_usuario TEXT,
  propuestas          TEXT,
  valoracion          TINYINT UNSIGNED NOT NULL,
  CHECK (tiempo > 0),
  CHECK (valoracion BETWEEN 0 AND 10),
  FOREIGN KEY (codigo_usuario) REFERENCES Usuarios(codigo_usuario)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Observaciones (
  id_observacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo_usuario INT UNSIGNED NOT NULL,
  comentario     TEXT NOT NULL,
  FOREIGN KEY (codigo_usuario) REFERENCES Usuarios(codigo_usuario)
) DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Respuestas (
  id_resultado INT UNSIGNED NOT NULL,
  num_pregunta TINYINT UNSIGNED NOT NULL,
  respuesta    TEXT NOT NULL,
  PRIMARY KEY (id_resultado, num_pregunta),
  CHECK (num_pregunta BETWEEN 1 AND 10),
  FOREIGN KEY (id_resultado) REFERENCES Resultados(id_resultado)
) DEFAULT CHARSET=utf8mb4;
