# Configuração da API REST

## Arquivos Criados

✓ **routes.php** (124 linhas)
  - Roteador principal da API
  - Gerencia CORS, requisições, respostas JSON
  - Trata erros e logging

✓ **auth.php** (199 linhas)
  - Endpoints de autenticação
  - Login, logout, refresh token, obter usuário, alterar senha
  - Integrado com classe Auth e validações

✓ **clientes.php** (375 linhas)
  - CRUD completo de clientes
  - Listagem paginada, busca, criar, atualizar, deletar
  - Validação de CPF, email, telefone, CEP

✓ **utils.php** (274 linhas)
  - Upload de arquivos
  - Busca de CEP (ViaCEP)
  - Exportação de dados para Excel/CSV

✓ **exemplo-uso-api.php** (318 linhas)
  - Exemplos práticos de uso da API
  - Cliente HTTP cURL customizado
  - Testes de todos os endpoints

✓ **README.md** (16.5KB)
  - Documentação completa da API
  - Exemplos de requisições/respostas
  - Guia de validações e tratamento de erros

---

## Requisitos de Sistema

### PHP
- Versão: 7.4+ (testado em 8.0+)
- Extensões:
  - PDO MySQL
  - cURL (para busca de CEP)
  - JSON (nativo)
  - Hash (nativo para password_hash)

### Banco de Dados
- MySQL 5.7+ ou MariaDB 10.3+
- Tabelas necessárias:
  - `usuarios` - Usuários do sistema
  - `clientes` - Dados dos clientes

### Permissões
- Diretório `/public_html/uploads/` - escrita
- Diretório `/public_html/uploads/exports/` - escrita
- Diretório `/public_html/logs/` - escrita

---

## Estrutura do Banco de Dados

### Tabela: usuarios
```sql
CREATE TABLE usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  tipo ENUM('admin', 'tecnico', 'cliente') NOT NULL DEFAULT 'cliente',
  ativo BOOLEAN NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_login DATETIME,
  ultimo_logout DATETIME,
  INDEX (email),
  INDEX (tipo)
);
```

### Tabela: clientes
```sql
CREATE TABLE clientes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  cpf VARCHAR(14) UNIQUE,
  telefone VARCHAR(20),
  endereco VARCHAR(255),
  cidade VARCHAR(100),
  estado VARCHAR(2),
  cep VARCHAR(10),
  observacoes TEXT,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  criado_por INT NOT NULL,
  atualizado_em DATETIME,
  atualizado_por INT,
  INDEX (nome),
  INDEX (email),
  INDEX (cpf),
  INDEX (estado),
  FOREIGN KEY (criado_por) REFERENCES usuarios(id),
  FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
);
```

---

## Configuração Inicial

### 1. Variáveis de Ambiente (.env)

Adicione ao arquivo `.env`:

```env
# JWT Configuration
JWT_SECRET=seu_secret_muito_seguro_aqui
JWT_EXPIRATION=3600

# API Configuration
API_URL=http://localhost
APP_DEBUG=true

# Upload
MAX_UPLOAD_SIZE=10485760
ALLOWED_EXTENSIONS=jpg,jpeg,png,pdf,doc,docx,xls,xlsx
```

### 2. Constantes Globais

O arquivo `config/constants.php` já contém:
- Caminhos de diretórios
- Tipos de usuário
- Status de pedidos
- Formatos de data
- Paginação padrão

### 3. Criar Usuário Admin (SQL)

```sql
INSERT INTO usuarios (nome, email, senha, tipo, ativo) 
VALUES (
  'Administrador',
  'admin@example.com',
  '$2y$10$...',  -- Hash bcrypt da senha
  'admin',
  1
);
```

Para gerar o hash bcrypt em PHP:
```php
echo password_hash('sua_senha', PASSWORD_DEFAULT);
```

---

## Testando a API

### 1. Usando cURL (linha de comando)

```bash
# Login
curl -X POST http://localhost/api/routes.php/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","senha":"senha123"}'

# Listar clientes
curl -X GET "http://localhost/api/routes.php/clientes?page=1&limit=20" \
  -H "Authorization: Bearer <TOKEN>"
```

### 2. Usando Postman/Insomnia

1. Importe coleção dos exemplos
2. Configure variáveis:
   - `base_url`: http://localhost/api/routes.php
   - `token`: Obtido após login
3. Execute requisições

### 3. Usando PHP

