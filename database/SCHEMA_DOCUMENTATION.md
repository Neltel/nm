# Documentação do Schema - NM Refrigeração

## Visão Geral
Este documento descreve o esquema de banco de dados completo para o Sistema de Gerenciamento Integrado da NM Refrigeração, contendo **30 tabelas** inter-relacionadas para gerenciar todos os aspectos do negócio.

---

## 📋 Tabelas por Categoria

### 1. GESTÃO DE USUÁRIOS E AUTENTICAÇÃO

#### 1.1 `usuarios` (Usuários do Sistema)
**Propósito:** Armazenar dados de autenticação e informações de usuários.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| nome | VARCHAR(150) | NOT NULL | Nome completo |
| email | VARCHAR(150) | NOT NULL, UNIQUE | Email de login |
| senha | VARCHAR(255) | NOT NULL | Hash bcrypt |
| tipo | ENUM | DEFAULT 'cliente' | admin / tecnico / cliente |
| telefone | VARCHAR(20) | - | Contato telefônico |
| cpf | VARCHAR(14) | UNIQUE | CPF único do usuário |
| ativo | BOOLEAN | DEFAULT TRUE | Status de ativação |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |
| ultimo_login | TIMESTAMP | NULL | Último acesso |

**Índices:** email, tipo, ativo

---

### 2. GESTÃO DE CLIENTES

#### 2.1 `clientes` (Dados de Clientes)
**Propósito:** Manter informações completas de clientes (PF e PJ).

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| nome | VARCHAR(200) | NOT NULL | Nome/razão social |
| email | VARCHAR(150) | - | Email para contato |
| telefone | VARCHAR(20) | - | Telefone comercial |
| celular | VARCHAR(20) | - | Celular para contato |
| cpf_cnpj | VARCHAR(20) | UNIQUE | Documento único |
| tipo_pessoa | ENUM | DEFAULT 'fisica' | fisica / juridica |
| endereco | VARCHAR(200) | - | Endereço |
| numero | VARCHAR(20) | - | Número |
| complemento | VARCHAR(100) | - | Complemento |
| bairro | VARCHAR(100) | - | Bairro |
| cidade | VARCHAR(100) | - | Cidade |
| estado | VARCHAR(2) | - | Estado (UF) |
| cep | VARCHAR(10) | - | Código postal |
| observacoes | LONGTEXT | - | Notas diversas |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de cadastro |
| atualizado_em | TIMESTAMP | DEFAULT NOW, UPDATED | Última atualização |

**Índices:** nome, cpf_cnpj, email, celular, criado_em

---

### 3. GESTÃO DE PRODUTOS E SERVIÇOS

#### 3.1 `categorias_produtos` (Categorias de Produtos)
**Propósito:** Classificar produtos em categorias.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| nome | VARCHAR(100) | NOT NULL, UNIQUE | Nome da categoria |
| descricao | TEXT | - | Descrição detalhada |

**Índices:** nome

---

#### 3.2 `produtos` (Catálogo de Produtos)
**Propósito:** Gerenciar inventário de produtos.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| nome | VARCHAR(200) | NOT NULL | Nome do produto |
| descricao | LONGTEXT | - | Descrição detalhada |
| categoria_id | INT UNSIGNED | FK → categorias_produtos | Categoria |
| preco_custo | DECIMAL(10,2) | NOT NULL | Preço de custo |
| preco_venda | DECIMAL(10,2) | NOT NULL | Preço de venda |
| margem_lucro | DECIMAL(5,2) | - | Percentual de lucro |
| estoque_atual | INT UNSIGNED | DEFAULT 0 | Quantidade disponível |
| estoque_minimo | INT UNSIGNED | DEFAULT 0 | Quantidade mínima |
| unidade | VARCHAR(20) | DEFAULT 'UN' | Unidade (UN, KG, LT, etc) |
| codigo_barras | VARCHAR(50) | UNIQUE | Código de barras |
| foto | VARCHAR(255) | - | Caminho da imagem |
| ativo | BOOLEAN | DEFAULT TRUE | Ativo no catálogo |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** nome, categoria_id, codigo_barras, ativo
**Foreign Keys:** categoria_id → categorias_produtos

