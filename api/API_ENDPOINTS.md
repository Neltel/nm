# Documentação de Endpoints da API

## Visão Geral

A API foi desenvolvida seguindo padrões RESTful com autenticação via JWT. Todos os endpoints requerem autenticação (exceto login) e retornam respostas em JSON.

**Base URL:** `/api/`

### Autenticação

Use o endpoint `POST /api/auth/login` para obter um token JWT. Inclua o token no header `Authorization: Bearer {token}` para todas as requisições autenticadas.

---

## 1. Autenticação (auth.php)

### POST /api/auth/login
Realiza login do usuário com email e senha.

**Entrada:**
```json
{
    "email": "usuario@email.com",
    "senha": "senha123"
}
```

**Resposta (200):**
```json
{
    "success": true,
    "message": "Login realizado com sucesso",
    "data": {
        "token": "eyJhbGc...",
        "usuario": {
            "id": 1,
            "nome": "João Silva",
            "email": "joao@email.com",
            "tipo": "admin"
        }
    }
}
```

### POST /api/auth/logout
Realiza logout do usuário.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Logout realizado com sucesso",
    "data": []
}
```

### POST /api/auth/refresh
Renova o token JWT.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Token renovado com sucesso",
    "data": {
        "token": "eyJhbGc...",
        "usuario": {
            "id": 1,
            "nome": "João Silva",
            "email": "joao@email.com",
            "tipo": "admin"
        }
    }
}
```

### GET /api/auth/me
Obtém dados do usuário autenticado.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Dados do usuário obtidos com sucesso",
    "data": {
        "id": 1,
        "nome": "João Silva",
        "email": "joao@email.com",
        "tipo": "admin",
        "criado_em": "2024-01-15 10:30:00",
        "ultimo_login": "2024-02-10 14:22:00"
    }
}
```

### POST /api/auth/change-password
Altera a senha do usuário autenticado.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada:**
```json
{
    "senha_atual": "senha123",
    "senha_nova": "novaSenha456",
    "confirmar_senha": "novaSenha456"
}
```

**Resposta (200):**
```json
{
    "success": true,
    "message": "Senha alterada com sucesso",
    "data": []
}
```

---

## 2. Clientes (clientes.php)

### GET /api/clientes
Lista todos os clientes com paginação.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Query Parameters:**
- `page` (int): Número da página (padrão: 1)
- `limit` (int): Itens por página, máximo 100 (padrão: 10)
- `order` (string): Campo e direção: "nome ASC", "email ASC", "criado_em DESC" (padrão: "nome ASC")

**Exemplo:** `GET /api/clientes?page=1&limit=10&order=nome%20ASC`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Clientes obtidos com sucesso",
    "data": {
        "clientes": [
            {
                "id": 1,
                "nome": "João Silva",
                "email": "joao@email.com",
                "cpf": "12345678900",
                "telefone": "11999999999",
                "endereco": "Rua A, 123",
                "cidade": "São Paulo",
                "estado": "SP",
                "cep": "01000000",
                "observacoes": "Cliente VIP",
                "criado_em": "2024-01-15 10:30:00"
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

### GET /api/clientes/search
Busca clientes por nome, email ou CPF.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Query Parameters:**
- `q` (string, obrigatório): Termo de busca (mínimo 2 caracteres)
- `page` (int): Número da página (padrão: 1)
- `limit` (int): Itens por página (padrão: 10)

**Exemplo:** `GET /api/clientes/search?q=joao&page=1&limit=10`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Busca realizada com sucesso",
    "data": {
        "clientes": [...],
        "paginacao": {...}
    }
}
```

