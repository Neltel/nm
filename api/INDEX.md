# API REST - Índice de Arquivos

## 📁 Estrutura de Arquivos

```
/public_html/api/
├── routes.php              ← Roteador principal
├── auth.php                ← Endpoints de autenticação
├── clientes.php            ← CRUD de clientes
├── utils.php               ← Endpoints utilitários
├── exemplo-uso-api.php     ← Exemplos práticos
├── README.md               ← Documentação completa
├── SETUP.md                ← Guia de configuração
└── INDEX.md               ← Este arquivo
```

---

## 📄 Descrição dos Arquivos

### 1. **routes.php** - Roteador Principal
**Linhas:** 124 | **Tamanho:** 3.6 KB

Responsável por:
- Receber todas as requisições HTTP
- Configurar headers de CORS
- Validar requisições OPTIONS
- Rotear para endpoint apropriado
- Centralizar tratamento de erros
- Logging de exceções

**Uso:** Ponto de entrada de toda a API
```php
// Incluído como ponto inicial
require_once '/api/routes.php';
```

---

### 2. **auth.php** - Autenticação
**Linhas:** 199 | **Tamanho:** 5.9 KB

Endpoints implementados:
- `POST /auth/login` - Login do usuário
- `POST /auth/logout` - Logout
- `POST /auth/refresh` - Renovar token JWT
- `GET /auth/me` - Obter dados do usuário
- `POST /auth/change-password` - Alterar senha

Validações:
- Email válido
- Senha mínimo 6 caracteres
- Token JWT com expiração

---

### 3. **clientes.php** - CRUD de Clientes
**Linhas:** 375 | **Tamanho:** 13 KB

Endpoints implementados:
- `GET /clientes` - Listar com paginação
- `GET /clientes/search` - Buscar por termo
- `GET /clientes/:id` - Obter específico
- `POST /clientes` - Criar cliente
- `PUT /clientes/:id` - Atualizar
- `DELETE /clientes/:id` - Deletar

Validações:
- Email e CPF únicos
- CPF válido (dígitos verificadores)
- Telefone (múltiplos formatos)
- CEP (8 dígitos)

---

### 4. **utils.php** - Endpoints Utilitários
**Linhas:** 274 | **Tamanho:** 9.4 KB

Endpoints implementados:
- `POST /utils/upload` - Upload de arquivo
- `GET /utils/cep/:cep` - Buscar CEP (ViaCEP)
- `POST /utils/export-excel` - Exportar para CSV

Funcionalidades:
- Validação de tipo de arquivo
- Validação de tamanho (máx 10MB)
- Integração com ViaCEP
- Geração de arquivo CSV

---

### 5. **exemplo-uso-api.php** - Exemplos Práticos
**Linhas:** 318 | **Tamanho:** 12 KB

Fornece:
- Classe `ApiClient` com cURL
- Exemplos de todos os endpoints
- Testes de validação
- Testes de erros
- Fluxo completo de uso

Como usar:
```bash
php exemplo-uso-api.php
```

---

### 6. **README.md** - Documentação Completa
**Tamanho:** 16.5 KB

Contém:
- Visão geral da API
- Estrutura de resposta
- Todos os endpoints documentados
- Exemplos com cURL
- Códigos de status HTTP
- Tratamento de erros
- Validações implementadas
- Configuração de CORS
- Logging
- Segurança

---

### 7. **SETUP.md** - Guia de Configuração
**Tamanho:** 7.7 KB

Contém:
- Requisitos de sistema
- Estrutura do banco de dados (SQL)
- Configuração inicial
- Variáveis de ambiente
- Scripts de teste
- Troubleshooting
- Changelog

---

## 🚀 Quick Start

### 1. Criar Tabelas no Banco
Veja `SETUP.md` para scripts SQL completos

### 2. Configurar .env
```env
JWT_SECRET=seu_secret_muito_seguro
JWT_EXPIRATION=3600
```

### 3. Fazer Login
```bash
curl -X POST http://localhost/api/routes.php/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","senha":"senha123"}'
```

### 4. Usar Token em Requisições
```bash
curl -X GET http://localhost/api/routes.php/clientes \
  -H "Authorization: Bearer <TOKEN>"
```

---

## 📚 Como Ler a Documentação

1. **Comece por:** README.md (visão geral e endpoints)
2. **Depois:** SETUP.md (configuração inicial)
3. **Pratique:** exemplo-uso-api.php (exemplos práticos)
4. **Referência:** Código comentado em cada arquivo .php

---

## 🔒 Segurança

Implementado:
- ✓ JWT com expiração
- ✓ Hash bcrypt para senhas
- ✓ Prepared statements
- ✓ Sanitização de entrada
- ✓ Validação de arquivo
- ✓ CORS configurável
- ✓ Logging de erros

---

## 📊 Estatísticas

| Arquivo | Linhas | Endpoints | Validações |
|---------|--------|-----------|------------|
| routes.php | 124 | - | - |
| auth.php | 199 | 5 | Email, Senha |
| clientes.php | 375 | 6 | Email, CPF, CEP, Telefone |
| utils.php | 274 | 3 | Arquivo, CEP |
| exemplo-uso-api.php | 318 | Todos | Todos |
| **Total** | **1.290** | **14** | **Completas** |

---

## 🧪 Testes

Todos os arquivos passaram em:
- ✓ Validação de sintaxe PHP
- ✓ Integração com classes existentes
- ✓ Validação de segurança
- ✓ Testes de resposta JSON

---

## 📞 Suporte

Para dúvidas:
1. Leia a documentação em README.md
2. Consulte exemplos em exemplo-uso-api.php
3. Verifique logs em `/logs/`
4. Teste com Postman/Insomnia

---

## 🔄 Fluxo de Requisição

```
Requisição HTTP
    ↓
routes.php (valida CORS, método)
    ↓
Roteia para endpoint (auth, clientes, utils)
    ↓
Arquivo específico (auth.php, clientes.php, utils.php)
    ↓
Valida autenticação e entrada
    ↓
Executa operação (DB, validação, integração)
    ↓
Retorna resposta JSON
```

---

## 🎯 Próximas Implementações

- [ ] Rate limiting
- [ ] Cache com Redis
- [ ] Webhooks
- [ ] GraphQL
- [ ] OAuth2
- [ ] Autenticação 2FA
- [ ] Documentação OpenAPI/Swagger

---

## 📝 Notas

- Todos os códigos estão comentados em português
- Seguem padrões RESTful
- Integrados com classes Database, Auth, Validator
- Prontos para produção
- Configuração segura por padrão

---

**Versão:** 1.0.0  
**Data de criação:** 2024-02-10  
**Última atualização:** 2024-02-10
