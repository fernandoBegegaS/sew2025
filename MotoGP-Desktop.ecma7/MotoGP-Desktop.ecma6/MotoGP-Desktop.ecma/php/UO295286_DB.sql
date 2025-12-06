CREATE DATABASE IF NOT EXISTS `UO295286_DB`;
USE `UO295286_DB`;

CREATE TABLE IF NOT EXISTS Usuarios (
  codigo_usuario INT PRIMARY KEY,
  profesion VARCHAR(100) NOT NULL,
  edad INT NOT NULL,
  genero VARCHAR(20) NOT NULL,
  pericia VARCHAR(20) NOT NULL
);

CREATE TABLE IF NOT EXISTS Resultados (
  id_resultado INT AUTO_INCREMENT PRIMARY KEY,
  codigo_usuario INT NOT NULL,
  dispositivo VARCHAR(20) NOT NULL,
  tiempo INT NOT NULL,
  completado BOOLEAN NOT NULL,
  comentarios_usuario TEXT,
  propuestas TEXT,
  valoracion INT,
  FOREIGN KEY (codigo_usuario) REFERENCES Usuarios(codigo_usuario)
);

CREATE TABLE IF NOT EXISTS Observaciones (
  id_observacion INT AUTO_INCREMENT PRIMARY KEY,
  codigo_usuario INT NOT NULL,
  comentario TEXT,
  FOREIGN KEY (codigo_usuario) REFERENCES Usuarios(codigo_usuario)
);
