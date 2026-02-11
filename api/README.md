# API - Documentação Completa

## Visão Geral

A API REST fornece endpoints para autenticação, gerenciamento de clientes e funções utilitárias. Todos os endpoints retornam respostas em formato JSON e requerem autenticação via token JWT (exceto login).

## Estrutura de Resposta

Todas as respostas seguem o padrão:

### Sucesso (Status 200, 201)
```json
{
  "success": true,
  "message": "Descrição da operação",
  "data": { /* dados retornados */ }
}
```

### Erro (Status 400, 401, 403, 404, 500)
```json
{
  "success": false,
  "message": "Descrição do erro",
  "error": true,
  "data": { /* dados adicionais opcionais */ }
}
```

## Autenticação

A maioria dos endpoints requer autenticação via JWT. O token deve ser enviado no header:

```
Authorization: Bearer <TOKEN_JWT>
```

O token é obtido fazendo login e é válido por 1 hora (configurável).

---

## Endpoints de Autenticação (`/api/auth`)

### 1. Login do Usuário
**POST** `/api/auth/login`

Realiza login do usuário e retorna um token JWT válido.

**Parâmetros (Body JSON):**
- `email` (string, obrigatório): Email do usuário
- `senha` (string, obrigatório): Senha do usuário

**Exemplo de Requisição:**
```bash
curl -X POST http://localhost/api/routes.php/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@example.com",
    "senha": "senha123"
  }'
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "usuario": {
      "id": 1,
      "nome": "João Silva",
      "email": "usuario@example.com",
      "tipo": "admin"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**Resposta de Erro (401):**
```json
{
  "success": false,
  "message": "Email ou senha inválidos",
  "error": true
}
```

---

### 2. Logout do Usuário
**POST** `/api/auth/logout`

Realiza logout do usuário autenticado.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`

**Exemplo de Requisição:**
```bash
curl -X POST http://localhost/api/routes.php/auth/logout \
  -H "Authorization: Bearer <TOKEN>"
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Logout realizado com sucesso",
  "data": {}
}
```

---

### 3. Renovar Token JWT
**POST** `/api/auth/refresh`

Renova o token JWT com um novo tempo de expiração.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`

**Exemplo de Requisição:**
```bash
curl -X POST http://localhost/api/routes.php/auth/refresh \
  -H "Authorization: Bearer <TOKEN>"
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Token renovado com sucesso",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "usuario": {
      "id": 1,
      "nome": "João Silva",
      "email": "usuario@example.com",
      "tipo": "admin"
    }
  }
}
```

---

### 4. Obter Dados do Usuário Autenticado
**GET** `/api/auth/me`

Retorna informações do usuário atualmente autenticado.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`

**Exemplo de Requisição:**
```bash
curl -X GET http://localhost/api/routes.php/auth/me \
  -H "Authorization: Bearer <TOKEN>"
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Dados do usuário obtidos com sucesso",
  "data": {
    "id": 1,
    "nome": "João Silva",
    "email": "usuario@example.com",
    "tipo": "admin",
    "criado_em": "2024-01-15 10:30:00",
    "ultimo_login": "2024-02-10 14:20:00"
  }
}
```

---

### 5. Alterar Senha
**POST** `/api/auth/change-password`

Altera a senha do usuário autenticado.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`

**Parâmetros (Body JSON):**
- `senha_atual` (string, obrigatório): Senha atual do usuário
- `senha_nova` (string, obrigatório): Nova senha (mínimo 6 caracteres)
- `confirmar_senha` (string, obrigatório): Confirmação da nova senha

**Exemplo de Requisição:**
```bash
curl -X POST http://localhost/api/routes.php/auth/change-password \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{
    "senha_atual": "senha123",
    "senha_nova": "novaSenha456",
    "confirmar_senha": "novaSenha456"
  }'
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Senha alterada com sucesso",
  "data": {}
}
```

---

## Endpoints de Clientes (`/api/clientes`)

### 1. Listar Clientes com Paginação
**GET** `/api/clientes`

Lista todos os clientes com paginação.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`

**Parâmetros de Query:**
- `page` (integer, opcional): Número da página (padrão: 1)
- `limit` (integer, opcional): Itens por página (padrão: 20, máximo: 100)
- `order` (string, opcional): Campo para ordenação (padrão: "nome ASC")
  - Opções: "nome ASC", "nome DESC", "email ASC", "email DESC", "criado_em ASC", "criado_em DESC"