---

#### 3.3 `servicos` (Serviços Oferecidos)
**Propósito:** Catálogo de serviços.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| nome | VARCHAR(200) | NOT NULL | Nome do serviço |
| descricao | LONGTEXT | - | Descrição detalhada |
| preco_base | DECIMAL(10,2) | NOT NULL | Preço padrão |
| tempo_estimado | INT | - | Tempo em minutos |
| materiais_inclusos | TEXT | - | Materiais inclusos |
| foto | VARCHAR(255) | - | Imagem do serviço |
| ativo | BOOLEAN | DEFAULT TRUE | Status de ativação |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** nome, ativo

---

### 4. GESTÃO DE PEDIDOS E VENDAS

#### 4.1 `pedidos` (Pedidos de Clientes)
**Propósito:** Registrar pedidos de clientes.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| cliente_id | INT UNSIGNED | NOT NULL, FK | Cliente |
| usuario_id | INT UNSIGNED | FK | Usuário responsável |
| data_pedido | TIMESTAMP | DEFAULT NOW | Data do pedido |
| status | ENUM | DEFAULT 'rascunho' | rascunho/confirmado/em_processamento/entregue/cancelado |
| valor_produtos | DECIMAL(10,2) | DEFAULT 0 | Total de produtos |
| valor_servicos | DECIMAL(10,2) | DEFAULT 0 | Total de serviços |
| valor_desconto | DECIMAL(10,2) | DEFAULT 0 | Desconto aplicado |
| valor_total | DECIMAL(10,2) | DEFAULT 0 | Valor total |
| observacoes | LONGTEXT | - | Notas |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** cliente_id, usuario_id, status, data_pedido
**Foreign Keys:** cliente_id → clientes, usuario_id → usuarios

---

#### 4.2 `pedidos_produtos` (Produtos em Pedidos)
**Propósito:** Itens de produtos em pedidos.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| pedido_id | INT UNSIGNED | NOT NULL, FK | Pedido |
| produto_id | INT UNSIGNED | NOT NULL, FK | Produto |
| quantidade | DECIMAL(10,2) | NOT NULL | Quantidade |
| preco_unitario | DECIMAL(10,2) | NOT NULL | Preço unitário |
| subtotal | DECIMAL(10,2) | GENERATED | Total (quantidade × preço) |

**Foreign Keys:** pedido_id → pedidos (CASCADE), produto_id → produtos

---

#### 4.3 `pedidos_servicos` (Serviços em Pedidos)
**Propósito:** Itens de serviços em pedidos.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| pedido_id | INT UNSIGNED | NOT NULL, FK | Pedido |
| servico_id | INT UNSIGNED | NOT NULL, FK | Serviço |
| quantidade | INT UNSIGNED | NOT NULL | Quantidade |
| preco_unitario | DECIMAL(10,2) | NOT NULL | Preço unitário |
| subtotal | DECIMAL(10,2) | GENERATED | Total (quantidade × preço) |

**Foreign Keys:** pedido_id → pedidos (CASCADE), servico_id → servicos

---

#### 4.4 `vendas` (Vendas Finalizadas)
**Propósito:** Registrar vendas consolidadas.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| pedido_id | INT UNSIGNED | FK | Pedido relacionado |
| cliente_id | INT UNSIGNED | NOT NULL, FK | Cliente |
| data_venda | TIMESTAMP | DEFAULT NOW | Data da venda |
| valor_bruto | DECIMAL(10,2) | NOT NULL | Valor bruto |
| valor_custo | DECIMAL(10,2) | DEFAULT 0 | Custo total |
| valor_lucro | DECIMAL(10,2) | GENERATED | Lucro (bruto - custo) |
| forma_pagamento | ENUM | NOT NULL | dinheiro/credito/debito/pix/transferencia/cheque |
| status_pagamento | ENUM | DEFAULT 'pendente' | pendente/pago/parcial/atrasado |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** pedido_id, cliente_id, data_venda, status_pagamento
**Foreign Keys:** pedido_id → pedidos, cliente_id → clientes

---

