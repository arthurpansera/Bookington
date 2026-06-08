CREATE DATABASE IF NOT EXISTS bookington
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE bookington;

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(20) UNIQUE NOT NULL,
    data_nascimento DATE NOT NULL,
    telefone VARCHAR(30) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    empresa VARCHAR(255) NOT NULL,
    servico VARCHAR(255) NOT NULL,
    data DATE NOT NULL,
    horario TIME NOT NULL,
    num_pessoas INT NOT NULL,
    observacao TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'Em aberto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserva_cliente
        FOREIGN KEY (cliente_id)
        REFERENCES clientes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE,
    descricao TEXT,
    telefone VARCHAR(30),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO empresas (nome)
VALUES
('La Casa Di Frango Ristorante'),
('Churrascaria Praes'),
('Centro de Eventos Carlos'),
('Restaurante Pansera'),
('Espaço Gourmet Vecchi'),
('Casa de Festas Kalil'),
('Espaço de Festas Coltre');