```php
// Incluir exemplo de uso
require_once __DIR__ . '/api/exemplo-uso-api.php';
```

---

## Estrutura de Resposta

### Sucesso
```json
{
  "success": true,
  "message": "Descrição",
  "data": { /* dados */ }
}
```

### Erro
```json
{
  "success": false,
  "message": "Descrição do erro",
  "error": true
}
```

---

## Headers HTTP Importantes

### Request
```
Content-Type: application/json
Authorization: Bearer <TOKEN_JWT>
```

### Response
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Content-Type: application/json; charset=utf-8
```

---

## Tratamento de Erros

A API retorna erros padronizados:

| Código | Situação |
|--------|----------|
| 400 | Dados inválidos ou ausentes |
| 401 | Token inválido/expirado ou credenciais incorretas |
| 403 | Acesso não autorizado |
| 404 | Recurso não encontrado |
| 405 | Método HTTP não permitido |
| 500 | Erro interno do servidor |
| 503 | Serviço indisponível (ex: ViaCEP offline) |

---

## Segurança

### Implementado
✓ Prepared Statements (prevenção SQL Injection)
✓ Hash bcrypt para senhas
✓ JWT com expiração
✓ Sanitização de entrada (Validator)
✓ Validação de tipo de arquivo
✓ Validação de tamanho de arquivo
✓ CORS configurável
✓ Logging de erros
✓ Headers de segurança

### Recomendações Adicionais
- [ ] Implementar rate limiting
- [ ] Usar HTTPS em produção
- [ ] Configurar CORS para origens específicas
- [ ] Implementar chave API para clientes externos
- [ ] Adicionar autenticação 2FA
- [ ] Backup regular do banco de dados
- [ ] Monitorar logs de erro

---

## Logging

Erros são registrados em:
- `/public_html/logs/api_YYYY-MM-DD.log` - Erros da API
- `/public_html/logs/database_YYYY-MM-DD.log` - Erros do banco

Formato de log:
```
[2024-02-10 15:30:00] ERRO: Descrição do erro
```

---

## Endpoints por Tipo de Usuário

### Admin
- Acesso a todos os endpoints
- Todas as operações CRUD

### Técnico
- Acesso a: clientes, orcamentos, tabelas de preços, histórico, garantias, relatórios, financeiro
- Operações: Criar, ler, atualizar, deletar

### Cliente
- Acesso limitado
- Apenas dados próprios
- Operações: Apenas leitura

---

## Melhorias Futuras

1. **Rate Limiting**
   - Limitar requisições por IP
   - Implementar cache com Redis

2. **Webhooks**
   - Notificar eventos em tempo real
   - Integração com serviços externos

3. **Versionamento**
   - Suportar múltiplas versões da API
   - Rota: `/api/v1/`, `/api/v2/`

4. **GraphQL**
   - Alternativa ao REST
   - Queries mais flexíveis

5. **Documentação Automática**
   - OpenAPI/Swagger
   - Documentação interativa

6. **Autenticação OAuth2**
   - Integração com Google, GitHub, etc.
   - Terceiros podem acessar dados

---

## Troubleshooting

### Erro: "Arquivo .env não encontrado"
**Solução:** Copie `.env.example` para `.env` e configure as variáveis.

### Erro: "Não foi possível conectar ao banco de dados"
**Solução:** Verifique credenciais no `config/database.php`.

### Erro: "Token inválido ou expirado"
**Solução:** Faça login novamente para obter novo token.

### Erro: "Arquivo excede o tamanho máximo"
**Solução:** Aumente `MAX_FILE_SIZE` em `.env` ou comprima o arquivo.

### Erro: "CEP não encontrado"
**Solução:** Verifique se o CEP existe. Serviço ViaCEP pode estar offline.

---

## Suporte

Para dúvidas ou problemas:
1. Consulte a documentação em `README.md`
2. Verifique os logs em `/public_html/logs/`
3. Use os exemplos em `exemplo-uso-api.php`
4. Teste com ferramentas como Postman

---

## Changelog

### Versão 1.0.0 (2024-02-10)
- ✓ Endpoints de autenticação (login, logout, refresh, me, change-password)
- ✓ CRUD completo de clientes
- ✓ Upload de arquivos
- ✓ Busca de CEP (ViaCEP)
- ✓ Exportação para Excel/CSV
- ✓ Validações completas
- ✓ Tratamento de erros
- ✓ Documentação e exemplos