### 5. GESTÃO DE ORÇAMENTOS

#### 5.1 `orcamentos` (Orçamentos)
**Propósito:** Gerenciar orçamentos enviados aos clientes.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| cliente_id | INT UNSIGNED | NOT NULL, FK | Cliente |
| usuario_id | INT UNSIGNED | FK | Usuário responsável |
| numero_orcamento | VARCHAR(50) | NOT NULL, UNIQUE | Número único |
| data_orcamento | TIMESTAMP | DEFAULT NOW | Data da criação |
| data_validade | DATE | - | Data de vencimento |
| status | ENUM | DEFAULT 'aberto' | aberto/enviado/aceito/rejeitado/expirado |
| valor_total | DECIMAL(10,2) | DEFAULT 0 | Valor total |
| observacoes | LONGTEXT | - | Notas |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** cliente_id, usuario_id, numero_orcamento, status, data_orcamento
**Foreign Keys:** cliente_id → clientes, usuario_id → usuarios

---

#### 5.2 `orcamentos_itens` (Itens de Orçamentos)
**Propósito:** Itens de orçamentos.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| orcamento_id | INT UNSIGNED | NOT NULL, FK | Orçamento |
| tipo | ENUM | NOT NULL | produto / servico |
| item_id | INT UNSIGNED | - | ID do produto/serviço |
| descricao | TEXT | NOT NULL | Descrição do item |
| quantidade | DECIMAL(10,2) | DEFAULT 1 | Quantidade |
| preco_unitario | DECIMAL(10,2) | NOT NULL | Preço unitário |
| subtotal | DECIMAL(10,2) | GENERATED | Total (quantidade × preço) |

**Foreign Keys:** orcamento_id → orcamentos (CASCADE)

---

### 6. GESTÃO DE COBRANÇA E FINANCEIRO

#### 6.1 `cobrancas` (Cobranças)
**Propósito:** Controle de pagamentos.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| venda_id | INT UNSIGNED | NOT NULL, FK | Venda |
| cliente_id | INT UNSIGNED | NOT NULL, FK | Cliente |
| valor | DECIMAL(10,2) | NOT NULL | Valor a cobrar |
| data_vencimento | DATE | NOT NULL | Data de vencimento |
| data_pagamento | DATE | - | Data do pagamento |
| status | ENUM | DEFAULT 'aberta' | aberta/paga/atrasada/cancelada |
| forma_pagamento | ENUM | - | dinheiro/credito/debito/pix/transferencia/cheque/boleto |
| observacoes | TEXT | - | Notas |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** venda_id, cliente_id, data_vencimento, status
**Foreign Keys:** venda_id → vendas, cliente_id → clientes

---

#### 6.2 `financeiro` (Transações Financeiras)
**Propósito:** Registro de receitas e despesas gerais.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| tipo | ENUM | NOT NULL | receita / despesa |
| categoria | VARCHAR(100) | NOT NULL | Categoria |
| descricao | VARCHAR(255) | NOT NULL | Descrição |
| valor | DECIMAL(10,2) | NOT NULL | Valor |
| data_transacao | DATE | NOT NULL | Data da transação |
| forma_pagamento | ENUM | - | dinheiro/credito/debito/pix/transferencia/cheque/boleto |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** tipo, categoria, data_transacao, criado_em

---

### 7. GESTÃO DE AGENDAMENTOS

#### 7.1 `agendamentos` (Agendamentos de Serviços)
**Propósito:** Agendar serviços com clientes.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| cliente_id | INT UNSIGNED | NOT NULL, FK | Cliente |
| servico_id | INT UNSIGNED | FK | Serviço |
| data_agendamento | DATE | NOT NULL | Data agendada |
| hora_inicio | TIME | NOT NULL | Hora de início |
| hora_fim | TIME | - | Hora de término |
| status | ENUM | DEFAULT 'agendado' | agendado/em_progresso/concluido/cancelado/nao_compareceu |
| observacoes | LONGTEXT | - | Notas |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** cliente_id, servico_id, data_agendamento, status
**Foreign Keys:** cliente_id → clientes, servico_id → servicos

---

### 8. GARANTIAS E MANUTENÇÃO

