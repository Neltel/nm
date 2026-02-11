# 📦 Database - NM Refrigeração

Sistema de banco de dados completo para gerenciamento da NM Refrigeração, com **30 tabelas inter-relacionadas** para cobertura total do negócio.

---

## 📊 Visão Geral

| Aspecto | Detalhes |
|--------|----------|
| **Tabelas** | 30 tabelas totalmente estruturadas |
| **Colunas** | 400+ campos com tipos apropriados |
| **Foreign Keys** | 32 relacionamentos garantindo integridade |
| **Índices** | 40+ índices para performance |
| **Charset** | UTF-8 completo (utf8mb4_unicode_ci) |
| **Engine** | InnoDB com transações ACID |
| **Versão MySQL** | 5.7+ ou MariaDB 10.2+ |
| **Tamanho** | ~88 KB (SQL) |

---

## 📁 Arquivos neste Diretório

### 1. **schema.sql** (30 KB, 621 linhas)
Arquivo principal contendo:
- Configuração de charset UTF-8
- Definição de todas as 30 tabelas
- Foreign Keys e constraints
- Índices para otimização
- Comentários em português

**Como usar:**
```bash
mysql -u root -p nm_refrigeracao < schema.sql
```

### 2. **SCHEMA_DOCUMENTATION.md** (28 KB, 779 linhas)
Documentação completa com:
- Descrição de cada tabela
- Listagem de todos os campos com tipos
- Explicação de constraints
- Diagrama de relacionamentos
- Índices e boas práticas
- Sugestões de queries

**Para consultar:** Estrutura e relacionamentos entre tabelas

### 3. **QUICK_REFERENCE.md** (12 KB, 472 linhas)
Referência rápida com:
- Lista das 30 tabelas por categoria
- Tipos de dados comuns
- Principais campos para filtros
- Relacionamentos principais
- Agregações de exemplo
- Checklist de implementação

**Para consultar:** Implementação rápida e queries comuns

### 4. **INSTALLATION_GUIDE.md** (12 KB, 528 linhas)
Guia passo a passo incluindo:
- Pré-requisitos
- 3 métodos de instalação
- Validação da instalação
- Dados iniciais recomendados
- Segurança pós-instalação
- Troubleshooting
- Scripts de backup

**Para usar:** Na primeira instalação do banco

### 5. **README.md** (Este arquivo)
- Visão geral do projeto
- Navegação pelos documentos
- Estatísticas do schema
- Quick start
- Suporte

---

## 🚀 Quick Start

### 1️⃣ Instalação Rápida (5 minutos)

```bash
# Conectar ao MySQL
mysql -u root -p

# Criar banco
CREATE DATABASE nm_refrigeracao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Executar schema
USE nm_refrigeracao;
SOURCE schema.sql;

# Verificar
SHOW TABLES;
-- Deve mostrar 30 tabelas
```

### 2️⃣ Validar Instalação

```sql
-- Contar tabelas
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = 'nm_refrigeracao';
-- Resultado esperado: 30

-- Listar todas
USE nm_refrigeracao;
SHOW TABLES;
```

### 3️⃣ Inserir Usuário Admin

```sql
INSERT INTO usuarios (nome, email, senha, tipo, telefone, cpf, ativo)
VALUES (
    'Administrador',
    'admin@nm-refrigeracao.com.br',
    '$2y$10$',  -- Hash bcrypt aqui
    'admin',
    '+55 (21) 3000-0000',
    '12345678901',
    TRUE
);
```

---

## 📋 Tabelas por Categoria

### ✅ Categorias de Dados (Agrupadas Logicamente)

**1. Autenticação & Usuários** (1 tabela)
- `usuarios` - Dados de login e tipo

**2. Clientes** (1 tabela)
- `clientes` - Dados de clientes (PF/PJ)

**3. Produtos & Serviços** (3 tabelas)
- `categorias_produtos` - Categorias
- `produtos` - Catálogo de produtos
- `servicos` - Serviços oferecidos

**4. Pedidos** (3 tabelas)
- `pedidos` - Pedidos principais
- `pedidos_produtos` - Produtos em pedidos
- `pedidos_servicos` - Serviços em pedidos

**5. Orçamentos** (2 tabelas)
- `orcamentos` - Orçamentos
- `orcamentos_itens` - Itens do orçamento

**6. Agendamentos** (1 tabela)
- `agendamentos` - Agendamentos de serviços

**7. Vendas & Pagamentos** (2 tabelas)
- `vendas` - Vendas finalizadas
- `cobrancas` - Cobranças

