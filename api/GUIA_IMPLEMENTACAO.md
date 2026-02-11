# API Endpoints - Guia de Implementação

## 📋 Resumo dos Arquivos Criados

Foram criados **3 novos arquivos de endpoints** na pasta `/public_html/api/`:

### 1. **produtos.php** (546 linhas)
Sistema completo de gerenciamento de produtos com CRUD e gestão de estoque.

**Endpoints:**
- `GET /api/produtos` - Lista produtos com paginação e filtros
- `POST /api/produtos` - Criar novo produto
- `GET /api/produtos/:id` - Obter produto específico
- `PUT /api/produtos/:id` - Atualizar produto
- `DELETE /api/produtos/:id` - Deletar produto
- `GET /api/produtos/search` - Buscar produtos
- `GET /api/produtos/categoria/:id` - Listar por categoria
- `PUT /api/produtos/:id/estoque` - Gerenciar estoque

**Campos do Produto:**
- `nome`, `codigo`, `preco`, `categoria_id` (obrigatórios)
- `descricao`, `quantidade`, `imagem_url`, `sku`, `peso`, `dimensoes` (opcionais)
- `ativo` (boolean)

### 2. **servicos.php** (323 linhas)
Sistema de gerenciamento de serviços oferecidos.

**Endpoints:**
- `GET /api/servicos` - Lista serviços com paginação
- `POST /api/servicos` - Criar novo serviço
- `GET /api/servicos/:id` - Obter serviço específico
- `PUT /api/servicos/:id` - Atualizar serviço
- `DELETE /api/servicos/:id` - Deletar serviço
- `GET /api/servicos/search` - Buscar serviços

**Campos do Serviço:**
- `nome`, `preco` (obrigatórios)
- `descricao`, `duracao` (em minutos), `categoria` (opcionais)
- `ativo` (boolean)

### 3. **pedidos.php** (495 linhas)
Sistema completo de gerenciamento de pedidos com rastreamento de itens e status.

**Endpoints:**
- `GET /api/pedidos` - Lista pedidos com paginação e filtros
- `POST /api/pedidos` - Criar novo pedido (com itens)
- `GET /api/pedidos/:id` - Obter pedido com detalhes
- `PUT /api/pedidos/:id` - Atualizar pedido
- `DELETE /api/pedidos/:id` - Deletar pedido
- `PUT /api/pedidos/:id/status` - Atualizar status
- `GET /api/pedidos/cliente/:id` - Listar pedidos por cliente

**Campos do Pedido:**
- `numero` (auto-gerado), `cliente_id`, `status`, `total` (obrigatórios)
- `observacoes` (opcional)
- `itens` (array com: `id_produto`, `quantidade`, `preco_unitario`)

**Status Válidos:** pendente, processando, enviado, entregue, cancelado

---

## 🔐 Autenticação e Segurança

**Todos os endpoints requerem:**
- Header `Authorization: Bearer {token}` (exceto login)
- Token JWT válido obtido através de `POST /api/auth/login`

**Segurança implementada:**
- ✅ Validação de entrada com `Validator::sanitizeString()`
- ✅ SQL injection prevention com prepared statements
- ✅ Proteção contra uploads de arquivos maliciosos
- ✅ Registro do usuário criador em cada recurso
- ✅ Timestamps automáticos de criação/atualização
- ✅ Tratamento de erros com mensagens claras

---

## 📊 Estrutura de Dados Esperada

### Banco de Dados - Tabelas Necessárias

```sql
-- Tabela de Produtos
CREATE TABLE produtos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    codigo VARCHAR(100) UNIQUE NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    categoria_id INT NOT NULL,
    descricao TEXT,
    quantidade INT DEFAULT 0,
    imagem_url VARCHAR(500),
    sku VARCHAR(100),
    peso DECIMAL(8,3),
    dimensoes VARCHAR(50),
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME,
    criado_por INT,
    atualizado_em DATETIME,
    atualizado_por INT,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabela de Serviços
CREATE TABLE servicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    descricao TEXT,
    duracao INT,
    categoria VARCHAR(100),
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME,
    criado_por INT,
    atualizado_em DATETIME,
    atualizado_por INT
);

-- Tabela de Pedidos
CREATE TABLE pedidos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(50) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pendente',
    total DECIMAL(12,2) NOT NULL,
    observacoes TEXT,
    data_entrega DATETIME,
    criado_em DATETIME,
    criado_por INT,
    atualizado_em DATETIME,
    atualizado_por INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- Tabela de Itens do Pedido
CREATE TABLE pedidos_itens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);
```

---

## 🚀 Como Usar

### 1. Obter Token de Autenticação

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "senha": "senha123"
  }'