#### 8.1 `garantias` (Garantias de Serviços)
**Propósito:** Controlar garantias de serviços.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| cliente_id | INT UNSIGNED | NOT NULL, FK | Cliente |
| servico_id | INT UNSIGNED | FK | Serviço garantido |
| numero_garantia | VARCHAR(50) | NOT NULL, UNIQUE | Número único |
| data_emissao | DATE | NOT NULL | Data de emissão |
| data_validade | DATE | NOT NULL | Data de vencimento |
| descricao | LONGTEXT | - | Descrição |
| termos_legais | LONGTEXT | - | Termos e condições |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** cliente_id, servico_id, numero_garantia, data_validade
**Foreign Keys:** cliente_id → clientes, servico_id → servicos

---

#### 8.2 `preventivas` (Manutenção Preventiva)
**Propósito:** Planejar manutenção preventiva.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| cliente_id | INT UNSIGNED | NOT NULL, FK | Cliente |
| titulo | VARCHAR(200) | NOT NULL | Título |
| descricao | LONGTEXT | - | Descrição |
| periodicidade | ENUM | DEFAULT 'mensal' | semanal/quinzenal/mensal/trimestral/semestral/anual |
| proxima_data | DATE | - | Próxima data |
| status | ENUM | DEFAULT 'ativa' | ativa/pausada/concluida/cancelada |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** cliente_id, status, proxima_data
**Foreign Keys:** cliente_id → clientes

---

#### 8.3 `preventivas_checklists` (Itens de Manutenção Preventiva)
**Propósito:** Itens de checklist para manutenção.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| preventiva_id | INT UNSIGNED | NOT NULL, FK | Manutenção preventiva |
| item | VARCHAR(200) | NOT NULL | Item a verificar |
| concluido | BOOLEAN | DEFAULT FALSE | Status de conclusão |
| data_conclusao | TIMESTAMP | - | Quando foi concluído |
| observacoes | TEXT | - | Notas |

**Foreign Keys:** preventiva_id → preventivas (CASCADE)

---

### 9. PROGRAMA DE MANUTENÇÃO PREVENTIVA (PMP)

#### 9.1 `pmp_contratos` (Contratos PMP)
**Propósito:** Gerenciar contratos de manutenção preventiva.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| cliente_id | INT UNSIGNED | NOT NULL, FK | Cliente |
| numero_contrato | VARCHAR(50) | NOT NULL, UNIQUE | Número único |
| data_inicio | DATE | NOT NULL | Data de início |
| data_fim | DATE | - | Data de término |
| valor_mensal | DECIMAL(10,2) | NOT NULL | Valor mensal |
| status | ENUM | DEFAULT 'ativo' | ativo/pausado/cancelado/expirado |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** cliente_id, numero_contrato, status
**Foreign Keys:** cliente_id → clientes

---

#### 9.2 `pmp_equipamentos` (Equipamentos no PMP)
**Propósito:** Listar equipamentos sob contrato PMP.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| contrato_id | INT UNSIGNED | NOT NULL, FK | Contrato |
| tipo_equipamento | VARCHAR(100) | NOT NULL | Tipo (Ar, Refrigerador, etc) |
| marca | VARCHAR(100) | - | Marca |
| modelo | VARCHAR(100) | - | Modelo |
| numero_serie | VARCHAR(100) | UNIQUE | Número de série |
| localizacao | VARCHAR(200) | - | Onde está instalado |

**Índices:** contrato_id, numero_serie
**Foreign Keys:** contrato_id → pmp_contratos (CASCADE)

---

#### 9.3 `pmp_checklists` (Checklists PMP Executados)
**Propósito:** Registrar manutenção executada.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| contrato_id | INT UNSIGNED | NOT NULL, FK | Contrato |
| data_execucao | DATE | NOT NULL | Data de execução |
| usuario_id | INT UNSIGNED | FK | Técnico responsável |
| observacoes | LONGTEXT | - | Notas |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** contrato_id, usuario_id, data_execucao
**Foreign Keys:** contrato_id → pmp_contratos (CASCADE), usuario_id → usuarios

---