### GET /api/clientes/:id
Obtém dados de um cliente específico.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Cliente obtido com sucesso",
    "data": {
        "id": 1,
        "nome": "João Silva",
        "email": "joao@email.com",
        "cpf": "12345678900",
        "telefone": "11999999999",
        "endereco": "Rua A, 123",
        "cidade": "São Paulo",
        "estado": "SP",
        "cep": "01000000",
        "observacoes": "Cliente VIP",
        "criado_em": "2024-01-15 10:30:00",
        "criado_por": 1
    }
}
```

### POST /api/clientes
Cria novo cliente.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada (Campos Obrigatórios):**
- `nome` (string)
- `email` (string, email válido)

**Entrada (Campos Opcionais):**
- `cpf` (string)
- `telefone` (string)
- `endereco` (string)
- `cidade` (string)
- `estado` (string, 2 caracteres)
- `cep` (string)
- `observacoes` (string)

**Exemplo:**
```json
{
    "nome": "Maria Santos",
    "email": "maria@email.com",
    "cpf": "98765432100",
    "telefone": "11998888888",
    "endereco": "Avenida B, 456",
    "cidade": "Rio de Janeiro",
    "estado": "RJ",
    "cep": "20000000"
}
```

**Resposta (201):**
```json
{
    "success": true,
    "message": "Cliente criado com sucesso",
    "data": {
        "id": 2,
        "nome": "Maria Santos",
        "email": "maria@email.com",
        "cpf": "98765432100",
        "telefone": "11998888888",
        "endereco": "Avenida B, 456",
        "cidade": "Rio de Janeiro",
        "estado": "RJ",
        "cep": "20000000",
        "observacoes": null,
        "criado_em": "2024-02-10 15:30:00",
        "criado_por": 1
    }
}
```

### PUT /api/clientes/:id
Atualiza dados de um cliente existente.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada:** Qualquer combinação dos campos acima (todos opcionais)

**Resposta (200):** Retorna cliente atualizado

### DELETE /api/clientes/:id
Deleta um cliente existente.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Cliente deletado com sucesso",
    "data": []
}
```

---

## 3. Produtos (produtos.php)

### GET /api/produtos
Lista todos os produtos com paginação e filtros.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Query Parameters:**
- `page` (int): Número da página (padrão: 1)
- `limit` (int): Itens por página (padrão: 10)
- `order` (string): "nome ASC", "preco ASC", "criado_em DESC" (padrão: "nome ASC")
- `categoria_id` (int): Filtrar por categoria
- `status` (string): "ativo" ou "inativo"

**Exemplo:** `GET /api/produtos?page=1&limit=10&categoria_id=1&status=ativo`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Produtos obtidos com sucesso",
    "data": {
        "produtos": [
            {
                "id": 1,
                "nome": "Produto A",
                "codigo": "PROD001",
                "preco": 99.99,
                "categoria_id": 1,
                "descricao": "Descrição do produto",
                "quantidade": 50,
                "imagem_url": "https://...",
                "sku": "SKU001",
                "peso": 2.5,
                "dimensoes": "10x10x10",
                "ativo": 1,
                "criado_em": "2024-01-15 10:30:00"
            }
        ],
        "paginacao": {
            "pagina_atual": 1,
            "total_itens": 100,
            "itens_por_pagina": 10,
            "total_paginas": 10
        }
    }
}
```

### GET /api/produtos/search
Busca produtos por nome, código ou descrição.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Query Parameters:**
- `q` (string, obrigatório): Termo de busca (mínimo 2 caracteres)
- `page` (int): Número da página
- `limit` (int): Itens por página

**Exemplo:** `GET /api/produtos/search?q=notebook&page=1&limit=10`

**Resposta (200):** Lista de produtos encontrados

### GET /api/produtos/categoria/:id
Obtém produtos de uma categoria específica.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Query Parameters:**
- `page` (int): Número da página
- `limit` (int): Itens por página

**Exemplo:** `GET /api/produtos/categoria/1?page=1&limit=10`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Produtos da categoria obtidos com sucesso",
    "data": {
        "produtos": [...],
        "categoria_id": 1,
        "paginacao": {...}
    }
}
```

### POST /api/produtos
Cria novo produto.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada (Campos Obrigatórios):**
- `nome` (string)
- `codigo` (string, único)
- `preco` (float)
- `categoria_id` (int)

**Entrada (Campos Opcionais):**
- `descricao` (string)
- `quantidade` (int)
- `imagem_url` (string, URL válida)
- `sku` (string)
- `peso` (float)
- `dimensoes` (string)

