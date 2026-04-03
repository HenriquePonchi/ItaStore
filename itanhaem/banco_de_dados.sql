-- ============================================================
-- BANCO DE DADOS: ServiçosItanhaém
-- Versão: 1.0
-- Criado para rodar no XAMPP (MySQL / MariaDB)
-- ============================================================

CREATE DATABASE IF NOT EXISTS servicos_itanhaem
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE servicos_itanhaem;

-- ------------------------------------------------------------
-- TABELA: categorias
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  icone VARCHAR(100) DEFAULT 'fa-briefcase',
  descricao TEXT,
  ativo TINYINT(1) DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categorias (nome, icone, descricao) VALUES
('Eletricista',        'fa-bolt',         'Instalações e reparos elétricos'),
('Encanador',          'fa-faucet',        'Hidráulica e encanamento'),
('Pedreiro',           'fa-hard-hat',      'Construção e reformas'),
('Pintor',             'fa-paint-roller',  'Pintura residencial e comercial'),
('Diarista',           'fa-broom',         'Limpeza e organização'),
('Jardineiro',         'fa-leaf',          'Jardins e áreas externas'),
('Internet / TI',      'fa-wifi',          'Redes, computadores e suporte'),
('Mecânico',           'fa-car',           'Mecânica automotiva'),
('Chaveiro',           'fa-key',           'Abertura e cópia de chaves'),
('Cuidador',           'fa-heart',         'Cuidados com idosos e crianças'),
('Nutricionista',      'fa-apple-alt',     'Nutrição e dieta'),
('Professor Particular','fa-graduation-cap','Aulas e reforço escolar'),
('Fotógrafo',          'fa-camera',        'Fotografia e vídeo'),
('Designer',           'fa-palette',       'Design gráfico e digital'),
('Outros',             'fa-ellipsis-h',    'Outros serviços');

-- ------------------------------------------------------------
-- TABELA: usuarios  (clientes + admins)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(200) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  telefone VARCHAR(20),
  bairro VARCHAR(100),
  tipo ENUM('cliente','admin') DEFAULT 'cliente',
  foto VARCHAR(255) DEFAULT NULL,
  bloqueado TINYINT(1) DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin padrão  (senha: admin123)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('Administrador', 'admin@itanhaem.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
 'admin');

-- ------------------------------------------------------------
-- TABELA: prestadores
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prestadores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(200) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  telefone VARCHAR(20),
  whatsapp VARCHAR(20),
  categoria_id INT,
  bairro VARCHAR(100),
  descricao TEXT,
  foto VARCHAR(255) DEFAULT NULL,
  aprovado TINYINT(1) DEFAULT 0,
  destaque TINYINT(1) DEFAULT 0,
  bloqueado TINYINT(1) DEFAULT 0,
  nota_media DECIMAL(3,2) DEFAULT 0.00,
  total_avaliacoes INT DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABELA: servicos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS servicos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prestador_id INT NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  descricao TEXT,
  preco VARCHAR(100),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (prestador_id) REFERENCES prestadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABELA: portfolio
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolio (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prestador_id INT NOT NULL,
  imagem VARCHAR(255) NOT NULL,
  legenda VARCHAR(255),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (prestador_id) REFERENCES prestadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABELA: avaliacoes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS avaliacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prestador_id INT NOT NULL,
  usuario_id INT NOT NULL,
  nota TINYINT NOT NULL CHECK (nota BETWEEN 1 AND 5),
  comentario TEXT,
  aprovado TINYINT(1) DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unica_avaliacao (prestador_id, usuario_id),
  FOREIGN KEY (prestador_id) REFERENCES prestadores(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABELA: favoritos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS favoritos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  prestador_id INT NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY fav_unico (usuario_id, prestador_id),
  FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)    ON DELETE CASCADE,
  FOREIGN KEY (prestador_id) REFERENCES prestadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABELA: mensagens  (chat básico)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mensagens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  de_usuario_id INT,
  de_prestador_id INT,
  para_usuario_id INT,
  para_prestador_id INT,
  mensagem TEXT NOT NULL,
  lida TINYINT(1) DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Dados de exemplo
INSERT INTO prestadores (nome, email, senha, telefone, whatsapp, categoria_id, bairro, descricao, aprovado, destaque, nota_media, total_avaliacoes) VALUES
('João Eletricista', 'joao@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '13991110001', '5513991110001', 1, 'Centro', 'Eletricista com 10 anos de experiência. Instalações, reparos e quadros elétricos.', 1, 1, 4.80, 25),
('Maria Diarista',   'maria@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '13992220002', '5513992220002', 5, 'Cibratel', 'Diarista experiente, limpeza completa de residências.', 1, 1, 4.60, 18),
('Pedro Encanador',  'pedro@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '13993330003', '5513993330003', 2, 'Jardim Savoy', 'Hidráulica residencial e comercial. Desentupimento 24h.', 1, 0, 4.50, 12),
('Ana Pintora',      'ana@email.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '13994440004', '5513994440004', 4, 'Balneário Itanhaém', 'Pintura interna e externa. Trabalho limpo e detalhado.', 1, 1, 4.90, 30),
('Carlos TI',        'carlos@email.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','13995550005','5513995550005', 7, 'Centro', 'Técnico em informática. Redes, formatação e suporte.', 1, 0, 4.70, 15);

INSERT INTO servicos (prestador_id, titulo, descricao, preco) VALUES
(1, 'Instalação Elétrica', 'Instalação completa de pontos de luz e tomadas', 'A partir de R$ 80'),
(1, 'Troca de Disjuntor',  'Substituição de disjuntores e quadros', 'A partir de R$ 60'),
(2, 'Faxina Completa',     'Limpeza geral da residência', 'A partir de R$ 150'),
(3, 'Desentupimento',      'Desentupimento de ralos e canos', 'A partir de R$ 100'),
(4, 'Pintura Interna',     'Pintura de quartos, salas e cozinhas', 'A partir de R$ 15/m²'),
(5, 'Formatação de PC',    'Formatação e instalação de Windows', 'R$ 80 fixo');