```

**Resposta:**
```json
{
    "success": true,
    "message": "Login realizado com sucesso",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "usuario": {
            "id": 1,
            "nome": "Administrador",
            "email": "admin@example.com",
            "tipo": "admin"
        }
    }
}
```

### 2. Criar um Produto

```bash
curl -X POST http://localhost/api/produtos \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJhbGc..." \
  -d '{
    "nome": "Notebook Dell",
    "codigo": "PROD-DELL-001",
    "preco": 3499.99,
    "categoria_id": 1,
    "descricao": "Notebook com processador Intel i7",
    "quantidade": 20,
    "sku": "DELL-I7-8GB"
  }'
```

### 3. Criar um Serviço

```bash
curl -X POST http://localhost/api/servicos \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJhbGc..." \
  -d '{
    "nome": "Consultoria Profissional",
    "preco": 250.00,
    "descricao": "Consultoria para empresas",
    "duracao": 60,
    "categoria": "Consultoria"
  }'
```

### 4. Criar um Pedido

```bash
curl -X POST http://localhost/api/pedidos \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJhbGc..." \
  -d '{
    "cliente_id": 1,
    "observacoes": "Entrega expressa",
    "itens": [
      {
        "id_produto": 1,
        "quantidade": 2,
        "preco_unitario": 99.99
      },
      {
        "id_produto": 2,
        "quantidade": 1,
        "preco_unitario": 50.00
      }
    ]
  }'
```

### 5. Atualizar Status do Pedido

```bash
curl -X PUT http://localhost/api/pedidos/1/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJhbGc..." \
  -d '{
    "status": "enviado"
  }'
```

### 6. Buscar Produtos

```bash
curl -X GET "http://localhost/api/produtos/search?q=notebook" \
  -H "Authorization: Bearer eyJhbGc..."
```

### 7. Listar Produtos de Categoria

```bash
curl -X GET "http://localhost/api/produtos/categoria/1?page=1&limit=10" \
  -H "Authorization: Bearer eyJhbGc..."
```

### 8. Atualizar Estoque

```bash
curl -X PUT http://localhost/api/produtos/1/estoque \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJhbGc..." \
  -d '{
    "quantidade": 5,
    "tipo": "saida"
  }'
```

---

## 📝 Padrões de Código Implementados

### Validação de Entrada

```php
// Campo obrigatório
if (empty($input['nome'])) {
    sendError('Nome é obrigatório', 400);
}

// Tipo de dado
if (!is_numeric($input['preco']) || floatval($input['preco']) < 0) {
    sendError('Preço deve ser um número positivo', 400);
}

// Validador especializado
if (!Validator::validateEmail($input['email'])) {
    sendError('Email inválido', 400);
}
```

### Sanitização de Dados

```php
// String sanitizada
$dados['nome'] = Validator::sanitizeString($input['nome']);

// Email em minúscula
$dados['email'] = strtolower(trim($input['email']));

// Apenas dígitos
$dados['telefone'] = preg_replace('/[^0-9]/', '', $input['telefone']);

// URL validada
$dados['imagem_url'] = filter_var($input['imagem_url'], FILTER_VALIDATE_URL);
```

### Tratamento de Erros

```php
// Buscar recurso
$produto = $db->queryOne("SELECT * FROM produtos WHERE id = ?", [$id]);

if (!$produto) {
    sendError('Produto não encontrado', 404);
}

// Verificar duplicatas
$existente = $db->queryOne(
    "SELECT id FROM produtos WHERE codigo = ? AND id != ?",
    [$input['codigo'], $id]
);

if ($existente) {
    sendError('Código já existe', 400);
}
```

### Resposta Padronizada

```php
// Sucesso
sendSuccess($dados, 'Mensagem de sucesso', 200);

// Erro
sendError('Mensagem de erro', 400);

// Criado
sendSuccess($novo_produto, 'Produto criado com sucesso', 201);