**Exemplo:**
```json
{
    "nome": "Notebook Dell",
    "codigo": "PROD-DELL-001",
    "preco": 3499.99,
    "categoria_id": 1,
    "descricao": "Notebook com processador Intel i7",
    "quantidade": 20,
    "imagem_url": "https://example.com/imagem.jpg",
    "sku": "DELL-I7-8GB",
    "peso": 1.8,
    "dimensoes": "34x24x2"
}
```

**Resposta (201):** Produto criado

### GET /api/produtos/:id
Obtém dados de um produto específico.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Resposta (200):** Retorna dados do produto com nome da categoria

### PUT /api/produtos/:id
Atualiza dados de um produto existente.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada:** Qualquer combinação dos campos acima (todos opcionais)

**Resposta (200):** Produto atualizado

### DELETE /api/produtos/:id
Deleta um produto existente.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Produto deletado com sucesso",
    "data": []
}
```

### PUT /api/produtos/:id/estoque
Atualiza estoque do produto.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada:**
```json
{
    "quantidade": 10,
    "tipo": "entrada"
}
```

**Parâmetros:**
- `quantidade` (int, obrigatório): Quantidade a adicionar/remover
- `tipo` (string): "entrada" (adiciona) ou "saida" (remove)

**Resposta (200):** Produto com estoque atualizado

---

## 4. Serviços (servicos.php)

### GET /api/servicos
Lista todos os serviços com paginação.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Query Parameters:**
- `page` (int): Número da página (padrão: 1)
- `limit` (int): Itens por página (padrão: 10)
- `order` (string): "nome ASC", "preco ASC", "criado_em DESC"
- `status` (string): "ativo" ou "inativo"

**Resposta (200):**
```json
{
    "success": true,
    "message": "Serviços obtidos com sucesso",
    "data": {
        "servicos": [
            {
                "id": 1,
                "nome": "Consultoria",
                "preco": 150.00,
                "descricao": "Consultoria profissional",
                "duracao": 60,
                "categoria": "Consultoria",
                "ativo": 1,
                "criado_em": "2024-01-15 10:30:00"
            }
        ],
        "paginacao": {...}
    }
}
```

### GET /api/servicos/search
Busca serviços por nome ou descrição.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Query Parameters:**
- `q` (string, obrigatório): Termo de busca (mínimo 2 caracteres)
- `page` (int): Número da página
- `limit` (int): Itens por página

**Resposta (200):** Lista de serviços encontrados

### POST /api/servicos
Cria novo serviço.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada (Campos Obrigatórios):**
- `nome` (string)
- `preco` (float)

**Entrada (Campos Opcionais):**
- `descricao` (string)
- `duracao` (int, em minutos)
- `categoria` (string)

**Exemplo:**
```json
{
    "nome": "Limpeza Residencial",
    "preco": 200.00,
    "descricao": "Limpeza completa da residência",
    "duracao": 120,
    "categoria": "Limpeza"
}
```

**Resposta (201):** Serviço criado

### GET /api/servicos/:id
Obtém dados de um serviço específico.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Resposta (200):** Retorna dados do serviço

### PUT /api/servicos/:id
Atualiza dados de um serviço existente.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada:** Qualquer combinação dos campos acima (todos opcionais)

**Resposta (200):** Serviço atualizado

### DELETE /api/servicos/:id
Deleta um serviço existente.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Serviço deletado com sucesso",
    "data": []
}
```

---

## 5. Pedidos (pedidos.php)

### GET /api/pedidos
Lista todos os pedidos com paginação.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Query Parameters:**
- `page` (int): Número da página (padrão: 1)
- `limit` (int): Itens por página (padrão: 10)
- `order` (string): "numero ASC", "criado_em DESC", "status ASC", "total DESC"
- `status` (string): "pendente", "processando", "enviado", "entregue", "cancelado"