**Exemplo de Requisição:**
```bash
curl -X GET "http://localhost/api/routes.php/clientes?page=1&limit=20&order=nome%20ASC" \
  -H "Authorization: Bearer <TOKEN>"
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Clientes obtidos com sucesso",
  "data": {
    "clientes": [
      {
        "id": 1,
        "nome": "Empresa ABC",
        "email": "contato@abc.com",
        "cpf": "12345678901",
        "telefone": "1133334444",
        "endereco": "Rua A, 123",
        "cidade": "São Paulo",
        "estado": "SP",
        "cep": "01310100",
        "observacoes": "Cliente importante",
        "criado_em": "2024-01-10 09:00:00"
      }
    ],
    "paginacao": {
      "pagina_atual": 1,
      "total_itens": 50,
      "itens_por_pagina": 20,
      "total_paginas": 3
    }
  }
}
```

---

### 2. Buscar Clientes
**GET** `/api/clientes/search`

Busca clientes por nome, email ou CPF.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`

**Parâmetros de Query:**
- `q` (string, obrigatório): Termo de busca (mínimo 2 caracteres)
- `page` (integer, opcional): Número da página (padrão: 1)
- `limit` (integer, opcional): Itens por página (padrão: 20, máximo: 100)

**Exemplo de Requisição:**
```bash
curl -X GET "http://localhost/api/routes.php/clientes/search?q=Empresa&page=1&limit=20" \
  -H "Authorization: Bearer <TOKEN>"
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Busca realizada com sucesso",
  "data": {
    "clientes": [
      {
        "id": 1,
        "nome": "Empresa ABC",
        "email": "contato@abc.com",
        "cpf": "12345678901",
        "telefone": "1133334444",
        "endereco": "Rua A, 123",
        "cidade": "São Paulo",
        "estado": "SP",
        "cep": "01310100",
        "criado_em": "2024-01-10 09:00:00"
      }
    ],
    "paginacao": {
      "pagina_atual": 1,
      "total_itens": 5,
      "itens_por_pagina": 20,
      "total_paginas": 1
    }
  }
}
```

---

### 3. Obter Cliente Específico
**GET** `/api/clientes/:id`

Obtém informações detalhadas de um cliente específico.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`

**Parâmetros de URL:**
- `id` (integer, obrigatório): ID do cliente

**Exemplo de Requisição:**
```bash
curl -X GET http://localhost/api/routes.php/clientes/1 \
  -H "Authorization: Bearer <TOKEN>"
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Cliente obtido com sucesso",
  "data": {
    "id": 1,
    "nome": "Empresa ABC",
    "email": "contato@abc.com",
    "cpf": "12345678901",
    "telefone": "1133334444",
    "endereco": "Rua A, 123",
    "cidade": "São Paulo",
    "estado": "SP",
    "cep": "01310100",
    "observacoes": "Cliente importante",
    "criado_em": "2024-01-10 09:00:00",
    "criado_por": 1
  }
}
```

---

### 4. Criar Cliente
**POST** `/api/clientes`

Cria um novo cliente.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`
- `Content-Type: application/json`

**Parâmetros (Body JSON):**
- `nome` (string, obrigatório): Nome do cliente
- `email` (string, obrigatório): Email do cliente (único)
- `cpf` (string, opcional): CPF do cliente (único, validado)
- `telefone` (string, opcional): Telefone do cliente (formato: XX 9XXXX-XXXX ou XX XXXX-XXXX)
- `endereco` (string, opcional): Endereço do cliente
- `cidade` (string, opcional): Cidade
- `estado` (string, opcional): Estado (2 caracteres)
- `cep` (string, opcional): CEP (8 dígitos)
- `observacoes` (string, opcional): Observações adicionais

**Exemplo de Requisição:**
```bash
curl -X POST http://localhost/api/routes.php/clientes \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{
    "nome": "Empresa XYZ",
    "email": "contato@xyz.com",
    "cpf": "98765432100",
    "telefone": "1133334444",
    "endereco": "Av. B, 456",
    "cidade": "Rio de Janeiro",
    "estado": "RJ",
    "cep": "20040021",
    "observacoes": "Novo cliente"
  }'