#### 9.4 `pmp_checklist_itens` (Itens do Checklist PMP)
**Propósito:** Itens verificados em manutenção.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| checklist_id | INT UNSIGNED | NOT NULL, FK | Checklist |
| item | VARCHAR(255) | NOT NULL | Item verificado |
| status | ENUM | DEFAULT 'pendente' | ok/com_problema/pendente/n_aplica |
| observacoes | TEXT | - | Notas |

**Índices:** checklist_id, status
**Foreign Keys:** checklist_id → pmp_checklists (CASCADE)

---

### 10. HISTÓRICO E RELATÓRIOS

#### 10.1 `historico` (Histórico de Atividades)
**Propósito:** Rastrear todas as atividades com clientes.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| cliente_id | INT UNSIGNED | NOT NULL, FK | Cliente |
| usuario_id | INT UNSIGNED | FK | Usuário responsável |
| tipo | ENUM | NOT NULL | visita/chamado/venda/orcamento/manutencao/contato/observacao |
| titulo | VARCHAR(200) | NOT NULL | Título |
| descricao | LONGTEXT | - | Descrição detalhada |
| data_servico | TIMESTAMP | - | Data do serviço |
| status | VARCHAR(50) | - | Status |
| valor | DECIMAL(10,2) | - | Valor envolvido |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** cliente_id, usuario_id, tipo, criado_em
**Foreign Keys:** cliente_id → clientes, usuario_id → usuarios

---

#### 10.2 `relatorios` (Relatórios de Serviços)
**Propósito:** Documentar serviços com fotos.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| usuario_id | INT UNSIGNED | FK | Técnico responsável |
| cliente_id | INT UNSIGNED | NOT NULL, FK | Cliente |
| titulo | VARCHAR(200) | NOT NULL | Título |
| descricao | LONGTEXT | - | Descrição |
| fotos | LONGTEXT | - | JSON com caminhos |
| data_relatorio | TIMESTAMP | DEFAULT NOW | Data do relatório |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** usuario_id, cliente_id, data_relatorio
**Foreign Keys:** usuario_id → usuarios, cliente_id → clientes

---

#### 10.3 `relatorios_fotos` (Fotos dos Relatórios)
**Propósito:** Armazenar referências de fotos.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| relatorio_id | INT UNSIGNED | NOT NULL, FK | Relatório |
| caminho_foto | VARCHAR(500) | NOT NULL | Caminho da imagem |
| descricao | TEXT | - | Descrição da foto |
| ordem | INT UNSIGNED | DEFAULT 0 | Ordem de exibição |

**Índices:** relatorio_id, ordem
**Foreign Keys:** relatorio_id → relatorios (CASCADE)

---

### 11. CONFIGURAÇÕES E SISTEMA

#### 11.1 `configuracoes` (Configurações do Sistema)
**Propósito:** Armazenar parâmetros do sistema.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| chave | VARCHAR(100) | NOT NULL, UNIQUE | Identificador da config |
| valor | LONGTEXT | - | Valor (pode ser JSON) |
| grupo | VARCHAR(100) | - | Categoria |
| descricao | TEXT | - | Descrição |
| atualizado_em | TIMESTAMP | DEFAULT NOW, UPDATED | Última atualização |

**Índices:** chave, grupo

---

#### 11.2 `tabelas_precos` (Tabelas de Preços)
**Propósito:** Definir tabelas de preço por tipo de serviço.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| servico_tipo | VARCHAR(100) | NOT NULL | Tipo de serviço |
| descricao | TEXT | - | Descrição |
| preco_base | DECIMAL(10,2) | NOT NULL | Preço base |
| custo_estimado | DECIMAL(10,2) | - | Custo estimado |
| margem_lucro | DECIMAL(5,2) | - | Percentual de margem |
| ativo | BOOLEAN | DEFAULT TRUE | Status |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** servico_tipo, ativo

---