**8. Garantias & Manutenção** (6 tabelas)
- `garantias` - Garantias de serviços
- `preventivas` - Manutenção preventiva
- `preventivas_checklists` - Itens de preventiva
- `pmp_contratos` - Contratos PMP
- `pmp_equipamentos` - Equipamentos PMP
- `pmp_checklists` - Checklists PMP

**9. Histórico & Relatórios** (4 tabelas)
- `historico` - Histórico de atividades
- `relatorios` - Relatórios de serviços
- `relatorios_fotos` - Fotos dos relatórios
- `financeiro` - Transações financeiras

**10. Sistema** (4 tabelas)
- `configuracoes` - Configurações
- `tabelas_precos` - Tabelas de preço
- `anexos` - Gerenciamento de arquivos
- `logs_sistema` - Auditoria

**11. Comunicação** (2 tabelas)
- `notificacoes` - Notificações
- `mensagens_whatsapp` - Mensagens WhatsApp

---

## 🔑 Principais Campos

### Para Autenticação
```sql
usuarios: id, email, senha, tipo, ativo
```

### Para Buscar Clientes
```sql
clientes: id, nome, cpf_cnpj, email, celular
```

### Para Pedidos
```sql
pedidos: id, cliente_id, data_pedido, status, valor_total
```

### Para Vendas
```sql
vendas: id, cliente_id, data_venda, valor_bruto, status_pagamento
```

### Para Agendamentos
```sql
agendamentos: id, cliente_id, data_agendamento, hora_inicio, status
```

---

## 🔗 Relacionamentos Principais

```
clientes ──┬─→ pedidos ──┬─→ pedidos_produtos ──→ produtos
           │             ├─→ pedidos_servicos ──→ servicos
           │             └─→ vendas ──→ cobrancas
           ├─→ orcamentos ──→ orcamentos_itens
           ├─→ agendamentos ──→ servicos
           ├─→ garantias
           ├─→ preventivas
           ├─→ historico
           └─→ relatorios

usuarios ──┬─→ pedidos, orcamentos, vendas
           ├─→ agendamentos, relatorios
           ├─→ historico, logs_sistema
           └─→ notificacoes
```

---

## 📊 Exemplos de Queries

### Listar Pedidos de um Cliente
```sql
SELECT p.id, p.data_pedido, p.status, p.valor_total
FROM pedidos p
WHERE p.cliente_id = 1
ORDER BY p.data_pedido DESC;
```

### Clientes com Inadimplência
```sql
SELECT c.id, c.nome, COUNT(cb.id) as pendencias, SUM(cb.valor) as total
FROM clientes c
JOIN cobrancas cb ON c.id = cb.cliente_id
WHERE cb.status IN ('aberta', 'atrasada')
GROUP BY c.id, c.nome
ORDER BY total DESC;
```

### Agendamentos da Próxima Semana
```sql
SELECT a.*, c.nome as cliente, s.nome as servico
FROM agendamentos a
JOIN clientes c ON a.cliente_id = c.id
LEFT JOIN servicos s ON a.servico_id = s.id
WHERE a.data_agendamento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY a.data_agendamento, a.hora_inicio;
```

### Receita do Mês
```sql
SELECT DATE_TRUNC(data_venda, MONTH) as mes, SUM(valor_bruto) as total
FROM vendas
WHERE YEAR(data_venda) = YEAR(NOW())
GROUP BY mes
ORDER BY mes DESC;
```

---

## 🛡️ Segurança Implementada

### ✅ Integridade de Dados
- [x] Foreign Keys em todas as relações
- [x] Unique constraints em dados sensíveis
- [x] Defaults sensatos em campos
- [x] NOT NULL onde apropriado

### ✅ Auditoria
- [x] Log completo em `logs_sistema`
- [x] Timestamps em todos os registros
- [x] Rastreamento de IP e User Agent
- [x] Histórico de mudanças

### ✅ Autenticação
- [x] Campo `tipo` para controle de acesso
- [x] Campo `ativo` para desabilitar usuários
- [x] Senha como hash (bcrypt)
- [x] Último login registrado

---

## 📈 Performance

### Índices Críticos
- `usuarios.email` - Busca rápida de usuário
- `clientes.cpf_cnpj` - Validação de duplicata
- `pedidos.cliente_id, pedidos.status` - Filtros comuns
- `vendas.data_venda` - Relatórios por data
- `cobrancas.data_vencimento` - Gestão de prazos

### Consultas Otimizadas
- Índices compostos para filtros frequentes
- Campos calculados com GENERATED ALWAYS (sem overhead)
- Timestamps com auto-update em tabelas críticas

