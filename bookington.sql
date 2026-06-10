-- ============================================
-- Banco de Dados - Bookington
-- ============================================

CREATE DATABASE IF NOT EXISTS bookington
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bookington;

-- ============================================
-- Tabela: usuario
-- Dados principais de cadastro
-- ============================================
CREATE TABLE IF NOT EXISTS usuario (
    id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150) NOT NULL,
    cpf             VARCHAR(14) NOT NULL UNIQUE,
    data_nascimento DATE NOT NULL,
    senha           VARCHAR(255) NOT NULL,
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Tabela: contato
-- Email e telefone (usados no login)
-- ============================================
CREATE TABLE IF NOT EXISTS contato (
    id_contato   INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario   INT NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    telefone     VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Tabela: perfil
-- Tipo e status da conta (cliente, banido, ativo, etc.)
-- ============================================
CREATE TABLE IF NOT EXISTS perfil (
    id_perfil      INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario     INT NOT NULL,
    tipo_perfil    ENUM('cliente','empresa','admin') NOT NULL DEFAULT 'cliente',
    status_perfil  ENUM('ativo','inativo','banido') NOT NULL DEFAULT 'ativo',
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Tabela: empresa
-- Empresas/organizações que recebem reservas
-- ============================================
CREATE TABLE IF NOT EXISTS empresa (
    id_empresa    INT AUTO_INCREMENT PRIMARY KEY,
    nome_empresa  VARCHAR(150) NOT NULL,
    endereco      VARCHAR(255),
    categoria     VARCHAR(100)
) ENGINE=InnoDB;

-- ============================================
-- Tabela: reserva
-- Reservas feitas pelos usuários
-- ============================================
CREATE TABLE IF NOT EXISTS reserva (
    id_reserva     INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario     INT NOT NULL,
    id_empresa     INT NOT NULL,
    data_reserva   DATE NOT NULL,
    hora_reserva   TIME NOT NULL,
    status_reserva ENUM('aberto','reservado','cancelado') NOT NULL DEFAULT 'aberto',
    criado_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Dados de exemplo (opcional)
-- ============================================
INSERT INTO empresa (nome_empresa, endereco, categoria) VALUES
('La Casa Di Frango Ristorante', 'Rua das Flores, 123 - Curitiba/PR', 'Restaurante');
