CREATE DATABASE IF NOT EXISTS bookington;
USE bookington;

CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    data_nasc DATE NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo_perfil ENUM('cliente','funcionario') NOT NULL DEFAULT 'cliente',
    PRIMARY KEY (id_usuario)
);

CREATE TABLE IF NOT EXISTS cliente (
    id_cliente INT NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    PRIMARY KEY (id_cliente),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS empresa (
    id_empresa INT NOT NULL AUTO_INCREMENT,
    nome_empresa VARCHAR(255) NOT NULL,
    PRIMARY KEY (id_empresa)
);

CREATE TABLE IF NOT EXISTS funcionario (
    id_funcionario INT NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_empresa INT NOT NULL,
    PRIMARY KEY (id_funcionario),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reserva (
    id_reserva INT AUTO_INCREMENT,
    id_cliente INT NOT NULL,
    id_empresa INT NOT NULL,
    data_reserva DATE NOT NULL,
    hora_reserva TIME NOT NULL,
    status_reserva ENUM('aberto','reservado','cancelado') NOT NULL DEFAULT 'aberto',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    servico VARCHAR(100) NULL,
    num_pessoas INT NOT NULL DEFAULT 1,
    observacao TEXT NULL,
    PRIMARY KEY (id_reserva),
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE
);