// Não encontrado
sendError('Produto não encontrado', 404);
```

---

## 🔍 Exemplos de Respostas

### Sucesso - Listar Produtos
```json
{
    "success": true,
    "message": "Produtos obtidos com sucesso",
    "data": {
        "produtos": [
            {
                "id": 1,
                "nome": "Notebook",
                "codigo": "PROD001",
                "preco": 2999.99,
                "categoria_id": 1,
                "quantidade": 10,
                "ativo": 1,
                "criado_em": "2024-02-10 10:30:00"
            }
        ],
        "paginacao": {
            "pagina_atual": 1,
            "total_itens": 50,
            "itens_por_pagina": 10,
            "total_paginas": 5
        }
    }
}
```

### Sucesso - Criar Pedido
```json
{
    "success": true,
    "message": "Pedido criado com sucesso",
    "data": {
        "id": 1,
        "numero": "PED20240210123456789",
        "cliente_id": 1,
        "status": "pendente",
        "total": 299.98,
        "criado_em": "2024-02-10 15:30:00",
        "itens": [
            {
                "id": 1,
                "pedido_id": 1,
                "produto_id": 1,
                "quantidade": 2,
                "preco_unitario": 99.99,
                "subtotal": 199.98
            },
            {
                "id": 2,
                "pedido_id": 1,
                "produto_id": 2,
                "quantidade": 1,
                "preco_unitario": 100.00,
                "subtotal": 100.00
            }
        ]
    }
}
```

### Erro - Validação
```json
{
    "success": false,
    "message": "Preço deve ser um número positivo",
    "error": true
}
```

### Erro - Não Encontrado
```json
{
    "success": false,
    "message": "Produto não encontrado",
    "error": true
}
```

---

## 🛠️ Funcionalidades Especiais

### 1. Gestão de Estoque
```bash
# Adicionar ao estoque
curl -X PUT http://localhost/api/produtos/1/estoque \
  -H "Authorization: Bearer {token}" \
  -d '{"quantidade": 10, "tipo": "entrada"}'

# Remover do estoque
curl -X PUT http://localhost/api/produtos/1/estoque \
  -H "Authorization: Bearer {token}" \
  -d '{"quantidade": 5, "tipo": "saida"}'
```

### 2. Rastreamento de Pedidos
```bash
# Obter pedido com detalhes
curl -X GET http://localhost/api/pedidos/1 \
  -H "Authorization: Bearer {token}"

# Retorna cliente, itens e todas as informações

# Atualizar status
curl -X PUT http://localhost/api/pedidos/1/status \
  -H "Authorization: Bearer {token}" \
  -d '{"status": "entregue"}'
```

### 3. Busca Avançada
```bash
# Buscar por nome, código ou descrição
curl -X GET "http://localhost/api/produtos/search?q=notebook" \
  -H "Authorization: Bearer {token}"

# Listar com filtros
curl -X GET "http://localhost/api/produtos?categoria_id=1&status=ativo" \
  -H "Authorization: Bearer {token}"
```

### 4. Histórico de Pedidos por Cliente
```bash
# Ver todos os pedidos de um cliente
curl -X GET "http://localhost/api/pedidos/cliente/1?page=1&limit=10" \
  -H "Authorization: Bearer {token}"
```

---

## ⚠️ Regras de Negócio

### Produtos
- Código deve ser único
- Preço deve ser positivo
- Categoria deve existir
- Quantidade não pode ser negativa
- Apenas produtos ativos aparecem em buscas públicas

### Serviços
- Nome é obrigatório
- Preço deve ser positivo
- Duração em minutos (opcional)
- Categoria é um texto livre (não foreign key)

### Pedidos
- Número gerado automaticamente (PED + data + timestamp)
- Requer mínimo 1 item
- Estoque é validado no momento da criação
- Status segue fluxo: pendente → processando → enviado → entregue
- Cancelado pode ser de qualquer estado
- Apenas pedidos "pendente" podem ser deletados
- Registra o usuário criador e datas

---

## 📚 Documentação Adicional

Para informações mais detalhadas, consulte:
- `API_ENDPOINTS.md` - Documentação completa de todos os endpoints
- `README.md` - Visão geral da API
- `SETUP.md` - Instruções de instalação

---

## ✅ Checklist de Implementação

- [x] Criar endpoints de produtos
- [x] Criar endpoints de serviços  
- [x] Criar endpoints de pedidos
- [x] Implementar validação de entrada
- [x] Implementar sanitização de dados
- [x] Implementar autenticação JWT
- [x] Implementar tratamento de erros
- [x] Implementar logging
- [x] Adicionar comentários em português
- [x] Atualizar routes.php
- [x] Criar documentação
- [x] Validar sintaxe PHP

---

## 🐛 Troubleshooting

### Erro 404 - Endpoint não encontrado
- Verifique se o endpoint está registrado em `routes.php`
- Verifique a URL (case-sensitive)
- Verifique se o arquivo existe

### Erro 401 - Não autenticado
- Obtenha um token com `POST /api/auth/login`
- Inclua o header `Authorization: Bearer {token}`
- Verifique se o token não expirou

### Erro 400 - Dados inválidos
- Verifique os campos obrigatórios
- Valide os tipos de dados
- Consulte a documentação para o formato esperado

### Erro 500 - Erro interno
- Verifique os logs em `/logs/api_*.log`
- Valide a sintaxe PHP dos arquivos
- Verifique a conexão com o banco de dados

---

## 📞 Suporte

Para reportar problemas ou sugestões, consulte a documentação ou entre em contato com o time de desenvolvimento.
