# Guia de Instalação - Schema NM Refrigeração

## 📋 Pré-requisitos

- MySQL Server 5.7+ ou MariaDB 10.2+
- Cliente MySQL (mysql CLI ou phpMyAdmin)
- Acesso com privilégios de criação de banco de dados
- ~2 MB de espaço em disco

---

## 🔧 Instalação Passo a Passo

### Método 1: Usando MySQL CLI

#### 1.1 Conectar ao servidor MySQL
```bash
mysql -u root -p
```
*Será solicitada a senha do root*

#### 1.2 Criar o banco de dados
```sql
CREATE DATABASE nm_refrigeracao 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

#### 1.3 Selecionar o banco
```sql
USE nm_refrigeracao;
```

#### 1.4 Executar o arquivo schema
```sql
SOURCE /home/runner/work/novo-site-0.1/novo-site-0.1/public_html/database/schema.sql;
```

#### 1.5 Verificar a instalação
```sql
-- Contar tabelas
SELECT COUNT(*) as total_tabelas FROM information_schema.tables 
WHERE table_schema = 'nm_refrigeracao';

-- Listar todas as tabelas
SHOW TABLES;

-- Verificar uma tabela
DESCRIBE usuarios;
```

---

### Método 2: Usando phpMyAdmin

#### 2.1 Acessar phpMyAdmin
- URL: `http://localhost/phpmyadmin` (ou seu servidor)
- Fazer login com suas credenciais

#### 2.2 Criar novo banco
1. Clicar em "Novo"
2. Nome do banco: `nm_refrigeracao`
3. Collation: `utf8mb4_unicode_ci`
4. Clicar em "Criar"

#### 2.3 Executar SQL
1. Clicar na aba "SQL"
2. Copiar todo o conteúdo de `schema.sql`
3. Colar na área de texto
4. Clicar em "Executar"

#### 2.4 Verificar resultado
- Clicar em "Banco de dados"
- Verificar se aparecem as 30 tabelas

---

### Método 3: Script Automatizado

#### 3.1 Criar arquivo batch (Linux/Mac)
```bash
cat > install_db.sh << 'EOF'
#!/bin/bash

# Solicitar credenciais
read -p "Usuário MySQL (padrão: root): " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Senha MySQL: " DB_PASS
echo

# Definir variáveis
DB_NAME="nm_refrigeracao"
SCRIPT_PATH="/home/runner/work/novo-site-0.1/novo-site-0.1/public_html/database/schema.sql"

# Executar
mysql -u "$DB_USER" -p"$DB_PASS" << MYSQL_EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE $DB_NAME;
SOURCE $SCRIPT_PATH;

SHOW TABLES;
MYSQL_EOF

echo "✅ Banco de dados criado com sucesso!"
EOF

chmod +x install_db.sh
./install_db.sh
```

#### 3.2 Criar arquivo batch (Windows)
```batch
@echo off
setlocal enabledelayedexpansion

echo.
echo === Instalacao do Banco de Dados NM Refrigeracao ===
echo.

set /p DB_USER="Usuario MySQL (padrao: root): "
if "%DB_USER%"=="" set DB_USER=root

set /p DB_PASS="Senha MySQL: "

set DB_NAME=nm_refrigeracao
set SCRIPT_PATH=C:\path\to\schema.sql

mysql -u %DB_USER% -p%DB_PASS% < %SCRIPT_PATH%

if %errorlevel% equ 0 (
    echo.
    echo ✅ Banco de dados criado com sucesso!
) else (
    echo.
    echo ❌ Erro ao criar banco de dados!
)

pause
```

---

## 🗂️ Estrutura de Arquivos Esperados

```
/public_html/database/
├── schema.sql                          # SQL principal (621 linhas)
├── SCHEMA_DOCUMENTATION.md             # Documentação completa
├── QUICK_REFERENCE.md                  # Referência rápida
├── INSTALLATION_GUIDE.md               # Este arquivo
├── /backups/                           # Backups automáticos
│   └── schema_backup_2024-02-10.sql
└── /scripts/                           # Scripts úteis
    ├── seed_initial_data.sql           # (opcional)
    ├── backup.sh                       # (opcional)
    └── restore.sh                      # (opcional)
```

---

## ✅ Validação da Instalação

### 1. Verificar Total de Tabelas
```sql
USE nm_refrigeracao;
SELECT COUNT(*) as total FROM information_schema.tables 
WHERE table_schema = 'nm_refrigeracao';
-- Deve retornar: 30
```

### 2. Listar Todas as Tabelas
```sql
SHOW TABLES;
```

