-- ============================================================
-- BANCO DE DADOS - NM REFRIGERAÇÃO
-- Sistema de Gerenciamento Integrado
-- Data de Criação: 2024
-- ============================================================

-- Configurar encoding e charset
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET COLLATION_CONNECTION = utf8mb4_unicode_ci;

-- ============================================================
-- 1. TABELA: usuarios
-- Descrição: Armazena dados dos usuários do sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt da senha',
  `tipo` ENUM('admin', 'tecnico', 'cliente') NOT NULL DEFAULT 'cliente' COMMENT 'Tipo de usuário no sistema',
  `telefone` VARCHAR(20),
  `cpf` VARCHAR(14) UNIQUE,
  `ativo` BOOLEAN DEFAULT TRUE,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` TIMESTAMP NULL,
  KEY `idx_email` (`email`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuários do sistema com dados de autenticação';

-- ============================================================
-- 2. TABELA: clientes
-- Descrição: Informações dos clientes
-- ============================================================
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(200) NOT NULL,
  `email` VARCHAR(150),
  `telefone` VARCHAR(20),
  `celular` VARCHAR(20),
  `cpf_cnpj` VARCHAR(20) UNIQUE,
  `tipo_pessoa` ENUM('fisica', 'juridica') NOT NULL DEFAULT 'fisica',
  `endereco` VARCHAR(200),
  `numero` VARCHAR(20),
  `complemento` VARCHAR(100),
  `bairro` VARCHAR(100),
  `cidade` VARCHAR(100),
  `estado` VARCHAR(2),
  `cep` VARCHAR(10),
  `observacoes` LONGTEXT,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_nome` (`nome`),
  KEY `idx_cpf_cnpj` (`cpf_cnpj`),
  KEY `idx_email` (`email`),
  KEY `idx_celular` (`celular`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dados de clientes do sistema';

-- ============================================================
-- 3. TABELA: categorias_produtos
-- Descrição: Categorias para classificação de produtos
-- ============================================================
CREATE TABLE IF NOT EXISTS `categorias_produtos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL UNIQUE,
  `descricao` TEXT,
  KEY `idx_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorias de produtos disponíveis';

-- ============================================================
-- 4. TABELA: produtos
-- Descrição: Catálogo de produtos
-- ============================================================
CREATE TABLE IF NOT EXISTS `produtos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(200) NOT NULL,
  `descricao` LONGTEXT,
  `categoria_id` INT UNSIGNED,
  `preco_custo` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `preco_venda` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `margem_lucro` DECIMAL(5, 2) COMMENT 'Percentual de margem de lucro',
  `estoque_atual` INT UNSIGNED DEFAULT 0,
  `estoque_minimo` INT UNSIGNED DEFAULT 0,
  `unidade` VARCHAR(20) DEFAULT 'UN' COMMENT 'Unidade de medida (UN, KG, LT, etc)',
  `codigo_barras` VARCHAR(50) UNIQUE,
  `foto` VARCHAR(255),
  `ativo` BOOLEAN DEFAULT TRUE,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_nome` (`nome`),
  KEY `idx_categoria_id` (`categoria_id`),
  KEY `idx_codigo_barras` (`codigo_barras`),
  KEY `idx_ativo` (`ativo`),
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias_produtos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de produtos comercializados';

-- ============================================================
-- 5. TABELA: servicos
-- Descrição: Serviços oferecidos pela empresa
-- ============================================================
CREATE TABLE IF NOT EXISTS `servicos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(200) NOT NULL,
  `descricao` LONGTEXT,
  `preco_base` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `tempo_estimado` INT COMMENT 'Tempo em minutos',
  `materiais_inclusos` TEXT,
  `foto` VARCHAR(255),
  `ativo` BOOLEAN DEFAULT TRUE,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_nome` (`nome`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Serviços oferecidos pela empresa';

-- ============================================================
-- 6. TABELA: pedidos
-- Descrição: Pedidos de clientes
-- ============================================================
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED,
  `data_pedido` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('rascunho', 'confirmado', 'em_processamento', 'entregue', 'cancelado') NOT NULL DEFAULT 'rascunho',
  `valor_produtos` DECIMAL(10, 2) DEFAULT 0,
  `valor_servicos` DECIMAL(10, 2) DEFAULT 0,
  `valor_desconto` DECIMAL(10, 2) DEFAULT 0,
  `valor_total` DECIMAL(10, 2) DEFAULT 0,
  `observacoes` LONGTEXT,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_status` (`status`),
  KEY `idx_data_pedido` (`data_pedido`),
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pedidos dos clientes';

-- ============================================================
-- 7. TABELA: pedidos_produtos
-- Descrição: Produtos inclusos em pedidos
-- ============================================================
CREATE TABLE IF NOT EXISTS `pedidos_produtos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT UNSIGNED NOT NULL,
  `produto_id` INT UNSIGNED NOT NULL,
  `quantidade` DECIMAL(10, 2) NOT NULL,
  `preco_unitario` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) GENERATED ALWAYS AS (quantidade * preco_unitario) STORED,
  KEY `idx_pedido_id` (`pedido_id`),
  KEY `idx_produto_id` (`produto_id`),
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relacionamento de produtos em pedidos';

-- ============================================================
-- 8. TABELA: pedidos_servicos
-- Descrição: Serviços inclusos em pedidos
-- ============================================================
CREATE TABLE IF NOT EXISTS `pedidos_servicos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT UNSIGNED NOT NULL,
  `servico_id` INT UNSIGNED NOT NULL,
  `quantidade` INT UNSIGNED NOT NULL DEFAULT 1,
  `preco_unitario` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) GENERATED ALWAYS AS (quantidade * preco_unitario) STORED,
  KEY `idx_pedido_id` (`pedido_id`),
  KEY `idx_servico_id` (`servico_id`),
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relacionamento de serviços em pedidos';

-- ============================================================
-- 9. TABELA: orcamentos
-- Descrição: Orçamentos enviados aos clientes
-- ============================================================
CREATE TABLE IF NOT EXISTS `orcamentos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED,
  `numero_orcamento` VARCHAR(50) UNIQUE NOT NULL,
  `data_orcamento` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_validade` DATE,
  `status` ENUM('aberto', 'enviado', 'aceito', 'rejeitado', 'expirado') NOT NULL DEFAULT 'aberto',
  `valor_total` DECIMAL(10, 2) DEFAULT 0,
  `observacoes` LONGTEXT,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_numero_orcamento` (`numero_orcamento`),
  KEY `idx_status` (`status`),
  KEY `idx_data_orcamento` (`data_orcamento`),
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Orçamentos para clientes';

-- ============================================================
-- 10. TABELA: orcamentos_itens
-- Descrição: Itens (produtos/serviços) de orçamentos
-- ============================================================
CREATE TABLE IF NOT EXISTS `orcamentos_itens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `orcamento_id` INT UNSIGNED NOT NULL,
  `tipo` ENUM('produto', 'servico') NOT NULL,
  `item_id` INT UNSIGNED COMMENT 'ID do produto ou serviço',
  `descricao` TEXT NOT NULL,
  `quantidade` DECIMAL(10, 2) NOT NULL DEFAULT 1,
  `preco_unitario` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) GENERATED ALWAYS AS (quantidade * preco_unitario) STORED,
  KEY `idx_orcamento_id` (`orcamento_id`),
  KEY `idx_tipo` (`tipo`),
  FOREIGN KEY (`orcamento_id`) REFERENCES `orcamentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Itens de orçamentos';

-- ============================================================
-- 11. TABELA: agendamentos
-- Descrição: Agendamentos de serviços
-- ============================================================
CREATE TABLE IF NOT EXISTS `agendamentos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT UNSIGNED NOT NULL,
  `servico_id` INT UNSIGNED,
  `data_agendamento` DATE NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fim` TIME,
  `status` ENUM('agendado', 'em_progresso', 'concluido', 'cancelado', 'nao_compareceu') NOT NULL DEFAULT 'agendado',
  `observacoes` LONGTEXT,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_servico_id` (`servico_id`),
  KEY `idx_data_agendamento` (`data_agendamento`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Agendamentos de serviços com clientes';

-- ============================================================
-- 12. TABELA: vendas
-- Descrição: Registros de vendas finalizadas
-- ============================================================
CREATE TABLE IF NOT EXISTS `vendas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT UNSIGNED,
  `cliente_id` INT UNSIGNED NOT NULL,
  `data_venda` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valor_bruto` DECIMAL(10, 2) NOT NULL,
  `valor_custo` DECIMAL(10, 2) DEFAULT 0,
  `valor_lucro` DECIMAL(10, 2) GENERATED ALWAYS AS (valor_bruto - valor_custo) STORED,
  `forma_pagamento` ENUM('dinheiro', 'credito', 'debito', 'pix', 'transferencia', 'cheque') NOT NULL,
  `status_pagamento` ENUM('pendente', 'pago', 'parcial', 'atrasado') NOT NULL DEFAULT 'pendente',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_pedido_id` (`pedido_id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_data_venda` (`data_venda`),
  KEY `idx_status_pagamento` (`status_pagamento`),
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de vendas finalizadas';

-- ============================================================
-- 13. TABELA: cobrancas
-- Descrição: Registro de cobranças e pagamentos
-- ============================================================
CREATE TABLE IF NOT EXISTS `cobrancas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `venda_id` INT UNSIGNED NOT NULL,
  `cliente_id` INT UNSIGNED NOT NULL,
  `valor` DECIMAL(10, 2) NOT NULL,
  `data_vencimento` DATE NOT NULL,
  `data_pagamento` DATE,
  `status` ENUM('aberta', 'paga', 'atrasada', 'cancelada') NOT NULL DEFAULT 'aberta',
  `forma_pagamento` ENUM('dinheiro', 'credito', 'debito', 'pix', 'transferencia', 'cheque', 'boleto'),
  `observacoes` TEXT,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_venda_id` (`venda_id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_data_vencimento` (`data_vencimento`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de cobranças e pagamentos';

-- ============================================================
-- 14. TABELA: garantias
-- Descrição: Garantias de serviços prestados
-- ============================================================
CREATE TABLE IF NOT EXISTS `garantias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT UNSIGNED NOT NULL,
  `servico_id` INT UNSIGNED,
  `numero_garantia` VARCHAR(50) UNIQUE NOT NULL,
  `data_emissao` DATE NOT NULL,
  `data_validade` DATE NOT NULL,
  `descricao` LONGTEXT,
  `termos_legais` LONGTEXT,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_servico_id` (`servico_id`),
  KEY `idx_numero_garantia` (`numero_garantia`),
  KEY `idx_data_validade` (`data_validade`),
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Garantias de serviços prestados';

-- ============================================================
-- 15. TABELA: preventivas
-- Descrição: Manutenção preventiva agendada
-- ============================================================
CREATE TABLE IF NOT EXISTS `preventivas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `descricao` LONGTEXT,
  `periodicidade` ENUM('semanal', 'quinzenal', 'mensal', 'trimestral', 'semestral', 'anual') NOT NULL DEFAULT 'mensal',
  `proxima_data` DATE,
  `status` ENUM('ativa', 'pausada', 'concluida', 'cancelada') NOT NULL DEFAULT 'ativa',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_status` (`status`),
  KEY `idx_proxima_data` (`proxima_data`),
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Manutenção preventiva para clientes';

-- ============================================================
-- 16. TABELA: preventivas_checklists
-- Descrição: Itens de checklist para manutenção preventiva
-- ============================================================
CREATE TABLE IF NOT EXISTS `preventivas_checklists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `preventiva_id` INT UNSIGNED NOT NULL,
  `item` VARCHAR(200) NOT NULL,
  `concluido` BOOLEAN DEFAULT FALSE,
  `data_conclusao` TIMESTAMP NULL,
  `observacoes` TEXT,
  KEY `idx_preventiva_id` (`preventiva_id`),
  KEY `idx_concluido` (`concluido`),
  FOREIGN KEY (`preventiva_id`) REFERENCES `preventivas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Itens de checklist para manutenção preventiva';

-- ============================================================
-- 17. TABELA: historico
-- Descrição: Histórico de atividades com clientes
-- ============================================================
CREATE TABLE IF NOT EXISTS `historico` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED,
  `tipo` ENUM('visita', 'chamado', 'venda', 'orcamento', 'manutencao', 'contato', 'observacao') NOT NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `descricao` LONGTEXT,
  `data_servico` TIMESTAMP,
  `status` VARCHAR(50),
  `valor` DECIMAL(10, 2),
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_criado_em` (`criado_em`),
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de atividades com clientes';

-- ============================================================
-- 18. TABELA: relatorios
-- Descrição: Relatórios de serviços executados
-- ============================================================
CREATE TABLE IF NOT EXISTS `relatorios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED,
  `cliente_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `descricao` LONGTEXT,
  `fotos` LONGTEXT COMMENT 'JSON array de caminhos de fotos',
  `data_relatorio` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_data_relatorio` (`data_relatorio`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relatórios de serviços executados';

-- ============================================================
-- 19. TABELA: relatorios_fotos
-- Descrição: Fotos anexadas aos relatórios
-- ============================================================
CREATE TABLE IF NOT EXISTS `relatorios_fotos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `relatorio_id` INT UNSIGNED NOT NULL,
  `caminho_foto` VARCHAR(500) NOT NULL,
  `descricao` TEXT,
  `ordem` INT UNSIGNED DEFAULT 0,
  KEY `idx_relatorio_id` (`relatorio_id`),
  KEY `idx_ordem` (`ordem`),
  FOREIGN KEY (`relatorio_id`) REFERENCES `relatorios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fotos anexadas aos relatórios';

-- ============================================================
-- 20. TABELA: financeiro
-- Descrição: Registro de transações financeiras
-- ============================================================
CREATE TABLE IF NOT EXISTS `financeiro` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tipo` ENUM('receita', 'despesa') NOT NULL,
  `categoria` VARCHAR(100) NOT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(10, 2) NOT NULL,
  `data_transacao` DATE NOT NULL,
  `forma_pagamento` ENUM('dinheiro', 'credito', 'debito', 'pix', 'transferencia', 'cheque', 'boleto'),
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tipo` (`tipo`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_data_transacao` (`data_transacao`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de transações financeiras';

-- ============================================================
-- 21. TABELA: pmp_contratos
-- Descrição: Contratos de Programa de Manutenção Preventiva (PMP)
-- ============================================================
CREATE TABLE IF NOT EXISTS `pmp_contratos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT UNSIGNED NOT NULL,
  `numero_contrato` VARCHAR(50) UNIQUE NOT NULL,
  `data_inicio` DATE NOT NULL,
  `data_fim` DATE,
  `valor_mensal` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('ativo', 'pausado', 'cancelado', 'expirado') NOT NULL DEFAULT 'ativo',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_numero_contrato` (`numero_contrato`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contratos de manutenção preventiva';

-- ============================================================
-- 22. TABELA: pmp_equipamentos
-- Descrição: Equipamentos cobertos por contrato PMP
-- ============================================================
CREATE TABLE IF NOT EXISTS `pmp_equipamentos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `contrato_id` INT UNSIGNED NOT NULL,
  `tipo_equipamento` VARCHAR(100) NOT NULL COMMENT 'Ex: Ar Condicionado, Refrigerador, etc',
  `marca` VARCHAR(100),
  `modelo` VARCHAR(100),
  `numero_serie` VARCHAR(100) UNIQUE,
  `localizacao` VARCHAR(200),
  KEY `idx_contrato_id` (`contrato_id`),
  KEY `idx_numero_serie` (`numero_serie`),
  FOREIGN KEY (`contrato_id`) REFERENCES `pmp_contratos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Equipamentos cobertos por contrato PMP';

-- ============================================================
-- 23. TABELA: pmp_checklists
-- Descrição: Checklists de manutenção preventiva executadas
-- ============================================================
CREATE TABLE IF NOT EXISTS `pmp_checklists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `contrato_id` INT UNSIGNED NOT NULL,
  `data_execucao` DATE NOT NULL,
  `usuario_id` INT UNSIGNED,
  `observacoes` LONGTEXT,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_contrato_id` (`contrato_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_data_execucao` (`data_execucao`),
  FOREIGN KEY (`contrato_id`) REFERENCES `pmp_contratos` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Checklists de manutenção preventiva executadas';

-- ============================================================
-- 24. TABELA: pmp_checklist_itens
-- Descrição: Itens de checklist do PMP
-- ============================================================
CREATE TABLE IF NOT EXISTS `pmp_checklist_itens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `checklist_id` INT UNSIGNED NOT NULL,
  `item` VARCHAR(255) NOT NULL,
  `status` ENUM('ok', 'com_problema', 'pendente', 'n_aplica') DEFAULT 'pendente',
  `observacoes` TEXT,
  KEY `idx_checklist_id` (`checklist_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`checklist_id`) REFERENCES `pmp_checklists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Itens de checklist do PMP';

-- ============================================================
-- 25. TABELA: configuracoes
-- Descrição: Configurações do sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `chave` VARCHAR(100) NOT NULL UNIQUE,
  `valor` LONGTEXT,
  `grupo` VARCHAR(100) COMMENT 'Agrupamento de configurações',
  `descricao` TEXT,
  `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_chave` (`chave`),
  KEY `idx_grupo` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configurações do sistema';

-- ============================================================
-- 26. TABELA: tabelas_precos
-- Descrição: Tabelas de preços para serviços
-- ============================================================
CREATE TABLE IF NOT EXISTS `tabelas_precos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `servico_tipo` VARCHAR(100) NOT NULL,
  `descricao` TEXT,
  `preco_base` DECIMAL(10, 2) NOT NULL,
  `custo_estimado` DECIMAL(10, 2),
  `margem_lucro` DECIMAL(5, 2) COMMENT 'Percentual de margem',
  `ativo` BOOLEAN DEFAULT TRUE,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_servico_tipo` (`servico_tipo`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabelas de preços para serviços';

-- ============================================================
-- 27. TABELA: anexos
-- Descrição: Gerenciamento de arquivos anexos
-- ============================================================
CREATE TABLE IF NOT EXISTS `anexos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `entidade_tipo` VARCHAR(100) NOT NULL COMMENT 'Ex: pedido, orcamento, relatorio',
  `entidade_id` INT UNSIGNED NOT NULL,
  `tipo_arquivo` VARCHAR(50) COMMENT 'pdf, imagem, documento, etc',
  `caminho_arquivo` VARCHAR(500) NOT NULL,
  `nome_original` VARCHAR(255),
  `tamanho` BIGINT UNSIGNED COMMENT 'Tamanho em bytes',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_entidade_tipo_id` (`entidade_tipo`, `entidade_id`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gerenciamento de arquivos anexos';

-- ============================================================
-- 28. TABELA: logs_sistema
-- Descrição: Registro de logs e auditoria do sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS `logs_sistema` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED,
  `acao` VARCHAR(100) NOT NULL COMMENT 'create, update, delete, view',
  `entidade_tipo` VARCHAR(100) COMMENT 'tabela afetada',
  `entidade_id` INT UNSIGNED,
  `dados_anteriores` LONGTEXT COMMENT 'JSON dos dados anteriores',
  `dados_novos` LONGTEXT COMMENT 'JSON dos dados novos',
  `ip` VARCHAR(45),
  `user_agent` VARCHAR(500),
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_acao` (`acao`),
  KEY `idx_entidade_tipo_id` (`entidade_tipo`, `entidade_id`),
  KEY `idx_criado_em` (`criado_em`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de auditoria do sistema';

-- ============================================================
-- 29. TABELA: notificacoes
-- Descrição: Notificações para usuários
-- ============================================================
CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `mensagem` LONGTEXT NOT NULL,
  `tipo` ENUM('info', 'sucesso', 'aviso', 'erro') NOT NULL DEFAULT 'info',
  `lida` BOOLEAN DEFAULT FALSE,
  `data_leitura` TIMESTAMP NULL,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_lida` (`lida`),
  KEY `idx_criado_em` (`criado_em`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notificações para usuários';

-- ============================================================
-- 30. TABELA: mensagens_whatsapp
-- Descrição: Registro de mensagens enviadas via WhatsApp
-- ============================================================
CREATE TABLE IF NOT EXISTS `mensagens_whatsapp` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `destinatario` VARCHAR(20) NOT NULL COMMENT 'Número de telefone com código de país',
  `tipo` ENUM('texto', 'imagem', 'documento', 'link') NOT NULL DEFAULT 'texto',
  `conteudo` LONGTEXT NOT NULL,
  `status` ENUM('pendente', 'enviado', 'entregue', 'lido', 'erro') NOT NULL DEFAULT 'pendente',
  `enviado_em` TIMESTAMP NULL,
  `lido_em` TIMESTAMP NULL,
  `erro` VARCHAR(500) COMMENT 'Mensagem de erro se houver',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_destinatario` (`destinatario`),
  KEY `idx_status` (`status`),
  KEY `idx_criado_em` (`criado_em`),
  KEY `idx_enviado_em` (`enviado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de mensagens WhatsApp enviadas';

-- ============================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================================

-- Índices para busca rápida por status
CREATE INDEX idx_pedidos_status_data ON `pedidos` (`status`, `data_pedido`);
CREATE INDEX idx_vendas_status_data ON `vendas` (`status_pagamento`, `data_venda`);
CREATE INDEX idx_cobrancas_status_vencimento ON `cobrancas` (`status`, `data_vencimento`);
CREATE INDEX idx_orcamentos_cliente_status ON `orcamentos` (`cliente_id`, `status`);

-- Índices para relatórios financeiros
CREATE INDEX idx_financeiro_tipo_data ON `financeiro` (`tipo`, `data_transacao`);
CREATE INDEX idx_financeiro_categoria_data ON `financeiro` (`categoria`, `data_transacao`);

-- Índices para histórico
CREATE INDEX idx_historico_cliente_data ON `historico` (`cliente_id`, `criado_em`);
CREATE INDEX idx_historico_tipo_data ON `historico` (`tipo`, `criado_em`);

-- Índices para buscas por intervalo de datas
CREATE INDEX idx_agendamentos_data_status ON `agendamentos` (`data_agendamento`, `status`);
CREATE INDEX idx_pedidos_data_status ON `pedidos` (`data_pedido`, `status`);

-- ============================================================
-- FIM DO SCRIPT DE SCHEMA
-- ============================================================