**Exemplo:** `GET /api/pedidos?page=1&limit=10&status=pendente`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Pedidos obtidos com sucesso",
    "data": {
        "pedidos": [
            {
                "id": 1,
                "numero": "PED20240210123456",
                "cliente_id": 1,
                "status": "pendente",
                "total": 299.99,
                "observacoes": "Entrega expressa",
                "criado_em": "2024-02-10 10:30:00",
                "data_entrega": null
            }
        ],
        "paginacao": {
            "pagina_atual": 1,
            "total_itens": 25,
            "itens_por_pagina": 10,
            "total_paginas": 3
        }
    }
}
```

### GET /api/pedidos/cliente/:id
Obtém pedidos de um cliente específico.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Query Parameters:**
- `page` (int): Número da página (padrão: 1)
- `limit` (int): Itens por página (padrão: 10)

**Exemplo:** `GET /api/pedidos/cliente/1?page=1&limit=10`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Pedidos do cliente obtidos com sucesso",
    "data": {
        "pedidos": [...],
        "cliente_id": 1,
        "paginacao": {...}
    }
}
```

### POST /api/pedidos
Cria novo pedido.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada (Campos Obrigatórios):**
- `cliente_id` (int)
- `itens` (array): Array de itens do pedido

**Entrada (Campos Opcionais):**
- `observacoes` (string)

**Estrutura de Item:**
```json
{
    "id_produto": 1,
    "quantidade": 2,
    "preco_unitario": 99.99
}
```

**Exemplo Completo:**
```json
{
    "cliente_id": 1,
    "observacoes": "Entrega em horário comercial",
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
}
```

**Resposta (201):**
```json
{
    "success": true,
    "message": "Pedido criado com sucesso",
    "data": {
        "id": 1,
        "numero": "PED20240210123456",
        "cliente_id": 1,
        "status": "pendente",
        "total": 249.97,
        "observacoes": "Entrega em horário comercial",
        "criado_em": "2024-02-10 15:30:00",
        "criado_por": 1,
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
                "preco_unitario": 50.00,
                "subtotal": 50.00
            }
        ]
    }
}
```

### GET /api/pedidos/:id
Obtém dados de um pedido específico com seus itens.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Resposta (200):**
```json
{
    "success": true,
    "message": "Pedido obtido com sucesso",
    "data": {
        "id": 1,
        "numero": "PED20240210123456",
        "cliente_id": 1,
        "cliente_nome": "João Silva",
        "cliente_email": "joao@email.com",
        "status": "pendente",
        "total": 249.97,
        "observacoes": "Entrega em horário comercial",
        "criado_em": "2024-02-10 15:30:00",
        "data_entrega": null,
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
                "preco_unitario": 50.00,
                "subtotal": 50.00
            }
        ]
    }
}
```

### PUT /api/pedidos/:id
Atualiza dados de um pedido existente.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada (Opcionais):**
- `cliente_id` (int)
- `status` (string)
- `total` (float)
- `observacoes` (string)

**Nota:** Recomenda-se não alterar itens após criação. Para refazer, delete e recrie o pedido.

**Resposta (200):** Pedido atualizado com itens

### PUT /api/pedidos/:id/status
Atualiza status do pedido.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada:**
```json
{
    "status": "processando"
}
```

**Status Válidos:** "pendente", "processando", "enviado", "entregue", "cancelado"

**Resposta (200):** Pedido com novo status

### DELETE /api/pedidos/:id
Deleta um pedido existente.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Nota:** Apenas pedidos em status "pendente" podem ser deletados.

**Resposta (200):**
```json
{
    "success": true,
    "message": "Pedido deletado com sucesso",
    "data": []
}
```

---

## 6. Utilitários (utils.php)

### POST /api/utils/upload
Realiza upload de arquivo.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Form Data:**
- `arquivo` (file): Arquivo para upload

**Resposta (201):**
```json
{
    "success": true,
    "message": "Arquivo enviado com sucesso",
    "data": {
        "arquivo": "upload_123456_20240210152030.jpg",
        "url": "/uploads/upload_123456_20240210152030.jpg",
        "tipo": "image/jpeg",
        "tamanho": 125000,
        "caminho": "/path/to/uploads/upload_123456_20240210152030.jpg"
    }
}
```