#### 11.3 `anexos` (Gerenciamento de Arquivos)
**Propósito:** Rastrear arquivos anexados.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| entidade_tipo | VARCHAR(100) | NOT NULL | Tipo (pedido, orcamento, etc) |
| entidade_id | INT UNSIGNED | NOT NULL | ID da entidade |
| tipo_arquivo | VARCHAR(50) | - | pdf, imagem, documento |
| caminho_arquivo | VARCHAR(500) | NOT NULL | Caminho do arquivo |
| nome_original | VARCHAR(255) | - | Nome original |
| tamanho | BIGINT UNSIGNED | - | Tamanho em bytes |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** entidade_tipo, entidade_id, criado_em

---

### 12. AUDITORIA E NOTIFICAÇÕES

#### 12.1 `logs_sistema` (Logs de Auditoria)
**Propósito:** Rastrear todas as alterações no sistema.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| usuario_id | INT UNSIGNED | FK | Usuário responsável |
| acao | VARCHAR(100) | NOT NULL | create/update/delete/view |
| entidade_tipo | VARCHAR(100) | - | Tabela afetada |
| entidade_id | INT UNSIGNED | - | ID do registro |
| dados_anteriores | LONGTEXT | - | JSON anterior |
| dados_novos | LONGTEXT | - | JSON novo |
| ip | VARCHAR(45) | - | IP do cliente |
| user_agent | VARCHAR(500) | - | User Agent |
| criado_em | TIMESTAMP | DEFAULT NOW | Data/hora |

**Índices:** usuario_id, acao, entidade_tipo, entidade_id, criado_em
**Foreign Keys:** usuario_id → usuarios

---

#### 12.2 `notificacoes` (Notificações)
**Propósito:** Sistema de notificações para usuários.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| usuario_id | INT UNSIGNED | NOT NULL, FK | Usuário |
| titulo | VARCHAR(200) | NOT NULL | Título |
| mensagem | LONGTEXT | NOT NULL | Mensagem |
| tipo | ENUM | DEFAULT 'info' | info/sucesso/aviso/erro |
| lida | BOOLEAN | DEFAULT FALSE | Se foi lida |
| data_leitura | TIMESTAMP | - | Quando foi lida |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** usuario_id, lida, criado_em
**Foreign Keys:** usuario_id → usuarios (CASCADE)

---

#### 12.3 `mensagens_whatsapp` (Mensagens WhatsApp)
**Propósito:** Registrar comunicações via WhatsApp.

| Campo | Tipo | Constraints | Descrição |
|-------|------|-------------|-----------|
| id | INT UNSIGNED | PK, AI | Identificador único |
| destinatario | VARCHAR(20) | NOT NULL | Número com código país |
| tipo | ENUM | DEFAULT 'texto' | texto/imagem/documento/link |
| conteudo | LONGTEXT | NOT NULL | Mensagem |
| status | ENUM | DEFAULT 'pendente' | pendente/enviado/entregue/lido/erro |
| enviado_em | TIMESTAMP | - | Data de envio |
| lido_em | TIMESTAMP | - | Data de leitura |
| erro | VARCHAR(500) | - | Mensagem de erro |
| criado_em | TIMESTAMP | DEFAULT NOW | Data de criação |

**Índices:** destinatario, status, criado_em, enviado_em

---

## 📊 Diagrama de Relacionamentos

```
usuarios (centro)
├── pedidos (cliente_id, usuario_id)
├── orcamentos (cliente_id, usuario_id)
├── vendas (cliente_id)
├── agendamentos (usuario_id)
├── historico (cliente_id, usuario_id)
├── relatorios (usuario_id, cliente_id)
├── logs_sistema (usuario_id)
└── notificacoes (usuario_id)

clientes (centro)
├── pedidos (cliente_id)
├── orcamentos (cliente_id)
├── agendamentos (cliente_id)
├── vendas (cliente_id)
├── cobrancas (cliente_id)
├── garantias (cliente_id)
├── preventivas (cliente_id)
├── historico (cliente_id)
├── relatorios (cliente_id)
└── pmp_contratos (cliente_id)

produtos (esquerda)
├── pedidos_produtos (produto_id)
└── categorias_produtos (categoria_id)

servicos (esquerda)
├── pedidos_servicos (servico_id)
├── agendamentos (servico_id)
└── garantias (servico_id)

pmp_contratos (centro)
├── pmp_equipamentos (contrato_id)
├── pmp_checklists (contrato_id)
└── pmp_checklists → pmp_checklist_itens

vendas (direita)
└── cobrancas (venda_id)
```

