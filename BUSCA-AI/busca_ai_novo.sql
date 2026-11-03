-- Banco novo do BUSCA AÍ
-- Criado para corresponder às consultas encontradas nos arquivos PHP do ZIP.
-- Não apaga nem altera o banco antigo "busca_ai".

CREATE DATABASE IF NOT EXISTS busca_ai_novo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE busca_ai_novo;

CREATE TABLE IF NOT EXISTS empresas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome_empresa VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    cnpj VARCHAR(14) NOT NULL,
    telefone VARCHAR(30) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_empresas_email (email),
    UNIQUE KEY uq_empresas_cnpj (cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios_buscaai (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_buscaai_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS devolucoes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id INT UNSIGNED NOT NULL,
    numero_pedido VARCHAR(100) NOT NULL,
    nome_cliente VARCHAR(255) NOT NULL,
    telefone_cliente VARCHAR(30) NOT NULL,
    email_cliente VARCHAR(255) NOT NULL,
    endereco_coleta VARCHAR(255) NOT NULL,
    cidade VARCHAR(150) NOT NULL,
    cep VARCHAR(20) NOT NULL,
    produto VARCHAR(255) NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    quantidade INT UNSIGNED NOT NULL DEFAULT 1,
    observacoes TEXT NULL,
    status VARCHAR(100) NOT NULL DEFAULT 'Solicitação',
    data_solicitacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_devolucoes_empresa (empresa_id),
    KEY idx_devolucoes_status (status),
    KEY idx_devolucoes_data (data_solicitacao),
    CONSTRAINT fk_devolucoes_empresa
      FOREIGN KEY (empresa_id) REFERENCES empresas(id)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS historico_devolucoes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    devolucao_id INT UNSIGNED NOT NULL,
    etapa VARCHAR(100) NOT NULL,
    data_etapa TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_historico_devolucao (devolucao_id),
    KEY idx_historico_etapa (etapa),
    CONSTRAINT fk_historico_devolucao
      FOREIGN KEY (devolucao_id) REFERENCES devolucoes(id)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mensagens_contato (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id INT UNSIGNED NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mensagens_empresa (empresa_id),
    KEY idx_mensagens_data (data_envio),
    CONSTRAINT fk_mensagens_empresa
      FOREIGN KEY (empresa_id) REFERENCES empresas(id)
      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Observação:
-- A tabela usuarios_buscaai é para o acesso interno existente.
-- Nenhum usuário administrador é criado automaticamente.
-- Para acessar essa área, será necessário cadastrar o usuário interno de forma segura.