**Resultado esperado:**
```
+--------------------------+
| Tables_in_nm_refrigeracao|
+--------------------------+
| usuarios                 |
| clientes                 |
| categorias_produtos      |
| produtos                 |
| servicos                 |
| pedidos                  |
| pedidos_produtos         |
| pedidos_servicos         |
| orcamentos               |
| orcamentos_itens         |
| agendamentos             |
| vendas                   |
| cobrancas                |
| garantias                |
| preventivas              |
| preventivas_checklists   |
| historico                |
| relatorios               |
| relatorios_fotos         |
| financeiro               |
| pmp_contratos            |
| pmp_equipamentos         |
| pmp_checklists           |
| pmp_checklist_itens      |
| configuracoes            |
| tabelas_precos           |
| anexos                   |
| logs_sistema             |
| notificacoes             |
| mensagens_whatsapp       |
+--------------------------+
30 rows in set
```

### 3. Verificar Estrutura da Tabela Crítica
```sql
DESCRIBE usuarios;
```

**Colunas esperadas:**
- id, nome, email, senha, tipo, telefone, cpf, ativo, criado_em, ultimo_login

### 4. Verificar Índices
```sql
SHOW INDEXES FROM usuarios;
SHOW INDEXES FROM clientes;
SHOW INDEXES FROM pedidos;
```

### 5. Verificar Foreign Keys
```sql
SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'nm_refrigeracao' 
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

**Deve retornar:** 25+ Foreign Keys

---

## 🌱 Dados Iniciais Recomendados

### 1. Inserir Usuário Admin
```sql
INSERT INTO usuarios (nome, email, senha, tipo, telefone, cpf, ativo)
VALUES (
    'Administrador',
    'admin@nm-refrigeracao.com.br',
    '$2y$10$...',  -- Hash bcrypt de 'senha123'
    'admin',
    '+55 (21) 3000-0000',
    '12345678901',
    TRUE
);
```

**Gerar hash bcrypt:**
```php
<?php
echo password_hash('senha123', PASSWORD_BCRYPT);
// Resultado: $2y$10$... (copiar este valor)
?>
```

### 2. Categorias de Produtos
```sql
INSERT INTO categorias_produtos (nome, descricao) VALUES
('Ar Condicionado', 'Equipamentos de ar condicionado'),
('Refrigeração Industrial', 'Sistemas de refrigeração industrial'),
('Peças e Acessórios', 'Peças de reposição e acessórios'),
('Manutenção', 'Produtos de limpeza e manutenção'),
('Ferramentas', 'Ferramentas especializadas');
```

### 3. Serviços Básicos
```sql
INSERT INTO servicos (nome, descricao, preco_base, tempo_estimado, ativo) VALUES
('Instalação AC', 'Instalação de ar condicionado', 500.00, 120, TRUE),
('Manutenção Preventiva', 'Manutenção preventiva mensal', 150.00, 60, TRUE),
('Reparo Emergencial', 'Reparo de emergência 24/7', 300.00, 60, TRUE),
('Limpeza Profissional', 'Limpeza profissional de equipamentos', 100.00, 45, TRUE);
```

### 4. Configurações Iniciais
```sql
INSERT INTO configuracoes (chave, valor, grupo, descricao) VALUES
('empresa_nome', 'NM Refrigeração', 'empresa', 'Nome da empresa'),
('empresa_email', 'contato@nm-refrigeracao.com.br', 'empresa', 'Email para contato'),
('empresa_telefone', '+55 (21) 3000-0000', 'empresa', 'Telefone principal'),
('empresa_cnpj', '12.345.678/0001-00', 'empresa', 'CNPJ da empresa'),
('currency', 'BRL', 'sistema', 'Moeda do sistema'),
('timezone', 'America/Sao_Paulo', 'sistema', 'Timezone do servidor');
```

---

## 🔒 Segurança Pós-Instalação

### 1. Criar Usuário Específico (não usar root)
```sql
-- MySQL 5.7+
CREATE USER 'nm_app'@'localhost' IDENTIFIED BY 'senha_complexa_aqui';

-- Dar apenas permissões necessárias
GRANT SELECT, INSERT, UPDATE, DELETE ON nm_refrigeracao.* TO 'nm_app'@'localhost';
GRANT CREATE, INDEX, ALTER ON nm_refrigeracao.* TO 'nm_app'@'localhost';

FLUSH PRIVILEGES;
```

### 2. Restringir Acesso
```sql
-- Apenas localhost
CREATE USER 'nm_app'@'127.0.0.1' IDENTIFIED BY 'senha_complexa';

-- Específico para aplicação
CREATE USER 'nm_app'@'192.168.1.100' IDENTIFIED BY 'senha_complexa';
```

### 3. Remover Usuário root (Recomendado em Produção)
```sql
-- Desabilitar acesso sem senha
DELETE FROM mysql.user WHERE user='root' AND authentication_string='';

-- Remover acesso remoto
DELETE FROM mysql.user WHERE user='root' AND host='%';