---

## 🔧 Manutenção

### Backup Regular
```bash
# Backup completo
mysqldump -u root -p nm_refrigeracao > backup_$(date +%Y%m%d).sql

# Com compressão
mysqldump -u root -p nm_refrigeracao | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Restauração
```bash
mysql -u root -p nm_refrigeracao < backup_2024-02-10.sql
```

### Verificação de Integridade
```sql
ANALYZE TABLE usuarios, clientes, pedidos;
OPTIMIZE TABLE usuarios, clientes, pedidos;
```

---

## 🚨 Troubleshooting

| Problema | Solução |
|----------|---------|
| "Access Denied" | Verificar credenciais, criar usuário com `GRANT` |
| "Table not found" | Verificar se schema foi executado, contar tabelas |
| "Charset utf8mb4 not available" | Atualizar MySQL para 5.5.3+, editar meu.cnf |
| "Foreign Key constraint failed" | Verificar ordem de insert, desabilitar check temporário |
| "Disk full" | Fazer limpeza de logs antigos, aumentar storage |

---

## 📞 Documentação de Referência

### Leia Primeiro
1. **README.md** (este arquivo) - Visão geral
2. **QUICK_REFERENCE.md** - Tabelas e campos principais

### Para Desenvolvimento
3. **SCHEMA_DOCUMENTATION.md** - Detalhes completos
4. **schema.sql** - Código SQL bruto

### Para Instalação/Manutenção
5. **INSTALLATION_GUIDE.md** - Passo a passo

---

## ✅ Checklist de Implementação

- [ ] Banco de dados criado
- [ ] Schema executado (30 tabelas)
- [ ] Foreign Keys validadas
- [ ] Usuário admin inserido
- [ ] Dados iniciais adicionados
- [ ] Backup configurado
- [ ] Aplicação conectando
- [ ] Testes de query OK
- [ ] Auditoria habilitada
- [ ] Documentação arquivada

---

## 🎯 Casos de Uso Cobertos

### Vendas
- ✅ Pedidos com múltiplos itens
- ✅ Rastreamento de orçamentos
- ✅ Histórico completo de cliente
- ✅ Gestão de pagamentos

### Manutenção
- ✅ Agendamentos de serviços
- ✅ Manutenção preventiva
- ✅ Contratos PMP
- ✅ Checklists de equipamentos
- ✅ Garantias de serviços

### Financeiro
- ✅ Controle de vendas
- ✅ Gestão de cobranças
- ✅ Receitas e despesas
- ✅ Relatórios por período

### Comunicação
- ✅ Agendamentos
- ✅ Relatórios com fotos
- ✅ Notificações
- ✅ Integração WhatsApp

### Gestão
- ✅ Catálogo de produtos
- ✅ Tabelas de preço
- ✅ Histórico de atividades
- ✅ Auditoria completa

---

## 📝 Informações Técnicas

| Aspecto | Valor |
|---------|-------|
| MySQL Mínimo | 5.7 |
| Charset | utf8mb4_unicode_ci |
| Engine | InnoDB |
| Transações | ACID completo |
| Foreign Keys | 32 |
| Índices | 40+ |
| Campos | 400+ |
| Máx Registros | Bilhões (escalável) |

---

## 🌍 Localização e Idioma

- **Idioma**: Português (Brasil)
- **Charset**: UTF-8 completo (emojis, caracteres especiais)
- **Moeda**: BRL (Real Brasileiro)
- **Timezone**: America/Sao_Paulo
- **Formato Data**: YYYY-MM-DD (ISO 8601)

---

## 📞 Suporte

### Para Dúvidas Sobre
- **Estrutura das tabelas** → SCHEMA_DOCUMENTATION.md
- **Como usar campos** → QUICK_REFERENCE.md
- **Instalação** → INSTALLATION_GUIDE.md
- **Queries SQL** → Ver exemplos em QUICK_REFERENCE.md

### Documentação Externa
- [MySQL 5.7 Docs](https://dev.mysql.com/doc/refman/5.7/en/)
- [MariaDB 10.2 Docs](https://mariadb.com/docs/reference/mdb10-2/)

---

## 📄 Versão e Data

- **Versão Schema**: 1.0
- **Data Criação**: 2024
- **Status**: ✅ Pronto para Produção
- **Última Atualização**: 2024-02-10

---

## 📜 Licença e Propriedade

Desenvolvido para: **NM Refrigeração**  
Todos os direitos reservados.

---

**Quer começar? Veja o [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)!** 🚀