### GET /api/utils/cep/:cep
Busca informações de CEP usando API ViaCEP.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Exemplo:** `GET /api/utils/cep/01000000`

**Resposta (200):**
```json
{
    "success": true,
    "message": "CEP encontrado com sucesso",
    "data": {
        "cep": "01000-000",
        "logradouro": "Avenida Paulista",
        "complemento": "lado ímpar",
        "bairro": "Bela Vista",
        "localidade": "São Paulo",
        "uf": "SP",
        "ibge": "3550308",
        "gia": "1004947",
        "ddd": "11",
        "siafi": "7107"
    }
}
```

### POST /api/utils/export-excel
Exporta dados para arquivo CSV.

**Headers Requeridos:** `Authorization: Bearer {token}`

**Entrada:**
```json
{
    "tabela": "clientes",
    "filtros": {
        "cidade": "São Paulo"
    }
}
```

**Tabelas Permitidas:** "clientes", "orcamentos", "agendamentos"

**Resposta (201):**
```json
{
    "success": true,
    "message": "Exportação realizada com sucesso",
    "data": {
        "arquivo": "clientes_20240210152030.csv",
        "url": "/uploads/exports/clientes_20240210152030.csv",
        "total_registros": 45,
        "formato": "CSV",
        "criado_em": "2024-02-10 15:30:00"
    }
}
```

---

## Códigos de Erro HTTP

- **200 OK**: Requisição bem-sucedida
- **201 Created**: Recurso criado com sucesso
- **400 Bad Request**: Dados inválidos ou incompletos
- **401 Unauthorized**: Autenticação inválida ou ausente
- **403 Forbidden**: Acesso não autorizado
- **404 Not Found**: Recurso não encontrado
- **405 Method Not Allowed**: Método HTTP não permitido
- **500 Internal Server Error**: Erro no servidor

---

## Estrutura de Resposta

### Sucesso
```json
{
    "success": true,
    "message": "Mensagem descritiva",
    "data": { /* dados retornados */ }
}
```

### Erro
```json
{
    "success": false,
    "message": "Mensagem de erro",
    "error": true,
    "data": { /* dados adicionais opcionais */ }
}
```

---

## Paginação Padrão

Todos os endpoints que retornam listas incluem informações de paginação:

```json
"paginacao": {
    "pagina_atual": 1,
    "total_itens": 100,
    "itens_por_pagina": 10,
    "total_paginas": 10
}
```

---

## Validações

### Email
Deve ser um email válido (formato: user@example.com)

### CPF
Deve conter 11 dígitos válidos

### Telefone
Deve conter apenas dígitos

### CEP
Deve conter 8 dígitos

### Preço/Valor
Deve ser um número positivo com até 2 casas decimais

### Quantidade
Deve ser um número inteiro não-negativo

---

## Exemplos de Uso com cURL

### Login
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@email.com","senha":"senha123"}'
```

### Listar Clientes
```bash
curl -X GET "http://localhost/api/clientes?page=1&limit=10" \
  -H "Authorization: Bearer {token}"
```

### Criar Produto
```bash
curl -X POST http://localhost/api/produtos \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "nome":"Produto",
    "codigo":"PROD001",
    "preco":99.99,
    "categoria_id":1
  }'
```

### Criar Pedido
```bash
curl -X POST http://localhost/api/pedidos \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "cliente_id":1,
    "itens":[
      {"id_produto":1,"quantidade":2,"preco_unitario":99.99}
    ]
  }'
```

---

## Notas Importantes

1. **Autenticação**: Todos os endpoints (exceto login) requerem token JWT válido
2. **Validação**: Todas as entradas são validadas e sanitizadas
3. **Paginação**: Máximo de 100 itens por página
4. **Timestamps**: Todos em formato ISO 8601 (YYYY-MM-DD HH:MM:SS)
5. **Usuário Criador**: Cada recurso criado registra o usuário que o criou
6. **Soft Delete**: Alguns recursos podem ser marcados como inativos em vez de deletados
7. **Logs**: Todas as operações são registradas em logs
