 
CREATE DATABASE IF NOT EXISTS `5cinf`;
USE `5cinf`;

 
CREATE TABLE IF NOT EXISTS users (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    nome  VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE
);

 
INSERT INTO users (nome, email) VALUES
    ('Mario Rossi',    'mario.rossi@gmail.com'),
    ('Frida Valecchi', 'frida.valecchi@gmail.com'),
    ('Luca Bianchi',   'luca.bianchi@gmail.com');
