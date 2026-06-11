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
    PRIMARY KEY (id_reserva),
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE
);
ALTER TABLE reserva
    ADD COLUMN servico VARCHAR(100) NULL AFTER id_empresa,
    ADD COLUMN num_pessoas INT NOT NULL DEFAULT 1 AFTER hora_reserva,
    ADD COLUMN observacao TEXT NULL AFTER num_pessoas;
INSERT INTO empresa (nome_empresa) VALUES ('Nome da Sua Empresa');

INSERT INTO usuario (nome, data_nasc, cpf, telefone, email, senha, tipo_perfil)
VALUES ('João Silva', '1990-05-20', '123.456.789-00', '(41) 99999-9999', 'joao@email.com', 'senha123', 'funcionario');

INSERT INTO funcionario (id_usuario, id_empresa)
VALUES (LAST_INSERT_ID(), 1);