FLUSH PRIVILEGES;
```

### 4. Configurar Backup Automático
```bash
# Criar diretório de backups
mkdir -p /var/backups/mysql

# Script de backup (backup.sh)
#!/bin/bash
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
mysqldump -u nm_app -p'senha' nm_refrigeracao > /var/backups/mysql/nm_refrigeracao_$TIMESTAMP.sql
gzip /var/backups/mysql/nm_refrigeracao_$TIMESTAMP.sql

# Agendar com cron
0 2 * * * /path/to/backup.sh  # Diariamente às 2:00 AM
```

---

## 🐛 Troubleshooting

### Erro: "Access Denied"
```bash
# Verificar credenciais
mysql -u root -p -e "SELECT VERSION();"

# Se esqueceu a senha (Linux)
sudo mysqld_safe --skip-grant-tables &
mysql -u root
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'nova_senha';
```

### Erro: "Charset utf8mb4 not available"
```sql
-- Verificar encoding
SHOW VARIABLES LIKE 'character%';

-- Ativar utf8mb4 (meu.cnf)
[mysqld]
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

### Erro: "Syntax error"
```sql
-- Verificar version
SELECT VERSION();

-- Checar se usar MySQL 5.7+
-- Caso contrário, ajustar sintaxe de GENERATED ALWAYS AS
```

### Erro: "Foreign Key Constraint Failed"
```sql
-- Desabilitar check temporário
SET FOREIGN_KEY_CHECKS=0;
-- ... executar SQL ...
SET FOREIGN_KEY_CHECKS=1;
```

### Banco muito grande
```sql
-- Verificar tamanho
SELECT 
    table_schema,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'nm_refrigeracao'
GROUP BY table_schema;
```

---

## 📊 Verificação de Performance

### 1. Analisar Tabelas
```sql
USE nm_refrigeracao;
ANALYZE TABLE usuarios, clientes, pedidos, vendas;
```

### 2. Verificar Índices
```sql
SELECT 
    table_name,
    count(*) as index_count
FROM information_schema.statistics
WHERE table_schema = 'nm_refrigeracao'
GROUP BY table_name
ORDER BY index_count DESC;
```

### 3. Otimizar Espaço
```sql
OPTIMIZE TABLE usuarios, clientes, pedidos, vendas, cobrancas;
```

### 4. Monitorar Logs
```bash
# Ver log de erro
tail -f /var/log/mysql/error.log

# Ver slow queries
tail -f /var/log/mysql/slow-query.log
```

---

## 🔄 Restauração de Backup

### Restaurar de Arquivo
```bash
# Restaurar completo
mysql -u root -p nm_refrigeracao < backup_2024-02-10.sql

# Restaurar específica
mysql -u nm_app -p nm_refrigeracao < schema.sql
```

### Restaurar Tabela Específica
```bash
# Extrair de backup e restaurar
grep -A 1000 "^-- CREATE TABLE.*clientes" backup.sql | \
grep -B 1000 "^-- CREATE TABLE.*" | \
mysql -u root -p nm_refrigeracao
```

---

## 🚀 Próximos Passos

1. **Conectar Aplicação**
   - Configurar credenciais no arquivo .env
   - Testar conexão
   - Fazer logs de erro

2. **Adicionar Dados**
   - Inserir empresas/clientes iniciais
   - Configurar serviços oferecidos
   - Adicionar produtos ao catálogo

3. **Configurar Backups**
   - Backup diário automático
   - Replicação (se multi-server)
   - Teste de restore

4. **Monitoramento**
   - Alertas de disk full
   - Monitoramento de performance
   - Logs de auditoria

5. **Documentação**
   - Documentar senhas em local seguro
   - Criar runbooks de operação
   - Treinar equipe

---

## 📞 Suporte e Referências

### Documentação Interna
- `SCHEMA_DOCUMENTATION.md` - Documentação completa de todas as tabelas
- `QUICK_REFERENCE.md` - Referência rápida de campos e queries

### Documentação Official
- MySQL: https://dev.mysql.com/doc/
- MariaDB: https://mariadb.com/docs/

### Ferramentas Úteis
- **MySQL Workbench**: Designer visual
- **phpMyAdmin**: Gerenciamento web
- **DBeaver**: Cliente universal
- **Sequel Pro** (Mac): Cliente nativo

---

## ✨ Checklist Final

- [ ] Banco criado com charset utf8mb4
- [ ] 30 tabelas criadas com sucesso
- [ ] Foreign Keys validadas
- [ ] Índices verificados
- [ ] Usuário app criado (não root)
- [ ] Dados iniciais inseridos
- [ ] Backup configurado
- [ ] Aplicação conectando
- [ ] Testes funcionais OK
- [ ] Documentação arquivada

---

**Data de Criação:** 2024
**Versão:** 1.0
**Status:** Pronto para Produção ✅