---

## 🔐 Boas Práticas Implementadas

### 1. **Integridade Referencial**
- Todas as relações possuem `FOREIGN KEY` com `ON DELETE` apropriado
- Cascata em tabelas dependentes (pedidos_produtos, pedidos_servicos)
- Restrição em tabelas críticas (clientes, vendas)
- NULL em relacionamentos opcionais

### 2. **Performance**
- Índices em chaves estrangeiras
- Índices em campos frequentemente pesquisados (email, cpf_cnpj)
- Índices compostos para filtros comuns (status, data)
- Índices em datas para relatórios

### 3. **Segurança**
- Senhas armazenadas com hash (bcrypt)
- Campos únicos em dados sensíveis (email, cpf_cnpj)
- Logs de auditoria completos
- Rastreamento de IP e User Agent

### 4. **Dados**
- Charset UTF-8 para suporte a português
- TIMESTAMP para auditoria automática
- Valores calculados com GENERATED ALWAYS
- Defaults sensatos em campos

### 5. **Auditoria**
- Todos os registros possuem `criado_em`
- Tabelas críticas possuem `atualizado_em`
- Log completo em `logs_sistema`
- Histórico de clientes em `historico`

---

## 📝 Índices de Performance

### Índices Primários (Automáticos)
- Cada tabela possui PRIMARY KEY em `id`

### Índices de Busca
- `usuarios`: email, tipo, ativo
- `clientes`: nome, cpf_cnpj, email, celular
- `produtos`: nome, categoria_id, codigo_barras
- `servicos`: nome, ativo
- `pedidos`: cliente_id, status, data_pedido

### Índices Compostos
- `pedidos`: (status, data_pedido)
- `vendas`: (status_pagamento, data_venda)
- `cobrancas`: (status, data_vencimento)
- `orcamentos`: (cliente_id, status)
- `financeiro`: (tipo, data_transacao)
- `histórico`: (cliente_id, criado_em)
- `agendamentos`: (data_agendamento, status)

---

## 🚀 Sugestões de Uso

### 1. Criar Banco de Dados
```sql
CREATE DATABASE nm_refrigeracao 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE nm_refrigeracao;
SOURCE schema.sql;
```

### 2. Adicionar Dados Iniciais
```sql
-- Inserir usuário admin
INSERT INTO usuarios (nome, email, senha, tipo)
VALUES ('Admin', 'admin@nm.com', HASH_BCRYPT('senha123'), 'admin');
```

### 3. Consultas Comuns
```sql
-- Vendas do mês
SELECT * FROM vendas WHERE MONTH(data_venda) = MONTH(NOW());

-- Clientes em atraso
SELECT * FROM cobrancas WHERE status = 'atrasada';

-- Agendamentos do dia
SELECT * FROM agendamentos WHERE data_agendamento = CURDATE();
```

---

## 📞 Campos de Contato

Tabelas com dados de contato:
- `usuarios.email`, `usuarios.telefone`
- `clientes.email`, `clientes.celular`, `clientes.telefone`
- `mensagens_whatsapp.destinatario`

---

## 🔧 Manutenção

### Backup Regular
```bash
mysqldump -u user -p nm_refrigeracao > backup.sql
```

### Verificação de Integridade
```sql
-- Verificar Foreign Keys
SELECT * FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'nm_refrigeracao';
```

### Limpeza de Dados
```sql
-- Deletar logs antigos
DELETE FROM logs_sistema WHERE criado_em < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Arquivar notificações lidas
DELETE FROM notificacoes WHERE lida = TRUE AND criado_em < DATE_SUB(NOW(), INTERVAL 3 MONTHS);
```

---

## 📌 Versão
- **Schema Version:** 1.0
- **Data de Criação:** 2024
- **Motor:** MySQL 5.7+
- **Charset:** utf8mb4 (suporte completo a português)
- **Total de Tabelas:** 30
- **Total de Índices:** 40+
- **Total de Foreign Keys:** 25+