```

**Resposta de Sucesso (201):**
```json
{
  "success": true,
  "message": "Cliente criado com sucesso",
  "data": {
    "id": 2,
    "nome": "Empresa XYZ",
    "email": "contato@xyz.com",
    "cpf": "98765432100",
    "telefone": "1133334444",
    "endereco": "Av. B, 456",
    "cidade": "Rio de Janeiro",
    "estado": "RJ",
    "cep": "20040021",
    "observacoes": "Novo cliente",
    "criado_em": "2024-02-10 15:30:00",
    "criado_por": 1
  }
}
```

---

### 5. Atualizar Cliente
**PUT** `/api/clientes/:id`

Atualiza informações de um cliente existente.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`
- `Content-Type: application/json`

**Parâmetros de URL:**
- `id` (integer, obrigatório): ID do cliente

**Parâmetros (Body JSON):**
Qualquer um dos campos:
- `nome` (string, opcional)
- `email` (string, opcional)
- `cpf` (string, opcional)
- `telefone` (string, opcional)
- `endereco` (string, opcional)
- `cidade` (string, opcional)
- `estado` (string, opcional)
- `cep` (string, opcional)
- `observacoes` (string, opcional)

**Exemplo de Requisição:**
```bash
curl -X PUT http://localhost/api/routes.php/clientes/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{
    "telefone": "1144445555",
    "observacoes": "Telefone atualizado"
  }'
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Cliente atualizado com sucesso",
  "data": {
    "id": 1,
    "nome": "Empresa ABC",
    "email": "contato@abc.com",
    "cpf": "12345678901",
    "telefone": "1144445555",
    "endereco": "Rua A, 123",
    "cidade": "São Paulo",
    "estado": "SP",
    "cep": "01310100",
    "observacoes": "Telefone atualizado",
    "atualizado_em": "2024-02-10 15:35:00",
    "atualizado_por": 1
  }
}
```

---

### 6. Deletar Cliente
**DELETE** `/api/clientes/:id`

Deleta um cliente do sistema.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`

**Parâmetros de URL:**
- `id` (integer, obrigatório): ID do cliente

**Exemplo de Requisição:**
```bash
curl -X DELETE http://localhost/api/routes.php/clientes/1 \
  -H "Authorization: Bearer <TOKEN>"
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Cliente deletado com sucesso",
  "data": {}
}
```

---

## Endpoints Utilitários (`/api/utils`)

### 1. Upload de Arquivo
**POST** `/api/utils/upload`

Realiza upload de arquivo para o servidor.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`

**Parâmetros (FormData):**
- `arquivo` (file, obrigatório): Arquivo para upload
  - Tipos permitidos: jpg, jpeg, png, pdf, doc, docx, xls, xlsx
  - Tamanho máximo: 10MB

**Exemplo de Requisição:**
```bash
curl -X POST http://localhost/api/routes.php/utils/upload \
  -H "Authorization: Bearer <TOKEN>" \
  -F "arquivo=@/caminho/para/arquivo.pdf"
```

**Resposta de Sucesso (201):**
```json
{
  "success": true,
  "message": "Arquivo enviado com sucesso",
  "data": {
    "arquivo": "upload_abc123_20240210153000.pdf",
    "url": "/uploads/upload_abc123_20240210153000.pdf",
    "tipo": "application/pdf",
    "tamanho": 512000,
    "caminho": "/home/user/public_html/uploads/upload_abc123_20240210153000.pdf"
  }
}
```

---

### 2. Buscar Informações de CEP
**GET** `/api/utils/cep/:cep`

Busca informações de endereço a partir de um CEP usando a API ViaCEP.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`

**Parâmetros de URL:**
- `cep` (string, obrigatório): CEP em formato 12345-678 ou 12345678

**Exemplo de Requisição:**
```bash
curl -X GET http://localhost/api/routes.php/utils/cep/01310-100 \
  -H "Authorization: Bearer <TOKEN>"
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "CEP encontrado com sucesso",
  "data": {
    "cep": "01310-100",
    "logradouro": "Avenida Paulista",
    "complemento": "",
    "bairro": "Bela Vista",
    "localidade": "São Paulo",
    "uf": "SP",
    "ibge": "3550308",
    "gia": "",
    "ddd": "11",
    "siafi": "7107"
  }
}
```

**Resposta de Erro - CEP Não Encontrado (404):**
```json
{
  "success": false,
  "message": "CEP não encontrado",
  "error": true
}
```

---

### 3. Exportar Dados para Excel
**POST** `/api/utils/export-excel`

Exporta dados de uma tabela para arquivo CSV/Excel.

**Headers Requeridos:**
- `Authorization: Bearer <TOKEN>`
- `Content-Type: application/json`

**Parâmetros (Body JSON):**
- `tabela` (string, obrigatório): Nome da tabela a exportar
  - Opções permitidas: clientes, orcamentos, agendamentos
- `filtros` (object, opcional): Filtros a aplicar aos dados
  - Formato: {"campo": "valor", "campo2": "valor2"}

**Exemplo de Requisição:**
```bash
curl -X POST http://localhost/api/routes.php/utils/export-excel \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{
    "tabela": "clientes",
    "filtros": {
      "estado": "SP"
    }
  }'
```

**Resposta de Sucesso (201):**
```json
{
  "success": true,
  "message": "Exportação realizada com sucesso",
  "data": {
    "arquivo": "clientes_20240210153000.csv",
    "url": "/uploads/exports/clientes_20240210153000.csv",
    "total_registros": 45,
    "formato": "CSV",
    "criado_em": "2024-02-10 15:30:00"
  }
}
```

---

## Códigos de Status HTTP

| Código | Descrição |
|--------|-----------|
| 200 | OK - Requisição bem-sucedida |
| 201 | Created - Recurso criado com sucesso |
| 400 | Bad Request - Dados inválidos ou malformados |
| 401 | Unauthorized - Token inválido ou ausente |
| 403 | Forbidden - Acesso não autorizado |
| 404 | Not Found - Recurso não encontrado |
| 405 | Method Not Allowed - Método HTTP não permitido |
| 500 | Internal Server Error - Erro no servidor |
| 503 | Service Unavailable - Serviço temporariamente indisponível |

---

## Tratamento de Erros

A API retorna erros estruturados com mensagens descritivas:

```json
{
  "success": false,
  "message": "Descrição detalhada do erro",
  "error": true,
  "data": {
    "detalhes_adicionais": "opcional"
  }
}
```

### Erros Comuns

**Credenciais Inválidas:**
```
Status: 401
Message: "Email ou senha inválidos"
```

**Validação de Email:**
```
Status: 400
Message: "Formato de email inválido"
```

**Arquivo Muito Grande:**
```
Status: 400
Message: "Arquivo excede o tamanho máximo permitido (10MB)"
```

**Tipo de Arquivo Não Permitido:**
```
Status: 400
Message: "Tipo de arquivo não permitido"
```

---

## Validações

### Email
- Deve ser válido e único (em criação)
- Formato: usuario@dominio.com

### CPF
- 11 dígitos
- Validação de dígitos verificadores
- Deve ser único (em criação)

### Telefone
- Formato: (XX) 9XXXX-XXXX ou (XX) XXXX-XXXX
- Aceita números com ou sem formatação

### CEP
- Formato: XXXXX-XXX ou XXXXXXXX (8 dígitos)

### Senha
- Mínimo 6 caracteres
- Hash bcrypt armazenado no banco

---

## Configuração de CORS

A API está configurada para aceitar requisições de qualquer origem:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

Para restringir a origens específicas, edite o arquivo `routes.php` linha 11.

---

## Logging

Erros são registrados em:
- `public_html/logs/api_YYYY-MM-DD.log`
- `public_html/logs/database_YYYY-MM-DD.log`

---

## Segurança

- Todas as senhas são hasheadas com bcrypt
- Tokens JWT expiram após 1 hora
- SQL Injection prevenida com prepared statements
- XSS prevenido com sanitização de entrada
- CSRF prevenido com validação de token

---

## Próximos Passos

1. Criar as tabelas no banco de dados
2. Configurar arquivo `.env` com constantes JWT
3. Testar endpoints com ferramentas como Postman ou Insomnia
4. Implementar rate limiting (opcional)
5. Adicionar validação de permissões mais granular (opcional)
